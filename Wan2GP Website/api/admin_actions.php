<?php
require_once '../includes/auth.php';

$user = get_current_user_data();
if (!$user || !$user['is_admin']) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'update_site_branding':
            $name = trim($data['site_name'] ?? 'Suno');
            $logo = trim($data['site_logo'] ?? '');
            if (empty($name)) $name = 'Wan2GP';
            set_site_setting('site_name', $name);
            set_site_setting('site_logo', $logo);
            echo json_encode(['success' => true]);
            break;

        case 'add_credits':
            $target_user_id = $data['user_id'];
            $amount = $data['amount'];
            $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$amount, $target_user_id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_rules':
            $rules = $data['rules'];
            set_site_setting('site_rules', $rules);
            echo json_encode(['success' => true]);
            break;

        case 'delete_song_admin':
            $song_id = $data['song_id'];
            $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
            $stmt->execute([$song_id]);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_ban':
            $target_user_id = $data['user_id'];
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 - is_banned WHERE id = ?");
            $stmt->execute([$target_user_id]);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_admin':
            $target_user_id = $data['user_id'];
            $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 - is_admin, role = IF(is_admin = 0, 'admin', 'user') WHERE id = ?");
            $stmt->execute([$target_user_id]);
            echo json_encode(['success' => true]);
            break;

        case 'set_skin':
            $skin = $data['skin'] ?? 'default';
            $skin = preg_replace('/[^a-zA-Z0-9_-]/', '', $skin);
            $skinPath = __DIR__ . '/../skins/' . $skin . '/skin.css';
            if (!file_exists($skinPath)) {
                echo json_encode(['error' => 'Skin not found']);
                break;
            }
            set_site_setting('active_skin', $skin);
            echo json_encode(['success' => true]);
            break;

        case 'update_google_api_key':
            $key = trim($data['key'] ?? '');
            set_site_setting('google_api_key', $key);
            echo json_encode(['success' => true]);
            break;

        case 'update_api_server_url':
            $url = trim($data['url'] ?? '');
            if (empty($url)) $url = 'http://127.0.0.1:8001';
            set_site_setting('api_server_url', $url);
            echo json_encode(['success' => true]);
            break;

        case 'update_api_server_auth':
            $user_val = trim($data['user'] ?? '');
            $pass = $data['pass'] ?? '';
            set_site_setting('api_server_user', $user_val);
            set_site_setting('api_server_pass', $pass);
            echo json_encode(['success' => true]);
            break;

        case 'update_language':
            $lang = preg_replace('/[^a-z]/', '', strtolower($data['language'] ?? 'en'));
            if (!file_exists(__DIR__ . '/../languages/' . $lang . '.php')) $lang = 'en';
            set_site_setting('site_language', $lang);
            $_SESSION['site_language'] = $lang;
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
