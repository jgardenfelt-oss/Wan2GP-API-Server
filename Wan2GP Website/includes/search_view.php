<?php
require_once __DIR__ . '/auth.php';
$q = htmlspecialchars($_GET['q'] ?? '');
?>
<div style="padding: 24px 0;">
    <h2 data-t="results_for" style="font-size: 20px; font-weight: 700; margin-bottom: 24px;"><?php echo t('results_for'); ?> "<?php echo $q; ?>"</h2>
    <div id="search-results"><div class="loader" style="width: 30px; height: 30px; border: 3px solid rgba(255,255,255,0.1); border-top-color: var(--accent-primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 40px auto;"></div></div>
</div>

<script>
(async function() {
    const res = await fetch('api/search.php?q=' + encodeURIComponent('<?php echo addslashes($_GET['q'] ?? ''); ?>'));
    const data = await res.json();
    const container = document.getElementById('search-results');
    
    if (!data.songs.length && !data.users.length) {
        container.innerHTML = '<p data-t="no_results" style="color: var(--text-secondary); text-align: center; padding: 60px;"><?php echo t('no_results'); ?></p>';
        return;
    }

    let html = '';

    if (data.users.length) {
        html += '<h3 data-t="section_users" style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-secondary);"><?php echo t('section_users'); ?></h3>';
        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px;">';
        data.users.forEach(u => {
            html += `<div class="user-card" onclick="loadPage('user_profile&id=${u.id}')" style="background: var(--bg-secondary); padding: 16px; border-radius: 16px; border: 1px solid var(--border-color); cursor: pointer; display: flex; align-items: center; gap: 14px; transition: all 0.2s;">
                <div style="width: 48px; height: 48px; border-radius: 50%; flex-shrink: 0; overflow: hidden;">
                    <img src="${u.avatar_url || 'assets/images/default-avatar.jpg'}" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <div style="font-size: 15px; font-weight: 600;">${u.username}</div>
            </div>`;
        });
        html += '</div>';
    }

    if (data.songs.length) {
        html += '<h3 data-t="section_songs" style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-secondary);"><?php echo t('section_songs'); ?></h3>';
        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">';
        data.songs.forEach((s, i) => {
            const cover = s.image_url || 'https://picsum.photos/seed/' + s.id + '/400/400';
            html += `<div class="song-card" style="background: var(--bg-secondary); padding: 12px; border-radius: 16px; border: 1px solid var(--border-color); cursor: pointer; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'" onclick="updatePlayer('${s.audio_url}', '${s.title.replace(/'/g, "\\'")}', '${cover}', window._searchPlaylist, ${i})">
                <div style="width: 100%; aspect-ratio: 1; background: #222; border-radius: 10px; margin-bottom: 10px; overflow: hidden;">
                    <img src="${cover}" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <h4 style="font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;">${s.title}</h4>
                <p style="font-size: 12px; color: var(--text-secondary);">${s.username}</p>
            </div>`;
        });
        html += '</div>';
    }

    container.innerHTML = html;
    window._searchPlaylist = data.songs.map(s => ({id: s.id, audio_url: s.audio_url, title: s.title, image_url: s.image_url || 'https://picsum.photos/seed/' + s.id + '/400/400'}));
})();
</script>

<style>
.user-card:hover { border-color: var(--accent-primary) !important; }
.song-card:hover { border-color: var(--accent-primary) !important; }
</style>
