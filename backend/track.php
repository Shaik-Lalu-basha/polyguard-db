<?php
require_once __DIR__ . '/db.php';

function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $personnel_id = (int)($_POST['personnel_id'] ?? 0);
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $duty_id = (int)($_POST['duty_id'] ?? 0);

    if (!$personnel_id || !$duty_id) {
        echo json_encode(['success'=>false,'message'=>'missing params']);
        exit;
    }

    $duty = $pdo->prepare('SELECT latitude,longitude,radius FROM duty_assignments WHERE duty_id=? AND personnel_id=?');
    $duty->execute([$duty_id,$personnel_id]);
    $d = $duty->fetch();
    if (!$d) {
        echo json_encode(['success'=>false,'message'=>'duty not found']);
        exit;
    }

    $dist = haversine($d['latitude'], $d['longitude'], $latitude, $longitude);
    $status = $dist <= $d['radius'] ? 'inside' : 'outside';

    $stmt = $pdo->prepare('INSERT INTO location_tracking (personnel_id,latitude,longitude,status) VALUES(?,?,?,?)');
    $stmt->execute([$personnel_id,$latitude,$longitude,$status]);

    if ($status === 'outside') {
        $pdo->prepare('INSERT INTO alerts (personnel_id,alert_type,duty_id) VALUES (?,"exit",?)')->execute([$personnel_id,$duty_id]);
        $pdo->prepare('UPDATE compliance SET violation_count=violation_count+1 WHERE personnel_id=? AND duty_id=?')->execute([$personnel_id,$duty_id]);
    }

    $compStmt = $pdo->prepare('SELECT violation_count, compliance_score FROM compliance WHERE personnel_id=? AND duty_id=?');
    $compStmt->execute([$personnel_id,$duty_id]);
    $comp = $compStmt->fetch();

    $violations = $comp ? (int)$comp['violation_count'] : 0;
    $compliance_score = max(0, 100 - ($violations * 10));
    $pdo->prepare('UPDATE compliance SET compliance_score=?, updated_at=NOW() WHERE personnel_id=? AND duty_id=?')->execute([$compliance_score,$personnel_id,$duty_id]);

    echo json_encode(['success'=>true, 'status'=>$status, 'distance'=>$dist, 'compliance_score'=>$compliance_score]);
    exit;
}

http_response_code(405);
echo json_encode(['success'=>false,'message'=>'Method not allowed']);
