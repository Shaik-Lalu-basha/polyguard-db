<?php

try {
    // Get database URL from Render environment
    $databaseUrl = getenv('DATABASE_URL');

    if (!$databaseUrl) {
        die("DATABASE_URL not set");
    }

    // Parse the URL
    $db = parse_url($databaseUrl);

    $pdo = new PDO(
        "pgsql:host=" . $db["host"] . 
        ";port=" . $db["port"] . 
        ";dbname=" . ltrim($db["path"], "/"),
        $db["user"],
        $db["pass"]
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
