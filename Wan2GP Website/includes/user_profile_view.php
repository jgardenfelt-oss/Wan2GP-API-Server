<?php
require_once __DIR__ . '/auth.php';
$current_user_id = $_SESSION['user_id'];

$user_id = intval($_GET['id'] ?? 0);
if (!$user_id) { echo '<div data-t="user_not_found" style="padding: 24px;">' . t('user_not_found') . '</div>'; exit; }

$stmt = $pdo->prepare("SELECT id, username, avatar_url, created_at FROM users WHERE id = ? AND is_banned = 0");
$stmt->execute([$user_id]);
$profile_user = $stmt->fetch();
if (!$profile_user) { echo '<div data-t="user_not_found" style="padding: 24px;">' . t('user_not_found') . '</div>'; exit; }

$is_self = ($user_id === $current_user_id);

// Get friend status
$friend_status = 'none';
if (!$is_self) {
    $stmt = $pdo->prepare("SELECT status, user_id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
    $stmt->execute([$current_user_id, $user_id, $user_id, $current_user_id]);
    $rel = $stmt->fetch();
    if ($rel) {
        if ($rel['status'] === 'accepted') $friend_status = 'friends';
        elseif ($rel['user_id'] == $current_user_id) $friend_status = 'sent';
        else $friend_status = 'received';
    }
}

$stmt = $pdo->prepare("SELECT * FROM songs WHERE user_id = ? AND is_public = 1 AND status = 'completed' AND audio_url NOT LIKE 'task:%' ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$public_songs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE ((user_id = ? OR friend_id = ?) AND status = 'accepted')");
$stmt->execute([$user_id, $user_id]);
$friend_count = $stmt->fetchColumn();
?>
<div style="padding: 24px 0;">
    <!-- User Header -->
    <div style="display: flex; align-items: center; gap: 24px; margin-bottom: 32px;">
        <div style="width: 80px; height: 80px; border-radius: 50%; flex-shrink: 0; overflow: hidden;">
            <img src="<?php echo htmlspecialchars($profile_user['avatar_url'] ?: 'assets/images/default-avatar.jpg'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div style="flex: 1;">
            <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 4px;"><?php echo htmlspecialchars($profile_user['username']); ?></h1>
            <p style="color: var(--text-secondary); font-size: 14px;"><span data-t="joined"><?php echo t('joined'); ?></span> <?php echo date('M Y', strtotime($profile_user['created_at'])); ?> • <?php echo count($public_songs); ?> <span data-t="shared_songs"><?php echo t('shared_songs'); ?></span> • <?php echo $friend_count; ?> <span data-t="friends"><?php echo t('friends'); ?></span></p>
        </div>
        <?php if (!$is_self): ?>
            <div id="friend-btn-container">
                <?php if ($friend_status === 'none'): ?>
                    <button data-t="add_friend" onclick="profileFriendAction('send')" class="icon-btn" style="width: auto; padding: 10px 20px; border-radius: 12px; font-size: 14px; gap: 8px;" title="<?php echo t('add_friend'); ?>"><i class="fa-solid fa-user-plus"></i> <?php echo t('add_friend'); ?></button>
                <?php elseif ($friend_status === 'sent'): ?>
                    <button data-t="request_sent" onclick="profileFriendAction('remove')" class="action-btn" style="width: auto; padding: 10px 20px; border-radius: 12px; font-size: 14px; gap: 8px; background: var(--bg-tertiary);" title="<?php echo t('cancel_request'); ?>"><i class="fa-solid fa-clock"></i> <?php echo t('request_sent'); ?></button>
                <?php elseif ($friend_status === 'received'): ?>
                    <button data-t="accept" onclick="profileFriendAction('accept')" class="icon-btn" style="width: auto; padding: 10px 20px; border-radius: 12px; font-size: 14px; gap: 8px; background: #4ade80;" title="Accept request"><i class="fa-solid fa-check"></i> <?php echo t('accept'); ?></button>
                <?php else: ?>
                    <button data-t="remove_friend" onclick="profileFriendAction('remove')" class="action-btn" style="width: auto; padding: 10px 20px; border-radius: 12px; font-size: 14px; gap: 8px; background: var(--bg-tertiary);" title="<?php echo t('remove_friend'); ?>"><i class="fa-solid fa-user-minus"></i> <?php echo t('friends'); ?></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
        <div>
            <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 4px;"><?php echo htmlspecialchars($profile_user['username']); ?></h1>
            <p style="color: var(--text-secondary); font-size: 14px;"><span data-t="joined"><?php echo t('joined'); ?></span> <?php echo date('M Y', strtotime($profile_user['created_at'])); ?> • <?php echo count($public_songs); ?> <span data-t="shared_songs"><?php echo t('shared_songs'); ?></span></p>
        </div>
    </div>

    <!-- Public Songs -->
    <?php if (empty($public_songs)): ?>
        <div style="text-align: center; padding: 80px 0; color: var(--text-secondary);">
            <i class="fa-solid fa-music" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.2;"></i>
            <p data-t="no_shared_songs"><?php echo t('no_shared_songs'); ?></p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
            <?php foreach ($public_songs as $index => $song): ?>
                <?php $cover = $song['image_url'] ?: 'https://picsum.photos/seed/'.$song['id'].'/400/400'; ?>
                <div class="song-card" style="background: var(--bg-secondary); padding: 16px; border-radius: 20px; border: 1px solid var(--border-color); cursor: pointer; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'" onclick="updatePlayer('<?php echo htmlspecialchars($song['audio_url'], ENT_QUOTES); ?>', '<?php echo addslashes($song['title']); ?>', '<?php echo $cover; ?>', window._profilePlaylist, <?php echo $index; ?>)">
                    <div style="width: 100%; aspect-ratio: 1; background: #222; border-radius: 12px; margin-bottom: 12px; overflow: hidden;">
                        <img src="<?php echo $cover; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h4 style="font-size: 14px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($song['title']); ?></h4>
                    <p style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($song['tags']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
window._profilePlaylist = <?php echo json_encode(array_map(function($s) { return ['id' => $s['id'], 'audio_url' => $s['audio_url'], 'title' => $s['title'], 'image_url' => $s['image_url'] ?: 'https://picsum.photos/seed/'.$s['id'].'/400/400']; }, $public_songs), JSON_HEX_TAG); ?>;

async function profileFriendAction(action) {
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('friend_id', <?php echo $user_id; ?>);
        const res = await fetch('api/friends.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            loadPage('user_profile&id=<?php echo $user_id; ?>');
        } else {
            showToast(data.error, 'error');
        }
    } catch(e) { showToast('<?php echo t('network_error_short'); ?>', 'error'); }
}
</script>

<style>
.song-card:hover { border-color: var(--accent-primary) !important; }
</style>
