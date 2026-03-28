<?php
/**
 * Simulate arrival/departure report POST to backend/api_reports.php
 * Usage: php tools/simulate_report.php
 */

$url = 'http://localhost/polyguard/backend/api_reports.php?action=submit_arrival';

$payload = [
    'officer_id' => 1,
    'duty_id' => 101,
    'latitude' => 37.4221,
    'longitude' => -122.0841,
    'timestamp' => date('c'),
    'image_base64' => base64_encode('test-image-bytes'),
    'notes' => 'Simulated arrival report from tools/simulate_report.php'
];

$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => json_encode($payload),
        'timeout' => 10
    ]
];

$context  = stream_context_create($opts);

echo "Posting simulated arrival report to: $url\n";

$result = @file_get_contents($url, false, $context);

if ($result === false) {
    $err = error_get_last();
    echo "Request failed: " . ($err['message'] ?? 'unknown error') . "\n";
} else {
    echo "Response:\n" . $result . "\n";
}

?>
