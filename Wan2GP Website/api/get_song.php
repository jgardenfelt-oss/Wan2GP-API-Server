<?php
/**
 * Public/Private Song Data Fetcher
 */
require_once '../includes/auth.php';

header('Content-Type: application/json');

$song_id = $_GET['id'] ?? null;
if (!$song_id) {
    echo json_encode(['error' => 'ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT s.title, s.audio_url, s.image_url, s.lyrics, s.tags, s.status, u.username, u.id as owner_id FROM songs s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$song_id]);
    $song = $stmt->fetch();

    if ($song) {
        $user_id = $_SESSION['user_id'] ?? 0;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE song_id = ?");
        $stmt->execute([$song_id]);
        $song['like_count'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE song_id = ?");
        $stmt->execute([$song_id]);
        $song['comment_count'] = $stmt->fetchColumn();

        if ($user_id) {
            $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND song_id = ?");
            $stmt->execute([$user_id, $song_id]);
            $song['liked'] = (bool)$stmt->fetch();
        } else {
            $song['liked'] = false;
        }

        echo json_encode(['success' => true, 'song' => $song]);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
