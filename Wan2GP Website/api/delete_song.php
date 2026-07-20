<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$song_id = $_GET['id'] ?? null;
if (!$song_id) {
    echo json_encode(['error' => 'Song ID required']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Ensure user owns the song
    $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ? AND user_id = ?");
    $stmt->execute([$song_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Song not found or unauthorized']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
