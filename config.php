<?php
// config.php – loads .env and establishes DB connection; logs errors instead of exposing details.

$configDir = __DIR__;
$envFile = $configDir . '/.env';

if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\"'");
            if ($name !== '') {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'time_tracker';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';

$logPath = $_ENV['LOG_PATH'] ?? $configDir . '/app_errors.log';

function log_db_error($message, $logPath) {
    $dir = dirname($logPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_writable($dir) || (file_exists($logPath) && is_writable($logPath))) {
        @file_put_contents(
            $logPath,
            date('Y-m-d H:i:s') . ' [DB] ' . $message . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    log_db_error($e->getMessage(), $logPath);
    header('HTTP/1.1 503 Service Unavailable');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Service Unavailable']);
    exit;
}
session_start();
?>
