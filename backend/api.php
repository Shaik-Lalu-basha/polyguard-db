<?php
/**
 * POLYGUARD AI - REST API Endpoints
 * 
 * Provides RESTful API for:
 * - Duty Management
 * - Location Tracking
 * - Compliance Tracking
 * - Alert Management
 * - Attendance Management
 * 
 * API Key Authentication Required
 * Response Format: JSON
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';

header('Content-Type: application/json');

class PolyguardAPI {
    private $pdo;
    private $user;
    private $request_method;
    private $endpoint;
    private $params;

    public function __construct($pdo, $user = null) {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->request_method = $_SERVER['REQUEST_METHOD'];
        self::parseRequest();
    }

    private function parseRequest() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace('/polyguard/backend/api/', '', $uri);
        $this->endpoint = explode('/', $uri)[0];
        $this->params = $_REQUEST;
    }

    public function handleRequest() {
        try {
            // Check API authentication
            $api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
            if ($api_key) {
                $this->user = SecurityMiddleware::validateAPIRequest($api_key, $this->pdo);
            }

            // Rate limit check
            $identifier = $_SERVER['REMOTE_ADDR'];
            SecurityMiddleware::checkRateLimit($identifier);

            $response = [];

            switch ($this->endpoint) {
                // Duty Endpoints
                case 'duties':
                    $response = $this->handleDutyRequests();
                    break;

                // Location Tracking
                case 'tracking':
                    $response = $this->handleLocationTracking();
                    break;

                // Compliance Endpoints
                case 'compliance':
                    $response = $this->handleCompliance();
                    break;

                // Alerts
                case 'alerts':
                    $response = $this->handleAlerts();
                    break;

                // Attendance
                case 'attendance':
                    $response = $this->handleAttendance();
                    break;

                // Analytics
                case 'analytics':
                    $response = $this->handleAnalytics();
                    break;

                // Realtime Data
                case 'realtime':
                    $response = $this->handleRealtime();
                    break;

                // AI Predictions
                case 'ai':
                    $response = $this->handleAI();
                    break;

                // Live Locations
                case 'locations':
                    $response = $this->handleLocations();
                    break;

                // Create Python Scripts
                case 'create_scripts':
                    $response = $this->handleCreateScripts();
                    break;

                default:
                    http_response_code(404);
                    $response = ['error' => 'Endpoint not found'];
            }

            return $this->sendJSON($response);

        } catch (Exception $e) {
            http_response_code(400);
            return $this->sendJSON(['error' => $e->getMessage()]);
        }
    }

    private function handleDutyRequests() {
        switch ($this->request_method) {
            case 'GET':
                return $this->getDuties();

            case 'POST':
                return $this->createDuty();

            case 'PUT':
                return $this->updateDuty();

            case 'DELETE':
                return $this->deleteDuty();

            default:
                throw new Exception('Method not allowed');
        }
    }

    private function getDuties() {
        $user_id = $_GET['user_id'] ?? null;
        $status = $_GET['status'] ?? null;

        $query = "SELECT da.*, u.name, u.rank FROM duty_assignments da 
                 JOIN users u ON da.personnel_id = u.user_id WHERE 1=1";

        if ($user_id) {
            $query .= " AND da.personnel_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$user_id]);
        } else if ($status) {
            $query .= " AND da.status = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$status]);
        } else {
            $stmt = $this->pdo->query($query);
        }

        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    private function createDuty() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $required = ['personnel_id', 'location_name', 'latitude', 'longitude', 'start_time', 'end_time'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO duty_assignments 
            (personnel_id, location_name, latitude, longitude, radius, start_time, end_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['personnel_id'],
            $data['location_name'],
            (float)$data['latitude'],
            (float)$data['longitude'],
            $data['radius'] ?? 30,
            $data['start_time'],
            $data['end_time']
        ]);

        $duty_id = $this->pdo->lastInsertId();

        // Create attendance record
        $this->pdo->prepare("INSERT INTO attendance (personnel_id, duty_id) VALUES (?, ?)")
            ->execute([$data['personnel_id'], $duty_id]);

        // Create compliance record
        $this->pdo->prepare("INSERT INTO compliance (personnel_id, duty_id) VALUES (?, ?)")
            ->execute([$data['personnel_id'], $duty_id]);

        SecurityMiddleware::logSecurityEvent('DUTY_CREATED', $this->user['user_id'] ?? null, 
            ['duty_id' => $duty_id, 'personnel_id' => $data['personnel_id']], $this->pdo);

        return ['success' => true, 'message' => 'Duty created', 'duty_id' => $duty_id];
    }

    private function updateDuty() {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $duty_id = $data['duty_id'] ?? null;

        if (!$duty_id) {
            throw new Exception('duty_id required');
        }

        $updates = [];
        $params = [];

        foreach (['status', 'location_name', 'start_time', 'end_time'] as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            throw new Exception('No fields to update');
        }

        $params[] = $duty_id;
        $query = "UPDATE duty_assignments SET " . implode(', ', $updates) . " WHERE duty_id = ?";
        $this->pdo->prepare($query)->execute($params);

        return ['success' => true, 'message' => 'Duty updated'];
    }

    private function deleteDuty() {
        $duty_id = $_GET['duty_id'] ?? null;

        if (!$duty_id) {
            throw new Exception('duty_id required');
        }

        $this->pdo->prepare("DELETE FROM duty_assignments WHERE duty_id = ?")->execute([$duty_id]);

        return ['success' => true, 'message' => 'Duty deleted'];
    }

    private function handleLocationTracking() {
        if ($this->request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $stmt = $this->pdo->prepare("INSERT INTO location_tracking 
                (personnel_id, latitude, longitude, status) VALUES (?, ?, ?, ?)");
            
            $stmt->execute([
                $data['personnel_id'],
                (float)$data['latitude'],
                (float)$data['longitude'],
                $data['status'] ?? 'inside'
            ]);

            return ['success' => true, 'message' => 'Location tracked'];
        }

        // GET latest location
        $user_id = $_GET['user_id'] ?? null;
        $query = "SELECT * FROM location_tracking WHERE personnel_id = ? ORDER BY timestamp DESC LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);

        return ['success' => true, 'data' => $stmt->fetch()];
    }

    private function handleCompliance() {
        $user_id = $_GET['user_id'] ?? null;
        $duty_id = $_GET['duty_id'] ?? null;

        if (!$user_id) {
            throw new Exception('user_id required');
        }

        $query = "SELECT * FROM compliance WHERE personnel_id = ?";
        $params = [$user_id];

        if ($duty_id) {
            $query .= " AND duty_id = ?";
            $params[] = $duty_id;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    private function handleAlerts() {
        if ($this->request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $stmt = $this->pdo->prepare("INSERT INTO alerts 
                (personnel_id, alert_type, duty_id) VALUES (?, ?, ?)");
            
            $stmt->execute([
                $data['personnel_id'],
                $data['alert_type'],
                $data['duty_id'] ?? null
            ]);

            return ['success' => true, 'message' => 'Alert created'];
        }

        // GET alerts
        $limit = (int)($_GET['limit'] ?? 10);
        $stmt = $this->pdo->query("SELECT a.*, u.name FROM alerts a 
                                  JOIN users u ON a.personnel_id = u.user_id 
                                  ORDER BY a.alert_time DESC LIMIT $limit");

        return ['success' => true, 'data' => $stmt->fetchAll()];
    }

    private function handleAttendance() {
        if ($this->request_method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $action = $data['action'] ?? null;

            if ($action === 'checkin') {
                $this->pdo->prepare("UPDATE attendance SET checkin_time = NOW() 
                    WHERE personnel_id = ? AND duty_id = ?")
                    ->execute([$data['personnel_id'], $data['duty_id']]);

                return ['success' => true, 'message' => 'Checked in'];
            } else if ($action === 'checkout') {
                $this->pdo->prepare("UPDATE attendance SET checkout_time = NOW(), 
                    total_seconds = TIMESTAMPDIFF(SECOND, checkin_time, NOW()) 
                    WHERE personnel_id = ? AND duty_id = ?")
                    ->execute([$data['personnel_id'], $data['duty_id']]);

                return ['success' => true, 'message' => 'Checked out'];
            }
        }

        return ['success' => false, 'message' => 'Invalid action'];
    }

    private function handleAnalytics() {
        $metric = $_GET['metric'] ?? 'overview';

        switch ($metric) {
            case 'compliance_rate':
                $stmt = $this->pdo->query("SELECT AVG(compliance_score) as avg_compliance FROM compliance");
                $data = $stmt->fetch();
                break;

            case 'duty_completion':
                $stmt = $this->pdo->query("SELECT 
                    COUNT(CASE WHEN status='completed' THEN 1 END) as completed,
                    COUNT(*) as total FROM duty_assignments");
                $data = $stmt->fetch();
                break;

            case 'violations':
                $stmt = $this->pdo->query("SELECT alert_type, COUNT(*) as count 
                    FROM alerts WHERE alert_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY alert_type");
                $data = $stmt->fetchAll();
                break;

            default:
                $data = ['personnel' => 0, 'duties' => 0, 'alerts' => 0];
        }

        return ['success' => true, 'data' => $data];
    }

    private function handleRealtime() {
        // Get current active duties
        $dutiesStmt = $this->pdo->query("SELECT COUNT(*) as active_duties FROM duty_assignments WHERE status='active'");
        $duties = $dutiesStmt->fetch()['active_duties'];

        // Get today's alerts
        $alertsStmt = $this->pdo->query("SELECT COUNT(*) as today_alerts FROM alerts WHERE DATE(alert_time) = CURDATE()");
        $alerts = $alertsStmt->fetch()['today_alerts'];

        // Get current online personnel (those with recent location updates)
        $onlineStmt = $this->pdo->query("SELECT COUNT(DISTINCT personnel_id) as online FROM location_tracking 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        $online = $onlineStmt->fetch()['online'];

        // Get compliance average
        $compStmt = $this->pdo->query("SELECT AVG(compliance_score) as avg_compliance FROM compliance");
        $compliance = round($compStmt->fetch()['avg_compliance'] ?? 0, 1);

        return [
            'success' => true,
            'data' => [
                'active_duties' => (int)$duties,
                'today_alerts' => (int)$alerts,
                'online_personnel' => (int)$online,
                'avg_compliance' => $compliance,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    }

    private function handleAI() {
        require_once __DIR__ . '/python_integration.php';
        $ai = new PythonIntegration();

        $type = $_GET['type'] ?? 'compliance';

        switch ($type) {
            case 'compliance':
                $result = $ai->analyzeCompliancePatterns($this->pdo);
                break;
            case 'prediction':
                $result = $ai->predictViolations($this->pdo);
                break;
            case 'anomaly':
                $result = $ai->detectAnomalies($this->pdo);
                break;
            default:
                $result = ['error' => 'Invalid AI type'];
        }

        return ['success' => true, 'data' => $result];
    }

    private function handleLocations() {
        // Get all current locations of police officers with recent updates
        $stmt = $this->pdo->prepare("
            SELECT 
                lt.id, lt.latitude, lt.longitude, lt.status, lt.timestamp,
                u.user_id, u.name, u.rank, u.mobile,
                d.duty_id, d.location_name, d.start_time, d.end_time
            FROM location_tracking lt
            JOIN users u ON lt.personnel_id = u.user_id
            LEFT JOIN duty_assignments d ON d.personnel_id = u.user_id AND d.status = 'active'
            WHERE u.role = 'police' 
            AND lt.timestamp >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ORDER BY lt.timestamp DESC
        ");
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by user_id to get latest location per officer
        $officerLocations = [];
        foreach ($locations as $loc) {
            $userId = $loc['user_id'];
            if (!isset($officerLocations[$userId]) || 
                strtotime($loc['timestamp']) > strtotime($officerLocations[$userId]['timestamp'])) {
                $officerLocations[$userId] = [
                    'user_id' => $loc['user_id'],
                    'name' => $loc['name'],
                    'rank' => $loc['rank'],
                    'mobile' => $loc['mobile'],
                    'latitude' => (float)$loc['latitude'],
                    'longitude' => (float)$loc['longitude'],
                    'status' => $loc['status'],
                    'timestamp' => $loc['timestamp'],
                    'duty' => $loc['duty_id'] ? [
                        'id' => $loc['duty_id'],
                        'location_name' => $loc['location_name'],
                        'start_time' => $loc['start_time'],
                        'end_time' => $loc['end_time']
                    ] : null
                ];
            }
        }

        return [
            'success' => true,
            'data' => array_values($officerLocations),
            'count' => count($officerLocations),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function handleCreateScripts() {
        require_once __DIR__ . '/python_integration.php';
        $ai = new PythonIntegration();
        $result = $ai->createPythonScripts();
        return ['success' => true, 'message' => 'Python scripts created', 'result' => $result];
    }

    private function sendJSON($data) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// Initialize and handle API request only if accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    initSecurityTables($pdo);
    $api = new PolyguardAPI($pdo, $_SESSION['user'] ?? null);
    $api->handleRequest();
}

?>
