<?php
if (file_exists(__DIR__ . '/includes/config.php')) {
    header('Location: login.php');
    exit;
}

$step = $_POST['step'] ?? 'config';
$error = '';
$success = '';

// Step 2: Process installation
if ($step === 'install') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';
    $base_url = trim($_POST['base_url'] ?? '/');
    $admin_user = trim($_POST['admin_user'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_pass = $_POST['admin_pass'] ?? '';

    if (empty($admin_user) || empty($admin_email) || empty($admin_pass)) {
        $error = 'Please fill in all admin fields.';
        $step = 'config';
    } elseif (strlen($admin_user) < 3) {
        $error = 'Username must be at least 3 characters.';
        $step = 'config';
    } elseif (strlen($admin_pass) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step = 'config';
    } else {
        try {
            // Test connection
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");

            // Create tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                avatar_url VARCHAR(500) DEFAULT NULL,
                credits INT DEFAULT 50,
                is_admin TINYINT(1) DEFAULT 0,
                is_banned TINYINT(1) DEFAULT 0,
                role VARCHAR(20) DEFAULT 'user',
                rules_accepted TINYINT(1) DEFAULT 0,
                last_credit_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");

            $pdo->exec("CREATE TABLE IF NOT EXISTS songs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                lyrics TEXT,
                tags TEXT,
                audio_url VARCHAR(500) DEFAULT NULL,
                image_url VARCHAR(500) DEFAULT NULL,
                status ENUM('pending','completed','failed') DEFAULT 'pending',
                is_public TINYINT(1) DEFAULT 0,
                task_id VARCHAR(255) DEFAULT NULL,
                duration INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");

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

            $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT
            ) ENGINE=InnoDB");

            // Insert default settings
            $settings = [
                ['site_name', 'Wan2GP'],
                ['site_logo', ''],
                ['api_server_url', 'http://127.0.0.1:8001'],
                ['api_server_user', ''],
                ['api_server_pass', ''],
                ['google_api_key', ''],
                ['site_rules', ''],
                ['site_language', 'en']
            ];
            $stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $s) {
                $stmt->execute($s);
            }

            // Create admin user
            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, credits, is_admin, role) VALUES (?, ?, ?, 9999, 1, 'admin')");
            $stmt->execute([$admin_user, $admin_email, $hash]);

            // Write config.php
            $base_url = rtrim($base_url, '/');
            if (empty($base_url)) $base_url = '/';
            $config_content = '<?php
define(\'DB_HOST\', ' . var_export($db_host, true) . ');
define(\'DB_NAME\', ' . var_export($db_name, true) . ');
define(\'DB_USER\', ' . var_export($db_user, true) . ');
define(\'DB_PASS\', ' . var_export($db_pass, true) . ');
define(\'BASE_URL\', ' . var_export($base_url, true) . ');
define(\'CSRF_SECRET\', \'a7c3e2f1b8d4c6a9e5f2d7b3c8a1e6f4d9b5c2a8e3f7d1b4c9a6e2f8d5b3c7a1\');

$host = $_SERVER[\'HTTP_HOST\'] ?? $_SERVER[\'SERVER_NAME\'] ?? \'\';
$is_local = in_array($host, [\'localhost\', \'127.0.0.1\', \'::1\']) || strpos($host, \'localhost:\') === 0;
$is_ssl = (!empty($_SERVER[\'HTTPS\']) && $_SERVER[\'HTTPS\'] !== \'off\') || ($_SERVER[\'SERVER_PORT\'] ?? 80) == 443;

if (!$is_ssl && !$is_local) {
    header(\'Location: https://\' . $host . $_SERVER[\'REQUEST_URI\']);
    exit;
}

define(\'SESSION_SECURE\', $is_ssl || $is_local);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
';

            file_put_contents(__DIR__ . '/includes/config.php', $config_content);

            header('Location: login.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            $step = 'config';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wan2GP - Installation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0a0a0a;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .install-container{width:520px;max-width:100%;}
        .install-logo{font-size:36px;font-weight:900;letter-spacing:6px;text-align:center;margin-bottom:12px;background:linear-gradient(135deg,#ec4899,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .install-subtitle{text-align:center;color:#a0a0a0;font-size:14px;margin-bottom:32px;}
        .install-card{background:#141414;border:1px solid #2a2a2a;border-radius:20px;padding:32px;}
        .install-card h2{font-size:18px;font-weight:700;margin-bottom:24px;display:flex;align-items:center;gap:10px;}
        .install-card h2 i{color:#ec4899;}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#a0a0a0;margin-bottom:8px;font-weight:600;}
        .form-group input{width:100%;padding:12px 16px;background:#1e1e1e;border:1px solid #2a2a2a;border-radius:10px;color:#fff;font-size:14px;outline:none;font-family:inherit;transition:border-color .2s;}
        .form-group input:focus{border-color:#ec4899;}
        .form-group input::placeholder{color:#666;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
        .divider{height:1px;background:#2a2a2a;margin:24px 0;}
        .btn-install{width:100%;padding:14px;background:linear-gradient(135deg,#ec4899,#f97316);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;margin-top:8px;}
        .btn-install:hover{opacity:.9;transform:scale(1.01);}
        .btn-install:disabled{opacity:.5;cursor:not-allowed;transform:none;}
        .error{background:rgba(239,68,68,.1);color:#f87171;padding:12px;border-radius:10px;margin-bottom:16px;font-size:13px;}
        .steps{display:flex;justify-content:center;gap:8px;margin-bottom:24px;}
        .step-dot{width:8px;height:8px;border-radius:50%;background:#333;}
        .step-dot.active{background:#ec4899;}
        .hint{font-size:11px;color:#666;margin-top:4px;}
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-logo">Wan2GP</div>
        <p class="install-subtitle">Installation Setup</p>

        <div class="install-card">
            <?php if ($error): ?>
                <div class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <h2><i class="fa-solid fa-database"></i> Database Configuration</h2>

            <form method="POST">
                <input type="hidden" name="step" value="install">

                <div class="form-row">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" placeholder="localhost">
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="Database Name">
                        <div class="hint">Will be created if it doesn't exist</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Database User</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" placeholder="root">
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass" value="" placeholder="Leave empty for no password">
                    </div>
                </div>

                <div class="form-group">
                    <label>Base URL</label>
                    <input type="text" name="base_url" value="<?= htmlspecialchars($_POST['base_url'] ?? '/') ?>" placeholder="If you use xampp so enter the folder you put it into">
                    <div class="hint">This is the folder you put the site into there if you use the xampp</div>
                </div>

                <div class="divider"></div>

                <h2><i class="fa-solid fa-user-shield"></i> Admin Account</h2>

                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>" placeholder="Admin Username" required minlength="3">
                </div>

                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" placeholder="Admin Email" required>
                </div>

                <div class="form-group">
                    <label>Admin Password</label>
                    <input type="password" name="admin_pass" value="<?= htmlspecialchars($_POST['admin_pass'] ?? '') ?>" placeholder="" required minlength="6">
                </div>

                <button type="submit" class="btn-install">
                    <i class="fa-solid fa-rocket"></i> Install & Setup
                </button>
            </form>
        </div>

        <p style="text-align:center;color:#666;font-size:12px;margin-top:16px;">Database tables and admin account will be created automatically.</p>
    </div>
</body>
</html>
