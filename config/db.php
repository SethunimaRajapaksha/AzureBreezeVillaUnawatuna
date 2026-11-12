<?php
// config/db.php - PDO connection (XAMPP defaults)
// If you moved MySQL to another port (e.g. 3307), change $DB_PORT.
$DB_HOST = '127.0.0.1';
$DB_PORT = '3306';
$DB_NAME = 'azure_breeze_villa';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions on SQL errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Check that MySQL is running in XAMPP and that db_structure.sql has been imported.');
}
