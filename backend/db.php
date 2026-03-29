<?php

try {
    $databaseUrl = getenv('DATABASE_URL');

    if (!$databaseUrl) {
        die("DATABASE_URL not set");
    }

    $db = parse_url($databaseUrl);

    $host = $db['host'] ?? 'localhost';
    $port = $db['port'] ?? 5432;   // Default PostgreSQL port
    $dbname = ltrim($db['path'], '/');
    $user = $db['user'];
    $password = $db['pass'];

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
