<?php
// db.php - Database connection (environment-aware)

$is_production = in_array($_SERVER['HTTP_HOST'] ?? '', [
    'web.farmbridgeai.rw',
    'www.farmbridgeai.rw',
]);

if ($is_production && file_exists(__DIR__ . '/db_production.php')) {
    $config = include 'db_production.php';
    $host = $config['host'];
    $user = $config['username'];
    $pass = $config['password'];
    $dbname = $config['database'];
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $dbname = getenv('DB_NAME') ?: 'farmbridge';
}

$conn = @new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_errno) {
    die('Database connection failed: (' . $conn->connect_errno . ') ' . $conn->connect_error);
}
?>
