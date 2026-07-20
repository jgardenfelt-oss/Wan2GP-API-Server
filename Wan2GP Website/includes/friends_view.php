<?php
require_once __DIR__ . '/auth.php';
$user_id = $_SESSION['user_id'];

// Fetch friends and pending requests
$stmt = $pdo->prepare("SELECT u.id, u.username, u.avatar_url, f.status, f.user_id as requester_id FROM friends f JOIN users u ON (u.id = CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END) WHERE (f.user_id = ? OR f.friend_id = ?) ORDER BY f.created_at DESC");
$stmt->execute([$user_id, $user_id, $user_id]);
$all = $stmt->fetchAll();

$accepted = array_filter($all, fn($f) => $f['status'] === 'accepted');
$pending_received = array_filter($all, fn($f) => $f['status'] === 'pending' && $f['requester_id'] != $user_id);
$pending_sent = array_filter($all, fn($f) => $f['status'] === 'pending' && $f['requester_id'] == $user_id);
?>
<div style="padding: 24px 0;">
    <!-- Pending Requests Received -->
    <?php if (count($pending_received)): ?>
    <section style="margin-bottom: 32px;">
        <h2 data-t="friend_requests" style="font-size: 18px; font-weight: 700; margin-bottom: 16px;"><?php echo t('friend_requests'); ?><span style="color: var(--accent-primary);">(<?php echo count($pending_received); ?>)</span></h2>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <?php foreach ($pending_received as $user): ?>
                <div class="friend-row" data-uid="<?php echo $user['id']; ?>" style="display: flex; align-items: center; gap: 14px; background: var(--bg-secondary); padding: 14px 20px; border-radius: 14px; border: 1px solid var(--border-color);">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; flex-shrink: 0; overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($user['avatar_url'] ?: 'assets/images/default-avatar.jpg'); ?>" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div style="flex: 1; font-size: 15px; font-weight: 600; cursor: pointer;" onclick="loadPage('user_profile&id=<?php echo $user['id']; ?>')"><?php echo htmlspecialchars($user['username']); ?></div>
                    <button class="icon-btn" data-t-title="accept" onclick="friendAction('accept', <?php echo $user['id']; ?>)" title="<?php echo t('accept'); ?>" style="background: #4ade80;"><i class="fa-solid fa-check"></i></button>
                    <button class="action-btn" data-t-title="reject" onclick="friendAction('reject', <?php echo $user['id']; ?>)" title="<?php echo t('reject'); ?>"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Pending Sent -->
    <?php if (count($pending_sent)): ?>
    <section style="margin-bottom: 32px;">
        <h2 data-t="sent_requests" style="font-size: 18px; font-weight: 700; margin-bottom: 16px;"><?php echo t('sent_requests'); ?></h2>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <?php foreach ($pending_sent as $user): ?>
                <div class="friend-row" data-uid="<?php echo $user['id']; ?>" style="display: flex; align-items: center; gap: 14px; background: var(--bg-secondary); padding: 14px 20px; border-radius: 14px; border: 1px solid var(--border-color);">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; flex-shrink: 0; overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($user['avatar_url'] ?: 'assets/images/default-avatar.jpg'); ?>" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div style="flex: 1; font-size: 15px; font-weight: 600; cursor: pointer;" onclick="loadPage('user_profile&id=<?php echo $user['id']; ?>')"><?php echo htmlspecialchars($user['username']); ?></div>
                    <span data-t="pending" style="font-size: 12px; color: var(--text-secondary);"><?php echo t('pending'); ?></span>
                    <button class="action-btn" onclick="friendAction('remove', <?php echo $user['id']; ?>)" title="<?php echo t('cancel'); ?>"><i class="fa-solid fa-xmark"></i></button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Friends List -->
    <section>
        <h2 data-t="friends" style="font-size: 18px; font-weight: 700; margin-bottom: 16px;"><?php echo t('friends'); ?> <span style="color: var(--text-secondary);">(<?php echo count($accepted); ?>)</span></h2>
        <?php if (empty($accepted)): ?>
            <div style="text-align: center; padding: 60px 0; color: var(--text-secondary);">
                <i class="fa-solid fa-user-group" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.2;"></i>
                <p data-t="no_friends"><?php echo t('no_friends'); ?> <a data-t="find_users" href="javascript:void(0)" onclick="loadPage('users')" style="color: var(--accent-primary); text-decoration: none;"><?php echo t('find_users'); ?></a> <span data-t="to_add"><?php echo t('to_add'); ?></span></p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                <?php foreach ($accepted as $user): ?>
                    <div class="friend-row" data-uid="<?php echo $user['id']; ?>" style="display: flex; align-items: center; gap: 14px; background: var(--bg-secondary); padding: 16px 20px; border-radius: 16px; border: 1px solid var(--border-color); cursor: pointer;" onclick="loadPage('user_profile&id=<?php echo $user['id']; ?>')">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; color: white; flex-shrink: 0; overflow: hidden;">
                            <img src="<?php echo htmlspecialchars($user['avatar_url'] ?: 'assets/images/default-avatar.jpg'); ?>" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 15px; font-weight: 600;"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        <button class="action-btn" data-t-title="remove_friend" onclick="event.stopPropagation(); friendAction('remove', <?php echo $user['id']; ?>)" title="<?php echo t('remove_friend'); ?>"><i class="fa-solid fa-user-minus"></i></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
async function friendAction(action, friendId) {
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('friend_id', friendId);
        const res = await fetch('api/friends.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast(data.message, 'success');
            loadPage('friends');
        } else {
            showToast(data.error, 'error');
        }
    } catch(e) { showToast('<?php echo t('network_error_short'); ?>', 'error'); }
}
</script>

<style>
.friend-row:hover { border-color: var(--accent-primary) !important; }
</style>
