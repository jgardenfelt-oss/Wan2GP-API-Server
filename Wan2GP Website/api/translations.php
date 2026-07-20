<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

$lang = preg_replace('/[^a-z]/', '', strtolower($_GET['lang'] ?? 'en'));
$file = __DIR__ . '/../languages/' . $lang . '.php';
if (!file_exists($file)) {
    $file = __DIR__ . '/../languages/en.php';
    $lang = 'en';
}
$translations = require $file;
echo json_encode($translations);
