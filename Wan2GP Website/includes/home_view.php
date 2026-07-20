<?php
require_once __DIR__ . '/auth.php';
if (!is_logged_in()) { echo '<div data-t="please_login" style="padding: 24px;">' . t('please_login') . '</div>'; exit; }
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM songs WHERE user_id = ? AND status = 'completed' AND audio_url NOT LIKE 'task:%' ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$my_songs = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT s.*, u.username FROM songs s JOIN users u ON s.user_id = u.id WHERE s.is_public = 1 AND s.status = 'completed' AND s.audio_url NOT LIKE 'task:%' AND s.user_id != ? ORDER BY s.created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$public_songs = $stmt->fetchAll();
$all_songs = array_merge($my_songs, $public_songs);
?>
<script>window._homePlaylist = <?php echo json_encode(array_map(function($s) { return ['id' => $s['id'], 'audio_url' => $s['audio_url'], 'title' => $s['title'], 'image_url' => $s['image_url'] ?: 'https://picsum.photos/seed/'.$s['id'].'/400/400']; }, $all_songs), JSON_HEX_TAG); ?>;</script>

<div style="padding: 24px 0;">
    <?php if (!empty($my_songs)): ?>
    <section style="margin-bottom: 48px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 data-t="recent_creations" style="font-size: 20px; font-weight: 700;"><?php echo t('recent_creations'); ?></h2>
            <a data-t="create_new" href="javascript:void(0)" onclick="loadPage('create')" style="color: var(--accent-primary); font-size: 14px; text-decoration: none;"><?php echo t('create_new'); ?></a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($my_songs as $index => $song): ?>
                <?php $cover = $song['image_url'] ?: 'https://picsum.photos/seed/'.$song['id'].'/300/300'; ?>
                <div class="song-card-small" style="background: var(--bg-secondary); padding: 12px; border-radius: 16px; border: 1px solid var(--border-color); cursor: pointer;" onclick="updatePlayer('<?php echo htmlspecialchars($song['audio_url'], ENT_QUOTES); ?>', '<?php echo addslashes($song['title']); ?>', '<?php echo $cover; ?>', window._homePlaylist, <?php echo $index; ?>)">
                    <div style="width: 100%; aspect-ratio: 1; background: #222; border-radius: 10px; margin-bottom: 10px; overflow: hidden;">
                        <img src="<?php echo $cover; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h4 style="font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($song['title']); ?></h4>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 data-t="community_spotlight" style="font-size: 20px; font-weight: 700;"><?php echo t('community_spotlight'); ?></h2>
            <a data-t="explore_all" href="javascript:void(0)" onclick="loadPage('explore')" style="color: var(--accent-primary); font-size: 14px; text-decoration: none;"><?php echo t('explore_all'); ?></a>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
            <?php if (empty($public_songs)): ?>
                <div style="grid-column: 1/-1; background: var(--bg-secondary); padding: 40px; border-radius: 20px; text-align: center; border: 1px dashed var(--border-color);">
                    <p data-t="no_public_songs_home" style="color: var(--text-secondary);"><?php echo t('no_public_songs_home'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($public_songs as $index => $song): ?>
                    <?php $cover = $song['image_url'] ?: 'https://picsum.photos/seed/'.$song['id'].'/200/200'; ?>
                    <div class="song-card" style="background: var(--bg-secondary); padding: 16px; border-radius: 20px; border: 1px solid var(--border-color); position: relative; cursor: pointer;" onclick="updatePlayer('<?php echo htmlspecialchars($song['audio_url'], ENT_QUOTES); ?>', '<?php echo addslashes($song['title']); ?>', '<?php echo $cover; ?>', window._homePlaylist, <?php echo count($my_songs) + $index; ?>)">
                        <div style="display: flex; gap: 16px;">
                            <div style="width: 80px; height: 80px; flex-shrink: 0; border-radius: 12px; overflow: hidden; background: #222;">
                                <img src="<?php echo $cover; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="display: flex; flex-direction: column; justify-content: center; min-width: 0;">
                                <h4 style="font-size: 16px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($song['title']); ?></h4>
                                <p data-t="by" style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;"><?php echo t('by'); ?> <?php echo htmlspecialchars($song['username']); ?></p>
                                <span style="font-size: 11px; background: var(--bg-tertiary); padding: 2px 8px; border-radius: 10px; width: fit-content;"><?php echo htmlspecialchars(explode(',', $song['tags'])[0]); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
.song-card-small:hover, .song-card:hover { border-color: var(--accent-primary) !important; transform: scale(1.02); transition: all 0.2s; }
</style>
