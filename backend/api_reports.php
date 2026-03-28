<?php
/**
 * POLYGUARD AI - Report API
 * Handles duty reports, GPS photos, and location tracking
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/duty_report_manager.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$manager = new DutyReportManager($pdo, $user_id);
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Increase limits for image uploads (best-effort, server config may still limit)
@ini_set('memory_limit', '256M');
@set_time_limit(120);

// Simple request logging for debugging uploads
function _report_log($msg) {
    $f = __DIR__ . '/../logs/api_reports.log';
    @mkdir(dirname($f), 0755, true);
    file_put_contents($f, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

_report_log("Incoming request: user={$user_id} action=" . ($action ?? 'none') . " method=" . $_SERVER['REQUEST_METHOD']);

// If request is JSON, parse input body
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) {
        // Merge into $_POST for compatibility
        foreach ($data as $k => $v) {
            $_POST[$k] = $v;
        }
        $action = $data['action'] ?? $action;
    }
}

try {
    switch ($action) {
        case 'submit_arrival':
            $duty_id = (int)($_POST['duty_id'] ?? 0);
            $latitude = (float)($_POST['latitude'] ?? 0);
            $longitude = (float)($_POST['longitude'] ?? 0);
            $image_base64 = $_POST['image'] ?? null;

            if (!$duty_id || !$latitude || !$longitude) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }

            $result = $manager->submitArrivalReport($duty_id, $latitude, $longitude, $image_base64);
            echo json_encode($result);
            break;

        case 'submit_departure':
            $duty_id = (int)($_POST['duty_id'] ?? 0);
            $latitude = (float)($_POST['latitude'] ?? 0);
            $longitude = (float)($_POST['longitude'] ?? 0);
            $image_base64 = $_POST['image'] ?? null;

            if (!$duty_id || !$latitude || !$longitude) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }

            $result = $manager->submitDepartureReport($duty_id, $latitude, $longitude, $image_base64);
            echo json_encode($result);
            break;

        case 'track_location':
            $latitude = (float)($_POST['latitude'] ?? $_GET['lat'] ?? 0);
            $longitude = (float)($_POST['longitude'] ?? $_GET['lng'] ?? 0);
            $accuracy = (float)($_POST['accuracy'] ?? $_GET['accuracy'] ?? null);

            if (!$latitude || !$longitude) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing coordinates']);
                exit;
            }

            $result = $manager->trackLocation($latitude, $longitude, $accuracy);
            echo json_encode($result);
            break;

        case 'get_reports':
            $filter = $_GET['filter'] ?? 'all';
            $reports = $manager->getDutyReports($filter);
            echo json_encode(['success' => true, 'data' => $reports]);
            break;

        case 'get_summary':
            $duty_id = (int)($_GET['duty_id'] ?? 0);
            if (!$duty_id) {
                http_response_code(400);
                echo json_encode(['error' => 'duty_id required']);
                exit;
            }
            $summary = $manager->getDutySummary($duty_id);
            echo json_encode(['success' => true, 'data' => $summary]);
            break;

        case 'get_entry_exit':
            $duty_id = (int)($_GET['duty_id'] ?? 0);
            if (!$duty_id) {
                http_response_code(400);
                echo json_encode(['error' => 'duty_id required']);
                exit;
            }
            $logs = $manager->getEntryExitLogs($duty_id);
            echo json_encode(['success' => true, 'data' => $logs]);
            break;

        case 'review_report':
            // Admin/Control only
            if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['role'] !== 'control') {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }

            $report_id = (int)($_POST['report_id'] ?? 0);
            $status = $_POST['status'] ?? null;
            $comments = $_POST['comments'] ?? null;

            if (!$report_id || !in_array($status, ['approved', 'rejected'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid parameters']);
                exit;
            }

            $result = $manager->reviewReport($report_id, $status, $comments, $user_id);
            echo json_encode($result);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
