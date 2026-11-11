<?php
// Database + application settings. Change these for your server (XAMPP defaults shown).
// Set env DB_DRIVER=sqlite to run without MySQL (used for quick demos/tests).
date_default_timezone_set('Africa/Nairobi'); // East Africa Time

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');
define('DB_HOST',   getenv('DB_HOST')   ?: 'localhost');
define('DB_NAME',   getenv('DB_NAME')   ?: 'mobile_banking');
define('DB_USER',   getenv('DB_USER')   ?: 'root');
define('DB_PASS',   getenv('DB_PASS')   ?: '');
define('SQLITE_FILE', getenv('SQLITE_FILE') ?: __DIR__ . '/../storage/app.sqlite');

define('SESSION_TIMEOUT', (int)(getenv('SESSION_TIMEOUT') ?: 900));          // seconds of inactivity before auto logout
define('SMS_MODE', getenv('SMS_MODE') ?: 'simulator'); // 'simulator' or 'africastalking'
define('AT_USERNAME', 'sandbox');        // Africa's Talking sandbox credentials
define('AT_API_KEY',  '');
define('SMS_SENDER',  '');
define('STORAGE_DIR', __DIR__ . '/../storage');

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    if (DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . SQLITE_FILE, null, null, $opt);
        $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    } else {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
        $pdo->exec("SET time_zone = '+03:00'");
    }
    return $pdo;
}
function is_sqlite(): bool { return DB_DRIVER === 'sqlite'; }
