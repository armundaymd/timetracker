<?php
// config.php
$host = 'localhost';
$db   = 'time_tracker';
$user = 'root';
$pass = ''; // <--- PUT YOUR DATABASE PASSWORD HERE

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
session_start();
?>