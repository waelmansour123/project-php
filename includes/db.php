<?php
$host = 'localhost';
$dbname = 'skillhub';
$username = 'root';
$password = '';

try {
    // Explicitly targeting port 3306 for WAMP/MySQL
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
