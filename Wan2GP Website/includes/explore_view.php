<?php
require_once __DIR__ . '/auth.php';

// Fetch all public, completed songs
$stmt = $pdo->prepare("SELECT s.*, u.username FROM songs s JOIN users u ON s.user_id = u.id WHERE s.is_public = 1 AND s.status = 'completed' AND s.audio_url NOT LIKE 'task:%' ORDER BY s.created_at DESC LIMIT 20");
$stmt->execute();
$songs = $stmt->fetchAll();
?>
<script>
window._explorePlaylist = <?php echo json_encode(array_map(function($s) { return ['id' => $s['id'], 'audio_url' => $s['audio_url'], 'title' => $s['title'], 'image_url' => $s['image_url'] ?: 'https://picsum.photos/seed/'.$s['id'].'/400/400']; }, $songs), JSON_HEX_TAG); ?>;
</script>

<div style="padding: 24px 0;">
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
        <?php if (empty($songs)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px; color: var(--text-secondary);">
                <i class="fa-solid fa-compass" style="font-size: 48px; margin-bottom: 16px; opacity: 0.2;"></i>
                <p data-t="no_public_songs"><?php echo t('no_public_songs'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($songs as $index => $song): ?>
                <?php $cover = $song['image_url'] ?: 'https://picsum.photos/seed/'.$song['id'].'/400/225'; ?>
                <div class="song-card" style="background: var(--bg-secondary); padding: 16px; border-radius: 20px; border: 1px solid var(--border-color); position: relative; overflow: hidden; transition: transform 0.3s;" onclick="updatePlayer('<?php echo htmlspecialchars($song['audio_url'], ENT_QUOTES); ?>', '<?php echo addslashes($song['title']); ?>', '<?php echo $cover; ?>', window._explorePlaylist, <?php echo $index; ?>)">
                    <div style="width: 100%; aspect-ratio: 16/9; background: #222; border-radius: 12px; margin-bottom: 12px; overflow: hidden; position: relative;">
                        <img src="<?php echo $cover; ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    </div>
                    <h4 style="font-size: 16px; margin-bottom: 4px;"><?php echo htmlspecialchars($song['title']); ?></h4>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;"><?php echo htmlspecialchars($song['tags']); ?></p>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary);">
                        <div style="width: 20px; height: 20px; background: var(--accent-secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; color: white;">
                            <?php echo strtoupper(substr($song['username'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($song['username']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.song-card:hover { transform: translateY(-5px); }
.song-card:hover .play-overlay { opacity: 1; }
</style>
