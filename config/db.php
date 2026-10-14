<?php
// config/db.php - Database Configuration & PDO Instance

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'training_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:20px;background:#fee2e2;border:1px solid #ef4444;color:#991b1b;border-radius:8px;">'
                . '<h3>Lỗi kết nối cơ sở dữ liệu MySQL</h3>'
                . '<p>Không thể kết nối đến cơ sở dữ liệu <code>' . DB_NAME . '</code> tại ' . DB_HOST . ':' . DB_PORT . '</p>'
                . '<p>Chi tiết: ' . htmlspecialchars($e->getMessage()) . '</p>'
                . '<p>Vui lòng đảm bảo dịch vụ MySQL trong XAMPP đang chạy.</p>'
                . '</div>');
        }
    }
    return $pdo;
}

