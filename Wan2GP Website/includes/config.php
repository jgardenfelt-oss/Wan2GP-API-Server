<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'suno8');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/suno8');
define('CSRF_SECRET', 'a7c3e2f1b8d4c6a9e5f2d7b3c8a1e6f4d9b5c2a8e3f7d1b4c9a6e2f8d5b3c7a1');

$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
$is_local = in_array($host, ['localhost', '127.0.0.1', '::1']) || strpos($host, 'localhost:') === 0;
$is_ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;


if (!$is_ssl && !$is_local) {
    header('Location: https://' . $host . $_SERVER['REQUEST_URI']);
    exit;
}

define('SESSION_SECURE', $is_ssl);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
