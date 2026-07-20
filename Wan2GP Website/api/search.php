<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['error' => 'Unauthorized']); exit; }

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['songs' => [], 'users' => []]); exit; }

$search = '%' . $q . '%';

// Search songs (public + user's own)
$current_user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT s.id, s.title, s.tags, s.image_url, s.audio_url, s.status, s.is_public, u.username FROM songs s JOIN users u ON s.user_id = u.id WHERE s.status = 'completed' AND s.audio_url NOT LIKE 'task:%' AND (s.is_public = 1 OR s.user_id = ?) AND (s.title LIKE ? OR s.tags LIKE ? OR s.lyrics LIKE ?) ORDER BY s.created_at DESC LIMIT 10");
$stmt->execute([$current_user_id, $search, $search, $search]);
$songs = $stmt->fetchAll();

// Search users
$stmt = $pdo->prepare("SELECT id, username, avatar_url FROM users WHERE is_banned = 0 AND username LIKE ? ORDER BY username ASC LIMIT 10");
$stmt->execute([$search]);
$users = $stmt->fetchAll();

echo json_encode(['songs' => $songs, 'users' => $users]);
?>