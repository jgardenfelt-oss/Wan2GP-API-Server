<?php
/**
 * Suno Clone - Secure Auth Library
 */

if (!defined('DB_HOST')) {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once 'config.php';
    } else {
        $root = str_replace('\\', '/', dirname(__DIR__));
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $baseUrl = substr($root, strlen($docRoot));
        header("Location: " . rtrim($baseUrl, '/') . "/install.php");
        exit;
    }
}

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => defined('SESSION_SECURE') ? SESSION_SECURE : false,
    'cookie_samesite' => 'Lax',
]);

// Translation system
$_translations = [];
$_current_lang = 'en';
function load_translations($lang) {
    global $_translations, $_current_lang;
    $lang = preg_replace('/[^a-z]/', '', strtolower($lang));
    $file = __DIR__ . '/../languages/' . $lang . '.php';
    if (file_exists($file)) {
        $_translations = require $file;
        $_current_lang = $lang;
    } else {
        $file = __DIR__ . '/../languages/en.php';
        $_translations = require $file;
        $_current_lang = 'en';
    }
}
function t($key, $fallback = null) {
    global $_translations;
    return $_translations[$key] ?? $fallback ?? $key;
}
function get_current_language() {
    global $_current_lang;
    return $_current_lang;
}

// Load language from DB or session
$_lang_loaded = false;
if (!empty($pdo)) {
    try {
        $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'site_language'");
        $db_lang = $stmt->fetchColumn();
        if (!empty($db_lang)) {
            load_translations($db_lang);
            $_lang_loaded = true;
        }
    } catch (Exception $e) {}
}
if (!$_lang_loaded) {
    $session_lang = $_SESSION['site_language'] ?? 'en';
    load_translations($session_lang);
}

if (!empty($pdo)) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS friends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        friend_id INT NOT NULL,
        status ENUM('pending', 'accepted') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        song_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
        UNIQUE KEY unique_like (user_id, song_id)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        song_id INT NOT NULL,
        parent_id INT DEFAULT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    try { $pdo->exec("ALTER TABLE comments ADD COLUMN parent_id INT DEFAULT NULL AFTER song_id"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE comments ADD FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE"); } catch (Exception $e) {}
}

/**
 * Regenerate session ID to prevent session fixation
 */
function secure_login($user_id) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['last_regen'] = time();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * CSRF Token Generation
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token Verification
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Register User
 */
function register_user($username, $email, $password) {
    global $pdo;
    
    // Check if this is the first user
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    $isAdmin = ($userCount == 0) ? 1 : 0;
    
    // Hash password using Argon2id if available, otherwise DEFAULT
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_admin, role) VALUES (?, ?, ?, ?, ?)");
    try {
        $role = $isAdmin ? 'admin' : 'user';
        return $stmt->execute([$username, $email, $hash, $isAdmin, $role]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Authenticate User
 */
function login_user($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        secure_login($user['id']);
        return true;
    }
    return false;
}

/**
 * Refresh Daily Credits (50 credits every 24h)
 */
function refresh_daily_credits($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT last_credit_update, credits FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        $last_update = strtotime($user['last_credit_update']);
        $now = time();
        
        // If more than 24 hours (86400 seconds) have passed
        if ($now - $last_update >= 86400) {
            $stmt = $pdo->prepare("UPDATE users SET credits = credits + 50, last_credit_update = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user_id]);
        }
    }
}

/**
 * Get User Data
 */
function get_current_user_data() {
    global $pdo;
    if (!is_logged_in()) return null;
    
    $user_id = $_SESSION['user_id'];
    refresh_daily_credits($user_id);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get User by Email
 */
function get_user_by_email($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get Site Setting
 */
function get_site_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Set Site Setting
 */
function set_site_setting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
