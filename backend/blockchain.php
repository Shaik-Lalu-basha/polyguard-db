<?php
require_once __DIR__ . '/db.php';

function makeHash($data){ return hash('sha256', $data); }

function addBlockchainEntry($pdo, $entry){
    $previous = $pdo->query('SELECT data_hash FROM blockchain_logs ORDER BY block_id DESC LIMIT 1')->fetchColumn();
    $payload = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hash = makeHash($payload . ($previous ?? ''));
    $stmt = $pdo->prepare('INSERT INTO blockchain_logs (data_hash, previous_hash, entry) VALUES (?,?,?)');
    $stmt->execute([$hash, $previous ?: '', $payload]);
    return $hash;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'] ?? '';
    if (!$data) {
        echo json_encode(['success'=>false, 'message'=>'Empty data']);
        exit;
    }
    $hash = addBlockchainEntry($pdo,$data);
    echo json_encode(['success'=>true, 'hash'=>$hash]);
    exit;
}

$logs = $pdo->query('SELECT * FROM blockchain_logs ORDER BY block_id DESC LIMIT 50')->fetchAll();
header('Content-Type: application/json');
 echo json_encode(['records'=>$logs]);
