<?php
/**
 * Get all pending songs for the current user (for global polling)
 */
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'songs' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, title, created_at FROM songs WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'songs' => $songs]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'songs' => []]);
}
?>
