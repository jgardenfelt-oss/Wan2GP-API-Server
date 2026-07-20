<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['error' => 'Unauthorized']); exit; }

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$friend_id = intval($_POST['friend_id'] ?? $_GET['friend_id'] ?? 0);

if (!$action) { echo json_encode(['error' => 'No action specified']); exit; }

try {
    switch ($action) {
        case 'send':
            if (!$friend_id) { echo json_encode(['error' => 'Invalid user']); exit; }
            if ($friend_id === $user_id) { echo json_encode(['error' => 'Cannot friend yourself']); exit; }

            // Check if already friends or request pending
            $stmt = $pdo->prepare("SELECT id, status FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($existing['status'] === 'accepted') {
                    echo json_encode(['error' => 'Already friends']);
                } else {
                    echo json_encode(['error' => 'Friend request already pending']);
                }
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$user_id, $friend_id]);
            echo json_encode(['success' => true, 'message' => 'Friend request sent']);
            break;

        case 'accept':
            if (!$friend_id) { echo json_encode(['error' => 'Invalid user']); exit; }
            $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
            $stmt->execute([$friend_id, $user_id]);
            if ($stmt->rowCount()) {
                echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
            } else {
                echo json_encode(['error' => 'No pending request found']);
            }
            break;

        case 'reject':
        case 'remove':
            if (!$friend_id) { echo json_encode(['error' => 'Invalid user']); exit; }
            $stmt = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
            echo json_encode(['success' => true, 'message' => 'Friend removed']);
            break;

        case 'status':
            if (!$friend_id) { echo json_encode(['status' => 'none']); exit; }
            $stmt = $pdo->prepare("SELECT status, user_id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
            $rel = $stmt->fetch();
            if (!$rel) {
                echo json_encode(['status' => 'none']);
            } elseif ($rel['status'] === 'accepted') {
                echo json_encode(['status' => 'friends']);
            } elseif ($rel['user_id'] == $user_id) {
                echo json_encode(['status' => 'sent']);
            } else {
                echo json_encode(['status' => 'received']);
            }
            break;

        case 'list':
            $stmt = $pdo->prepare("SELECT u.id, u.username, u.avatar_url, f.status, f.user_id as requester_id, f.created_at FROM friends f JOIN users u ON (u.id = CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END) WHERE (f.user_id = ? OR f.friend_id = ?) ORDER BY f.created_at DESC");
            $stmt->execute([$user_id, $user_id, $user_id]);
            $friends = $stmt->fetchAll();
            echo json_encode(['friends' => $friends]);
            break;

        case 'count':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE ((user_id = ? OR friend_id = ?) AND status = 'accepted')");
            $stmt->execute([$user_id, $user_id]);
            echo json_encode(['count' => $stmt->fetchColumn()]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error']);
}
?>