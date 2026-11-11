<?php
// Command-line installer:  php install.php   (creates tables + simulated starting data for the configured DB_DRIVER)
require_once __DIR__ . '/config/database.php';
if (PHP_SAPI !== 'cli') exit('Run from the command line.');
if (is_sqlite()) {
    $sql = file_get_contents(__DIR__ . '/sql/schema_sqlite.sql');
    $pdo = db();
} else {
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) { $stmt = trim(preg_replace('/^--.*$/m', '', $stmt)); if ($stmt !== '') $pdo->exec($stmt); }
echo "Database ready.\nLogins: admin/admin123  john/1234  mary/2345  peter/3456\n";
