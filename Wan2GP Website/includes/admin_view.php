<?php
require_once __DIR__ . '/auth.php';

$user = get_current_user_data();
if (!$user || !$user['is_admin']) {
    echo '<div style="padding: 24px;">' . t('unauthorized') . '</div>';
    exit;
}

$siteName = get_site_setting('site_name', 'Wan2GP');
$siteLogo = get_site_setting('site_logo', '');

$stmt = $pdo->query("SELECT id, username, email, credits, is_admin, is_banned FROM users ORDER BY username ASC");
$users = $stmt->fetchAll();

$stmt = $pdo->query("SELECT s.*, u.username FROM songs s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC");
$all_songs = $stmt->fetchAll();

$rules = get_site_setting('site_rules', '');

$stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'active_skin'");
$currentSkin = $stmt->fetchColumn() ?: 'default';

$googleApiKey = get_site_setting('google_api_key', '');
$apiServerUrl = get_site_setting('api_server_url', 'http://127.0.0.1:8001');
$apiServerUser = get_site_setting('api_server_user', '');
$apiServerPass = get_site_setting('api_server_pass', '');

$skins = [];
if (is_dir(__DIR__ . '/../skins')) {
    foreach (scandir(__DIR__ . '/../skins') as $dir) {
        if ($dir === '.' || $dir === '..') continue;
        if (is_dir(__DIR__ . '/../skins/' . $dir) && file_exists(__DIR__ . '/../skins/' . $dir . '/skin.css')) {
            $skins[] = $dir;
        }
    }
}
?>
<div class="admin-container" style="padding: 24px 0;">

    <!-- Site Branding -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="site_branding" style="font-size: 18px; margin-bottom: 16px;"><i class="fa-solid fa-palette" style="margin-right: 8px; color: var(--accent-primary);"></i> <?php echo t('site_branding'); ?></h2>
        <p data-t="branding_desc" style="color: var(--text-secondary); font-size: 13px; margin-bottom: 16px;"><?php echo t('branding_desc'); ?></p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label data-t="site_name" style="display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;"><?php echo t('site_name'); ?></label>
                <input type="text" id="admin-site-name" value="<?php echo htmlspecialchars($siteName); ?>" placeholder="Suno" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; outline: none;">
            </div>
            <div>
                <label data-t="logo_url" style="display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;"><?php echo t('logo_url'); ?></label>
                <input data-t-placeholder="logo_leave_empty" type="text" id="admin-site-logo" value="<?php echo htmlspecialchars($siteLogo); ?>" placeholder="<?php echo t('logo_leave_empty'); ?>" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; outline: none;">
            </div>
        </div>
        <div style="margin-top: 16px;">
            <label data-t="logo_preview" style="display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;"><?php echo t('logo_preview'); ?></label>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 900; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); color: white; overflow: hidden;" id="admin-logo-preview">
                    <?php if ($siteLogo): ?>
                        <img src="<?php echo htmlspecialchars($siteLogo); ?>" style="width: 100%; height: 100%; object-fit: cover;" id="admin-logo-preview-img">
                    <?php else: ?>
                        <i class="fa-solid fa-music" id="admin-logo-preview-icon"></i>
                    <?php endif; ?>
                </div>
                <span style="font-size: 18px; font-weight: 900;" id="admin-name-preview"><?php echo htmlspecialchars($siteName); ?></span>
            </div>
        </div>
        <button data-t="save_branding" class="btn-primary" onclick="updateSiteBranding()" style="margin-top: 16px;"><?php echo t('save_branding'); ?></button>
    </section>

    <!-- Site Rules Management -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="site_rules_terms" style="font-size: 18px; margin-bottom: 16px;"><?php echo t('site_rules_terms'); ?></h2>
        <textarea id="admin-rules" style="width: 100%; height: 150px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; color: white; margin-bottom: 16px; font-family: var(--font-main);"><?php echo htmlspecialchars($rules); ?></textarea>
        <button data-t="update_rules" class="btn-primary" onclick="updateRules()"><?php echo t('update_rules'); ?></button>
    </section>

    <!-- Skin Management -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="site_skin" style="font-size: 18px; margin-bottom: 16px;"><?php echo t('site_skin'); ?></h2>
        <p data-t="skin_desc" style="color: var(--text-secondary); font-size: 13px; margin-bottom: 16px;"><?php echo t('skin_desc'); ?></p>
        <div id="skin-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;">
            <?php foreach ($skins as $skin): ?>
                <?php
                    $skinData = file_get_contents(__DIR__ . '/../skins/' . $skin . '/skin.css');
                    preg_match('/--accent-primary:\s*([^;]+)/', $skinData, $m1);
                    preg_match('/--accent-secondary:\s*([^;]+)/', $skinData, $m2);
                    preg_match('/--bg-main:\s*([^;]+)/', $skinData, $m3);
                    $accent1 = $m1[1] ?? '#ec4899';
                    $accent2 = $m2[1] ?? '#f97316';
                    $bgMain = $m3[1] ?? '#0a0a0a';
                ?>
                <div class="skin-card" onclick="applySkin('<?php echo $skin; ?>')" style="background: <?php echo $bgMain; ?>; border: 2px solid <?php echo $currentSkin === $skin ? $accent1 : 'transparent'; ?>; border-radius: 16px; padding: 16px; cursor: pointer; transition: all 0.2s; text-align: center;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <div style="display: flex; gap: 6px; justify-content: center; margin-bottom: 10px;">
                        <div style="width: 24px; height: 24px; border-radius: 50%; background: <?php echo $accent1; ?>;"></div>
                        <div style="width: 24px; height: 24px; border-radius: 50%; background: <?php echo $accent2; ?>;"></div>
                    </div>
                    <h4 style="font-size: 13px; text-transform: capitalize; color: <?php echo $accent1; ?>;"><?php echo $skin; ?></h4>
                    <?php if ($currentSkin === $skin): ?>
                        <span data-t="active" style="font-size: 10px; color: var(--text-secondary); display: block; margin-top: 4px;"><?php echo t('active'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Language -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="language" style="font-size: 18px; margin-bottom: 16px;"><i class="fa-solid fa-globe" style="color: var(--accent-primary); margin-right: 8px;"></i><?php echo t('language'); ?></h2>
        <p data-t="language_desc" style="color: var(--text-secondary); font-size: 13px; margin-bottom: 16px;"><?php echo t('language_desc'); ?></p>
        <div style="display: flex; gap: 12px; align-items: center;">
            <select id="admin-language" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; outline: none;">
                <option value="en" <?php echo get_current_language() === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="da" <?php echo get_current_language() === 'da' ? 'selected' : ''; ?>>Dansk</option>
            </select>
            <button data-t="save_language" class="btn-primary" onclick="updateLanguage()"><?php echo t('save_language'); ?></button>
        </div>
    </section>

    <!-- Google API Key -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="google_api_key" style="font-size: 18px; margin-bottom: 16px;"><?php echo t('google_api_key'); ?></h2>
        <p data-t="google_api_desc" style="color: var(--text-secondary); font-size: 13px; margin-bottom: 16px;"><?php echo t('google_api_desc'); ?></p>
        <div style="display: flex; gap: 12px; align-items: center;">
            <input type="text" id="admin-google-api-key" value="<?php echo htmlspecialchars($googleApiKey); ?>" placeholder="Enter Google API key..." style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; outline: none;">
            <button data-t="save_key" class="btn-primary" onclick="updateGoogleApiKey()"><?php echo t('save_key'); ?></button>
        </div>
    </section>

    <!-- API Server URL -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="api_server" style="font-size: 18px; margin-bottom: 16px;"><?php echo t('api_server'); ?></h2>
        <p data-t="api_server_desc" style="color: var(--text-secondary); font-size: 13px; margin-bottom: 16px;"><?php echo t('api_server_desc'); ?></p>
        <div style="display: flex; gap: 12px; align-items: center;">
            <input type="text" id="admin-api-server-url" value="<?php echo htmlspecialchars($apiServerUrl); ?>" placeholder="http://127.0.0.1:8001" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; outline: none;">
            <button data-t="save_url" class="btn-primary" onclick="updateApiServerUrl()"><?php echo t('save_url'); ?></button>
        </div>
        <div style="display: flex; gap: 12px; align-items: center; margin-top: 12px;">
            <input type="text" id="admin-api-server-user" value="<?php echo htmlspecialchars($apiServerUser); ?>" placeholder="Username" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; outline: none;">
            <input type="text" id="admin-api-server-pass" value="<?php echo htmlspecialchars($apiServerPass); ?>" placeholder="Password" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; outline: none;">
            <button data-t="save_auth" class="btn-primary" onclick="updateApiServerAuth()"><?php echo t('save_auth'); ?></button>
        </div>
    </section>

    <!-- User Management -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="user_management" style="font-size: 18px; margin-bottom: 16px;"><?php echo t('user_management'); ?></h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="color: var(--text-secondary); font-size: 14px; border-bottom: 1px solid var(--border-color);">
                        <th data-t="col_user" style="padding: 12px;"><?php echo t('col_user'); ?></th>
                        <th data-t="col_email" style="padding: 12px;"><?php echo t('col_email'); ?></th>
                        <th data-t="col_credits" style="padding: 12px;"><?php echo t('col_credits'); ?></th>
                        <th data-t="col_status" style="padding: 12px;"><?php echo t('col_status'); ?></th>
                        <th data-t="col_action" style="padding: 12px;"><?php echo t('col_action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 14px;">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($u['username']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="padding: 12px;"><?php echo $u['credits']; ?></td>
                        <td style="padding: 12px;">
                                <span data-t="<?php echo $u['is_admin'] ? 'admin_badge_short' : 'user_badge'; ?>" style="padding: 4px 8px; border-radius: 6px; font-size: 11px; background: <?php echo $u['is_admin'] ? 'rgba(236, 72, 153, 0.2)' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $u['is_admin'] ? 'var(--accent-primary)' : 'var(--text-secondary)'; ?>;">
                                    <?php echo $u['is_admin'] ? t('admin_badge_short') : t('user_badge'); ?>
                                </span>
                        </td>
                        <td style="padding: 12px; display: flex; gap: 8px; align-items: center;">
                            <input data-t-placeholder="qty" type="number" id="credits-<?php echo $u['id']; ?>" placeholder="<?php echo t('qty'); ?>" style="width: 60px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 8px; color: white;">
                            <button data-t="add" class="btn-primary" style="padding: 4px 10px; font-size: 11px;" onclick="addCredits(<?php echo $u['id']; ?>)"><?php echo t('add'); ?></button>
                            <button data-t="<?php echo $u['is_admin'] ? 'revoke_admin' : 'make_admin'; ?>" class="btn-primary" style="padding: 4px 10px; font-size: 11px;" onclick="toggleAdmin(<?php echo $u['id']; ?>)">
                                <?php echo $u['is_admin'] ? t('revoke_admin') : t('make_admin'); ?>
                            </button>
                            <button data-t="<?php echo $u['is_banned'] ? 'unban' : 'ban'; ?>" style="padding: 4px 10px; font-size: 11px; background: <?php echo $u['is_banned'] ? '#10b981' : '#ef4444'; ?>; color: white; border: none; border-radius: 99px; cursor: pointer; font-family: var(--font-main);" onclick="toggleBan(<?php echo $u['id']; ?>)">
                                <?php echo $u['is_banned'] ? t('unban') : t('ban'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Song Moderation -->
    <section style="background: var(--bg-secondary); padding: 24px; border-radius: 24px; border: 1px solid var(--border-color);">
        <h2 data-t="global_library" style="font-size: 18px; margin-bottom: 16px;"><?php echo t('global_library'); ?></h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="color: var(--text-secondary); font-size: 14px; border-bottom: 1px solid var(--border-color);">
                        <th data-t="col_user" style="padding: 12px;"><?php echo t('col_user'); ?></th>
                        <th data-t="col_song_title" style="padding: 12px;"><?php echo t('col_song_title'); ?></th>
                        <th data-t="col_status" style="padding: 12px;"><?php echo t('col_status'); ?></th>
                        <th data-t="col_created" style="padding: 12px;"><?php echo t('col_created'); ?></th>
                        <th data-t="col_action" style="padding: 12px;"><?php echo t('col_action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_songs as $s): ?>
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 14px;">
                        <td style="padding: 12px;"><?php echo htmlspecialchars($s['username']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($s['title']); ?></td>
                        <td style="padding: 12px;"><span style="color: <?php echo $s['status'] === 'completed' ? '#10b981' : '#f59e0b'; ?>"><?php echo $s['status']; ?></span></td>
                        <td style="padding: 12px;"><?php echo date('Y-m-d', strtotime($s['created_at'])); ?></td>
                        <td style="padding: 12px;">
                            <button onclick="deleteSongAdmin(<?php echo $s['id']; ?>)" style="color: #f87171; background: none; border: none; cursor: pointer;"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
async function updateSiteBranding() {
    const name = document.getElementById('admin-site-name').value.trim();
    const logo = document.getElementById('admin-site-logo').value.trim();
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_site_branding', site_name: name, site_logo: logo })
    });
    const data = await res.json();
    if (data.success) {
        showToast(T.branding_updated, 'success');
    }
}

async function addCredits(userId) {
    const amount = document.getElementById('credits-' + userId).value;
    if (!amount) return;
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add_credits', user_id: userId, amount: parseInt(amount) })
    });
    const data = await res.json();
    if (data.success) { showToast(T.credits_added, 'success'); loadPage('admin'); }
}

async function toggleAdmin(userId) {
    if (!confirm(T.confirm_toggle_admin)) return;
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle_admin', user_id: userId })
    });
    const data = await res.json();
    if (data.success) loadPage('admin');
}

