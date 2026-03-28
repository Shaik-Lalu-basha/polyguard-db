<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /polyguard/login.php');
        exit;
    }
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['user']['role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied';
        exit;
    }
}

function calcCompliance(int $dutyId, int $personnelId, PDO $pdo): int {
    $score = 0;
    $attendance = $pdo->prepare('SELECT checkin_time, checkout_time FROM attendance WHERE duty_id=? AND personnel_id=?');
    $attendance->execute([$dutyId, $personnelId]);
    $att = $attendance->fetch();

    if ($att && $att['checkin_time']) $score += 20;
    if ($att && $att['checkout_time']) $score += 20;

    $violations = $pdo->prepare('SELECT violation_count FROM compliance WHERE duty_id=? AND personnel_id=?');
    $violations->execute([$dutyId, $personnelId]);
    $v = $violations->fetch();
    $viol = $v ? (int)$v['violation_count'] : 0;

    $score += 40;
    $score += max(0, 20 - ($viol * 5));
    return min(100, max(0, $score));
}
