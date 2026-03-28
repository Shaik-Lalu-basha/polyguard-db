<?php
/**
 * POLYGUARD AI - Duty Report & GPS Camera Management
 * 
 * Handles:
 * - GPS coordinates capture with images
 * - Report submission (arrival/departure)
 * - Location entry/exit tracking
 * - Violation detection
 * - Report management for admin/control
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

class DutyReportManager {
    private $pdo;
    private $user_id;

    public function __construct($pdo, $user_id = null) {
        $this->pdo = $pdo;
        $this->user_id = $user_id ?? $_SESSION['user']['user_id'] ?? null;
    }

    /**
     * Submit duty arrival report with GPS image
     */
    public function submitArrivalReport($duty_id, $latitude, $longitude, $image_base64 = null) {
        try {
            // Get duty assignment
            $stmt = $this->pdo->prepare('SELECT * FROM duty_assignments WHERE duty_id=? AND personnel_id=? LIMIT 1');
            $stmt->execute([$duty_id, $this->user_id]);
            $duty = $stmt->fetch();

            if (!$duty) {
                return ['error' => 'Duty not found'];
            }

            // Calculate distance from duty location
            $distance = $this->calculateDistance(
                $duty['latitude'], $duty['longitude'],
                $latitude, $longitude
            );

            $location_name = $this->getLocationName($latitude, $longitude);

            // Save image if provided
            $image_path = null;
            if ($image_base64) {
                $image_path = $this->saveImage($this->user_id, $duty_id, $image_base64, 'arrival');
            }

            // Create arrival report
            $reportStmt = $this->pdo->prepare('
                INSERT INTO duty_reports 
                (personnel_id, duty_id, report_type, latitude, longitude, location_name, 
                 image_path, image_base64, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $reportStmt->execute([
                $this->user_id,
                $duty_id,
                'arrival',
                $latitude,
                $longitude,
                $location_name,
                $image_path,
                $image_base64 ? substr($image_base64, 0, 100000) : null, // Limit base64 size
                "Officer arrived at duty location. Distance from checkpoint: {$distance}m"
            ]);

            $report_id = $this->pdo->lastInsertId();

            // Update duty assignment
            $this->pdo->prepare('UPDATE duty_assignments SET arrival_time=NOW(), arrival_report_id=? WHERE duty_id=?')
                ->execute([$report_id, $duty_id]);

            // Create or update duty summary
            $this->updateDutySummary($duty_id, 'arrival', $report_id);

            // Log entry event
            $this->logEntryExit('entry', $duty_id, $latitude, $longitude, $distance);

            // Log to blockchain
            SecurityMiddleware::logEvent($this->pdo, 'duty_arrival', [
                'personnel_id' => $this->user_id,
                'duty_id' => $duty_id,
                'report_id' => $report_id,
                'distance' => $distance
            ]);

            return [
                'success' => true,
                'message' => 'Arrival report submitted',
                'report_id' => $report_id,
                'distance' => $distance,
                'image_path' => $image_path
            ];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Submit duty departure report with GPS image
     */
    public function submitDepartureReport($duty_id, $latitude, $longitude, $image_base64 = null) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM duty_assignments WHERE duty_id=? AND personnel_id=? LIMIT 1');
            $stmt->execute([$duty_id, $this->user_id]);
            $duty = $stmt->fetch();

            if (!$duty) {
                return ['error' => 'Duty not found'];
            }

            if (!$duty['arrival_time']) {
                return ['error' => 'Officer has not arrived yet'];
            }

            $distance = $this->calculateDistance(
                $duty['latitude'], $duty['longitude'],
                $latitude, $longitude
            );

            $location_name = $this->getLocationName($latitude, $longitude);
            $image_path = null;

            if ($image_base64) {
                $image_path = $this->saveImage($this->user_id, $duty_id, $image_base64, 'departure');
            }

            // Create departure report
            $reportStmt = $this->pdo->prepare('
                INSERT INTO duty_reports 
                (personnel_id, duty_id, report_type, latitude, longitude, location_name, 
                 image_path, image_base64, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $reportStmt->execute([
                $this->user_id,
                $duty_id,
                'departure',
                $latitude,
                $longitude,
                $location_name,
                $image_path,
                $image_base64 ? substr($image_base64, 0, 100000) : null,
                "Officer departed from duty location"
            ]);

            $report_id = $this->pdo->lastInsertId();

            // Calculate duty duration
            $duration = strtotime('now') - strtotime($duty['arrival_time']);

            // Update duty assignment
            $this->pdo->prepare('UPDATE duty_assignments SET departure_time=NOW(), status="completed" WHERE duty_id=?')
                ->execute([$duty_id]);

            // Update duty summary
            $this->updateDutySummary($duty_id, 'departure', $report_id, $duration);

            // Log exit event
            $this->logEntryExit('departure', $duty_id, $latitude, $longitude, $distance);

            // Log to blockchain
            SecurityMiddleware::logEvent($this->pdo, 'duty_departure', [
                'personnel_id' => $this->user_id,
                'duty_id' => $duty_id,
                'report_id' => $report_id,
                'duration' => $duration
            ]);

            return [
                'success' => true,
                'message' => 'Departure report submitted',
                'report_id' => $report_id,
                'duration' => $duration,
                'image_path' => $image_path
            ];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Track location and detect entry/exit
     */
    public function trackLocation($latitude, $longitude, $accuracy = null) {
        try {
            $user_id = $this->user_id;

            // Get active duty
            $stmt = $this->pdo->prepare('
                SELECT * FROM duty_assignments 
                WHERE personnel_id=? AND status="active" 
                ORDER BY created_at DESC LIMIT 1
            ');
            $stmt->execute([$user_id]);
            $duty = $stmt->fetch();

            $result = [
                'success' => true,
                'has_active_duty' => (bool)$duty
            ];

            if ($duty) {
                $distance = $this->calculateDistance(
                    $duty['latitude'], $duty['longitude'],
                    $latitude, $longitude
                );

                $status = $distance <= $duty['radius'] ? 'inside' : 'outside';

                // Check for entry/exit transition
                $lastStmt = $this->pdo->prepare('
                    SELECT status FROM location_tracking 
                    WHERE personnel_id=? AND duty_id=?
                    ORDER BY timestamp DESC LIMIT 1
                ');
                $lastStmt->execute([$user_id, $duty['duty_id']]);
                $lastLocation = $lastStmt->fetch();

                $transition = null;
                if ($lastLocation && $lastLocation['status'] !== $status) {
                    $transition = $lastLocation['status'] . '_to_' . $status;
                    
                    // Log entry/exit event
                    $event_type = ($status === 'outside') ? 'exit' : 'entry';
                    $violation = ($event_type === 'exit') ? true : false;
                    
                    $this->logEntryExit(
                        $event_type,
                        $duty['duty_id'],
                        $latitude,
                        $longitude,
                        $distance,
                        $violation
                    );

                    // Create violation record
                    if ($violation) {
                        $this->createViolation(
                            $duty['duty_id'],
                            'outside_zone',
                            $latitude,
                            $longitude,
                            'Officer left designated zone'
                        );
                    }
                }

                // Update location tracking
                $this->pdo->prepare('
                    INSERT INTO location_tracking 
                    (personnel_id, latitude, longitude, status)
                    VALUES (?, ?, ?, ?)
                ')->execute([$user_id, $latitude, $longitude, $status]);

                // Update location history
                $this->pdo->prepare('
                    INSERT INTO location_history 
                    (personnel_id, duty_id, latitude, longitude, status, distance_from_duty)
                    VALUES (?, ?, ?, ?, ?, ?)
                ')->execute([$user_id, $duty['duty_id'], $latitude, $longitude, $status, $distance]);

                $result['duty_id'] = $duty['duty_id'];
                $result['status'] = $status;
                $result['distance'] = $distance;
                $result['transition'] = $transition;
                $result['compliance_score'] = $this->calculateCompliance($user_id);
            }

            return $result;

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get all duty reports for a user
     */
    public function getDutyReports($filter = 'all') {
        try {
            $query = '
                SELECT r.*, 
                       d.location_name as duty_location, d.start_time, d.end_time,
                       u.name as reviewed_by_name
                FROM duty_reports r
                LEFT JOIN duty_assignments d ON r.duty_id = d.duty_id
                LEFT JOIN users u ON r.reviewed_by = u.user_id
            ';

            $params = [];

            if ($filter === 'my') {
                $query .= ' WHERE r.personnel_id = ?';
                $params[] = $this->user_id;
            } elseif ($filter === 'pending') {
                $query .= ' WHERE r.status = "pending"';
            } elseif ($filter === 'approved') {
                $query .= ' WHERE r.status = "approved"';
            }

            $query .= ' ORDER BY r.created_at DESC LIMIT 100';

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get duty summary with all timing data
     */
    public function getDutySummary($duty_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM duty_summary 
                WHERE duty_id = ? LIMIT 1
            ');
            $stmt->execute([$duty_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get entry/exit logs for duty
     */
    public function getEntryExitLogs($duty_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM entry_exit_logs 
                WHERE duty_id = ? 
                ORDER BY timestamp DESC
            ');
            $stmt->execute([$duty_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Review report (admin/control only)
     */
    public function reviewReport($report_id, $status, $comments = null, $reviewer_id = null) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE duty_reports 
                SET status = ?, review_comments = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE report_id = ?
            ');

            $stmt->execute([$status, $comments, $reviewer_id, $report_id]);

            SecurityMiddleware::logEvent($this->pdo, 'report_reviewed', [
                'report_id' => $report_id,
                'status' => $status,
                'reviewed_by' => $reviewer_id
            ]);

            return ['success' => true, 'message' => 'Report reviewed'];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371000; // meters

        $lat1_rad = deg2rad($lat1);
        $lat2_rad = deg2rad($lat2);
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lon = deg2rad($lon2 - $lon1);

        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lon / 2) * sin($delta_lon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earth_radius * $c, 2);
    }

    /**
     * Get location name from coordinates (reverse geocoding)
     */
    private function getLocationName($latitude, $longitude) {
        // This can be enhanced with actual reverse geocoding API
        return "Location: {$latitude}, {$longitude}";
    }

    /**
     * Save image file
     */
    private function saveImage($personnel_id, $duty_id, $image_base64, $type) {
        try {
            $upload_dir = __DIR__ . '/../uploads/duty_reports/';
            @mkdir($upload_dir, 0755, true);

            // Remove data URI prefix if present
            $image_data = $image_base64;
            if (strpos($image_base64, 'base64,') !== false) {
                $image_data = explode('base64,', $image_base64)[1];
            }

            $image_binary = base64_decode($image_data);
            $filename = "report_{$personnel_id}_{$duty_id}_{$type}_" . time() . '.jpg';
            $file_path = $upload_dir . $filename;

            file_put_contents($file_path, $image_binary);

            return 'uploads/duty_reports/' . $filename;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Log entry/exit events
     */
    private function logEntryExit($event_type, $duty_id, $lat, $lon, $distance, $violation = false) {
        try {
            $this->pdo->prepare('
                INSERT INTO entry_exit_logs 
                (personnel_id, duty_id, event_type, latitude, longitude, distance_from_zone, is_violation)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ')->execute([
                $this->user_id,
                $duty_id,
                $event_type,
                $lat,
                $lon,
                $distance,
                $violation ? 1 : 0
            ]);
        } catch (Exception $e) {
            // Log error silently
        }
    }

    /**
     * Create violation record
     */
    private function createViolation($duty_id, $type, $lat, $lon, $details) {
        try {
            $this->pdo->prepare('
                INSERT INTO location_violations 
                (personnel_id, duty_id, violation_type, latitude, longitude, details)
                VALUES (?, ?, ?, ?, ?, ?)
            ')->execute([
                $this->user_id,
                $duty_id,
                $type,
                $lat,
                $lon,
                $details
            ]);
        } catch (Exception $e) {
            // Log error silently
        }
    }

    /**
     * Update duty summary
     */
    private function updateDutySummary($duty_id, $event, $report_id, $duration = 0) {
        try {
            $date = date('Y-m-d');
            
            // Get personal_id from duty
            $stmt = $this->pdo->prepare('SELECT personnel_id FROM duty_assignments WHERE duty_id = ?');
            $stmt->execute([$duty_id]);
            $duty = $stmt->fetch();

            if ($event === 'arrival') {
                // Create new summary if not exists
                $this->pdo->prepare('
                    INSERT IGNORE INTO duty_summary 
                    (personnel_id, duty_id, duty_date, arrival_time, arrival_report_id, status)
                    VALUES (?, ?, ?, NOW(), ?, "in_progress")
                ')->execute([$duty['personnel_id'], $duty_id, $date, $report_id]);

                $this->pdo->prepare('
                    UPDATE duty_summary 
                    SET arrival_time = NOW(), arrival_report_id = ?, status = "in_progress"
                    WHERE duty_id = ? AND duty_date = ?
                ')->execute([$report_id, $duty_id, $date]);

            } elseif ($event === 'departure') {
                $this->pdo->prepare('
                    UPDATE duty_summary 
                    SET departure_time = NOW(), 
                        departure_report_id = ?, 
                        total_duration = ?,
                        status = "completed"
                    WHERE duty_id = ? AND duty_date = ?
                ')->execute([$report_id, $duration, $duty_id, $date]);
            }
        } catch (Exception $e) {
            // Log error silently
        }
    }

    /**
     * Calculate compliance score
     */
    private function calculateCompliance($user_id) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT AVG(compliance_score) as avg_score FROM compliance 
                WHERE personnel_id = ? AND DATE(updated_at) = CURDATE()
            ');
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return round($result['avg_score'] ?? 100, 1);
        } catch (Exception $e) {
            return 100;
        }
    }
}