async function toggleBan(userId) {
    if (!confirm(T.confirm_toggle_ban)) return;
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle_ban', user_id: userId })
    });
    const data = await res.json();
    if (data.success) loadPage('admin');
}

async function updateRules() {
    const rules = document.getElementById('admin-rules').value;
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_rules', rules: rules })
    });
    const data = await res.json();
    if (data.success) showToast(T.rules_updated, 'success');
}

async function applySkin(skin) {
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'set_skin', skin: skin })
    });
    const data = await res.json();
    if (data.success) {
        const link = document.getElementById('skin-css');
        if (link) link.href = 'skins/' + skin + '/skin.css';
        loadPage('admin');
    }
}

async function deleteSongAdmin(songId) {
    if (!confirm(T.confirm_delete_song)) return;
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_song_admin', song_id: songId })
    });
    const data = await res.json();
    if (data.success) loadPage('admin');
}

async function updateGoogleApiKey() {
    const key = document.getElementById('admin-google-api-key').value.trim();
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_google_api_key', key: key })
    });
    const data = await res.json();
    if (data.success) showToast(T.google_api_saved, 'success');
}

async function updateApiServerUrl() {
    const url = document.getElementById('admin-api-server-url').value.trim();
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_api_server_url', url: url })
    });
    const data = await res.json();
    if (data.success) showToast(T.api_url_saved, 'success');
}

async function updateApiServerAuth() {
    const user = document.getElementById('admin-api-server-user').value.trim();
    const pass = document.getElementById('admin-api-server-pass').value;
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_api_server_auth', user: user, pass: pass })
    });
    const data = await res.json();
    if (data.success) showToast(T.api_auth_saved, 'success');
}

async function updateLanguage() {
    const lang = document.getElementById('admin-language').value;
    const res = await fetch('api/admin_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update_language', language: lang })
    });
    const data = await res.json();
    if (data.success) {
        if (typeof applyTranslations === 'function') {
            await applyTranslations(lang);
        }
        showToast('Language updated!', 'success');
    }
}
</script>
