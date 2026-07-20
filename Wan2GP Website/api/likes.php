<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['error' => 'Unauthorized']); exit; }

$user_id = $_SESSION['user_id'];
$song_id = intval($_GET['song_id'] ?? $_GET['id'] ?? $_POST['song_id'] ?? 0);
$action = $_GET['action'] ?? $_POST['action'] ?? 'toggle';

if (!$song_id && $action !== 'delete_comment') { echo json_encode(['error' => 'Song ID required']); exit; }

try {
    if ($action === 'toggle') {
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND song_id = ?");
        $stmt->execute([$user_id, $song_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND song_id = ?");
            $stmt->execute([$user_id, $song_id]);
            $liked = false;
        } else {
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, song_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $song_id]);
            $liked = true;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE song_id = ?");
        $stmt->execute([$song_id]);
        $count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);

    } elseif ($action === 'status') {
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND song_id = ?");
        $stmt->execute([$user_id, $song_id]);
        $liked = (bool)$stmt->fetch();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE song_id = ?");
        $stmt->execute([$song_id]);
        $count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);

    } elseif ($action === 'comment') {
        $comment = trim($_POST['comment'] ?? '');
        if (empty($comment)) { echo json_encode(['error' => 'Comment cannot be empty']); exit; }
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

        if ($parent_id) {
            $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND song_id = ?");
            $stmt->execute([$parent_id, $song_id]);
            if (!$stmt->fetch()) { echo json_encode(['error' => 'Parent comment not found']); exit; }
        }

        $stmt = $pdo->prepare("INSERT INTO comments (user_id, song_id, parent_id, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $song_id, $parent_id, $comment]);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE song_id = ?");
        $stmt->execute([$song_id]);
        $count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'count' => $count]);

    } elseif ($action === 'delete_comment') {
        $comment_id = intval($_POST['comment_id'] ?? $_GET['comment_id'] ?? 0);
        if (!$comment_id) { echo json_encode(['error' => 'Comment ID required']); exit; }

        $stmt = $pdo->prepare("SELECT c.*, s.user_id AS owner_id FROM comments c JOIN songs s ON c.song_id = s.id WHERE c.id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch();
        if (!$comment) { echo json_encode(['error' => 'Comment not found']); exit; }

        if ($comment['user_id'] != $user_id && $comment['owner_id'] != $user_id) {
            echo json_encode(['error' => 'Not authorized']); exit;
        }

        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? OR parent_id = ?");
        $stmt->execute([$comment_id, $comment_id]);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE song_id = ?");
        $stmt->execute([$comment['song_id']]);
        $count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'count' => $count]);

    } elseif ($action === 'get_comments') {
        $stmt = $pdo->prepare("SELECT s.user_id AS owner_id FROM songs s WHERE s.id = ?");
        $stmt->execute([$song_id]);
        $song = $stmt->fetch();
        $owner_id = $song ? $song['owner_id'] : 0;

        $stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar_url FROM comments c JOIN users u ON c.user_id = u.id WHERE c.song_id = ? AND c.parent_id IS NULL ORDER BY c.created_at DESC LIMIT 50");
        $stmt->execute([$song_id]);
        $comments = $stmt->fetchAll();

        foreach ($comments as &$c) {
            $stmt = $pdo->prepare("SELECT c.*, u.username, u.avatar_url FROM comments c JOIN users u ON c.user_id = u.id WHERE c.parent_id = ? ORDER BY c.created_at ASC");
            $stmt->execute([$c['id']]);
            $c['replies'] = $stmt->fetchAll();
        }
        unset($c);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE song_id = ?");
        $stmt->execute([$song_id]);
        $count = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'comments' => $comments, 'count' => $count, 'current_user_id' => $user_id, 'owner_id' => $owner_id]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
