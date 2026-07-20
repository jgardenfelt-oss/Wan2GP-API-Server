<?php
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$song_id = $data['song_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$song_id) {
    echo json_encode(['error' => 'Song ID required']);
    exit;
}

try {
    // Check ownership
    $stmt = $pdo->prepare("SELECT is_public FROM songs WHERE id = ? AND user_id = ?");
    $stmt->execute([$song_id, $user_id]);
    $song = $stmt->fetch();

    if (!$song) {
        echo json_encode(['error' => 'Song not found or access denied']);
        exit;
    }

    $new_status = $song['is_public'] ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE songs SET is_public = ? WHERE id = ?");
    $stmt->execute([$new_status, $song_id]);

    echo json_encode(['success' => true, 'is_public' => $new_status]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
