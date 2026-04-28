<?php
require_once __DIR__ . '/config.php';

$host = 'localhost';
$dbname = 'english_learning_app';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ]);
} catch (PDOException $exception) {
    $pdo = null;
    $database_error = 'Không thể kết nối database. Vui lòng kiểm tra MySQL, tên database và thông tin trong config/database.php.';
}
