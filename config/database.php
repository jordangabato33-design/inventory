<?php
// config/database.php — MySQL connection using .env credentials
require_once __DIR__ . '/env.php';

function getDB(): mysqli {
    static $conn = null;
    if ($conn !== null) return $conn;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(
            $_ENV['DB_HOST'] ?? 'casestudy',
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASS'] ?? '',
            $_ENV['DB_NAME'] ?? 'inventory_db',
            (int)($_ENV['DB_PORT'] ?? 3306)
        );
        $conn->set_charset('utf8mb4');
        // Philippine timezone (UTC+8)
        date_default_timezone_set('Asia/Manila');
        $conn->query("SET time_zone = '+08:00'");
    } catch (mysqli_sql_exception $e) {
        die('<div style="font-family:sans-serif;padding:30px;background:#1a0a0a;color:#ef4444;border-radius:8px;max-width:500px;margin:60px auto;">
            <h3>Database Connection Failed</h3>
            <p>' . htmlspecialchars($e->getMessage()) . '</p>
            <p style="color:#888;font-size:13px;margin-top:10px;">
            ✅ Make sure XAMPP Apache + MySQL are running<br>
            ✅ Add <code>127.0.0.1 casestudy</code> to your hosts file<br>
            ✅ Check your .env file credentials
            </p>
        </div>');
    }
    return $conn;
}