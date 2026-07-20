<?php
require_once '../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;
$avatar = $_FILES['avatar'] ?? null;

try {
    $updates = [];
    $params = [];

    // Handle Email Change
    if ($email) {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Invalid email address']);
            exit;
        }
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            echo json_encode(['error' => 'Email already in use']);
            exit;
        }
        $updates[] = "email = ?";
        $params[] = $email;
    }

    // Handle Password Change
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $params[] = $hash;
    }

    // Handle Avatar Upload
    if ($avatar && $avatar['tmp_name']) {
        $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array(strtolower($ext), $allowed)) {
            echo json_encode(['error' => 'Invalid image format']);
            exit;
        }

        $filename = "avatar_" . $user_id . "_" . time() . "." . $ext;
        $upload_dir = "../uploads/avatars/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        if (move_uploaded_file($avatar['tmp_name'], $upload_dir . $filename)) {
            $updates[] = "avatar_url = ?";
            $params[] = "uploads/avatars/" . $filename;
        } else {
            echo json_encode(['error' => 'Failed to upload image']);
            exit;
        }
    }

    if (empty($updates)) {
        echo json_encode(['success' => true, 'message' => 'No changes made']);
        exit;
    }

    $params[] = $user_id;
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Return the new avatar URL if it was updated
    $avatar_url = null;
    if (!empty($avatar) && !empty($avatar['tmp_name'])) {
        $avatar_url = "uploads/avatars/" . $filename;
    }

    echo json_encode(['success' => true, 'avatar_url' => $avatar_url]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
