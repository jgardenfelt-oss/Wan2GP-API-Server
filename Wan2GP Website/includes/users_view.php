<?php
require_once __DIR__ . '/auth.php';
$current_user_id = $_SESSION['user_id'];

$search = $_GET['q'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.avatar_url, u.created_at, COUNT(s.id) as song_count FROM users u LEFT JOIN songs s ON u.id = s.user_id AND s.is_public = 1 AND s.status = 'completed' WHERE u.is_banned = 0 AND u.username LIKE ? GROUP BY u.id ORDER BY song_count DESC LIMIT 20");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT u.id, u.username, u.avatar_url, u.created_at, COUNT(s.id) as song_count FROM users u LEFT JOIN songs s ON u.id = s.user_id AND s.is_public = 1 AND s.status = 'completed' WHERE u.is_banned = 0 GROUP BY u.id ORDER BY song_count DESC LIMIT 30");
}
$users = $stmt->fetchAll();
?>

<div style="padding: 24px 0;">
    <div style="margin-bottom: 24px;">
        <div class="search-bar" style="max-width: 400px;">
            <i class="fa-solid fa-magnifying-glass" style="color: var(--text-secondary);"></i>
            <input type="text" id="user-search" data-t-placeholder="search_users" placeholder="<?php echo t('search_users'); ?>" value="<?php echo htmlspecialchars($search); ?>" onkeyup="if(event.key==='Enter') searchUsers()">
        </div>
    </div>

    <?php if (empty($users)): ?>
        <div style="text-align: center; padding: 80px 0; color: var(--text-secondary);">
            <i class="fa-solid fa-users" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.2;"></i>
            <p data-t="<?php echo $search ? 'no_users_found' : 'no_users'; ?>"><?php echo $search ? t('no_users_found') . ' "' . htmlspecialchars($search) . '"' : t('no_users'); ?></p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($users as $user): ?>
                <div class="user-card" onclick="loadPage('user_profile&id=<?php echo $user['id']; ?>')" style="background: var(--bg-secondary); padding: 20px; border-radius: 20px; border: 1px solid var(--border-color); cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0; overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($user['avatar_url'] ?: 'assets/images/default-avatar.jpg'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;"><?php echo htmlspecialchars($user['username']); ?></div>
                        <div style="font-size: 13px; color: var(--text-secondary);"><?php echo $user['song_count']; ?> <span data-t="shared_songs"><?php echo t('shared_songs'); ?></span></div>
                    </div>
                    <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 12px;"></i>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function searchUsers() {
    const q = document.getElementById('user-search').value.trim();
    loadPage('users' + (q ? '&q=' + encodeURIComponent(q) : ''));
}
</script>
