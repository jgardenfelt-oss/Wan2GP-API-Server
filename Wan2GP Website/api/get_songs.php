<?php
/**
 * Get all songs for the current user
 */
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM songs WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'songs' => $songs]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>