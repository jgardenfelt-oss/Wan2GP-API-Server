<?php
require_once __DIR__ . '/auth.php';
$csrf_token = generate_csrf_token();
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM songs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$recent_songs = $stmt->fetchAll();
?>
<header class="content-header">
    <h1>Create</h1>
</header>

<div class="create-container" style="max-width: 700px; margin: 0 auto;">
    <div style="background: var(--bg-secondary); padding: 40px; border-radius: 32px; border: 1px solid var(--border-color); box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
        <form id="generate-form">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $csrf_token; ?>">

            <div style="margin-bottom: 32px;">
                <label style="display: block; margin-bottom: 12px; color: var(--text-secondary); font-weight: 600;">Song Lyrics</label>
                <textarea id="prompt" name="prompt" required
                    placeholder="[Verse 1]&#10;Enter your lyrics here...&#10;&#10;[Chorus]&#10;Add your chorus..."
                    style="width: 100%; height: 220px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 20px; padding: 20px; color: white; font-size: 16px; line-height: 1.6; resize: none;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px;">
                <div>
                    <label style="display: block; margin-bottom: 12px; color: var(--text-secondary); font-weight: 600;">Style / Tags</label>
                    <input type="text" id="tags" name="tags" placeholder="e.g. pop, male"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 16px; padding: 14px; color: white; font-size: 15px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 12px; color: var(--text-secondary); font-weight: 600;">Title</label>
                    <input type="text" id="title" name="title" placeholder="Optional"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 16px; padding: 14px; color: white; font-size: 15px;">
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <div onclick="document.getElementById('advanced-settings').style.display = document.getElementById('advanced-settings').style.display === 'none' ? 'block' : 'none';"
                    style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-secondary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; padding: 8px 0;">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Advanced Settings</span>
                    <i class="fa-solid fa-chevron-down" style="margin-left: auto; font-size: 10px; transition: transform .2s;" id="adv-chevron"></i>
                </div>
                <div id="advanced-settings" style="display: none; background: var(--bg-tertiary); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color);">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div>
                            <label style="display: block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px; font-weight: 600;">Guidance Scale</label>
                            <select id="guidance_scale" name="guidance_scale"
                                style="width: 100%; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; color: white; font-size: 13px;">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8" selected>8 (Default)</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="12">12</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px; font-weight: 600;">Temperature</label>
                            <select id="temperature" name="temperature"
                                style="width: 100%; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; color: white; font-size: 13px;">
                                <option value="0.1">0.1</option>
                                <option value="0.3">0.3</option>
                                <option value="0.5">0.5</option>
                                <option value="0.7">0.7</option>
                                <option value="0.8">0.8</option>
                                <option value="0.9">0.9</option>
                                <option value="1.0" selected>1.0 (Default)</option>
                                <option value="1.1">1.1</option>
                                <option value="1.2">1.2</option>
                                <option value="1.3">1.3</option>
                                <option value="1.5">1.5</option>
                                <option value="2.0">2.0</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px; font-weight: 600;">Top P</label>
                            <select id="top_p" name="top_p"
                                style="width: 100%; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; color: white; font-size: 13px;">
                                <option value="0.1">0.1</option>
                                <option value="0.3">0.3</option>
                                <option value="0.5">0.5</option>
                                <option value="0.7">0.7</option>
                                <option value="0.8">0.8</option>
                                <option value="0.9" selected>0.9 (Default)</option>
                                <option value="0.95">0.95</option>
                                <option value="1.0">1.0</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px; font-weight: 600;">Top K</label>
                            <select id="top_k" name="top_k"
                                style="width: 100%; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; color: white; font-size: 13px;">
                                <option value="0">0</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="30">30</option>
                                <option value="40">40</option>
                                <option value="50" selected>50 (Default)</option>
                                <option value="60">60</option>
                                <option value="80">80</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; margin-bottom: 6px; color: var(--text-secondary); font-size: 12px; font-weight: 600;">Source Audio Strength</label>
                            <select id="source_audio_strength" name="source_audio_strength"
                                style="width: 100%; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; color: white; font-size: 13px;">
                                <option value="0">0 (No influence)</option>
                                <option value="0.1">0.1</option>
                                <option value="0.2">0.2</option>
                                <option value="0.3">0.3</option>
                                <option value="0.4">0.4</option>
                                <option value="0.5" selected>0.5 (Default)</option>
                                <option value="0.6">0.6</option>
                                <option value="0.7">0.7</option>
                                <option value="0.8">0.8</option>
                                <option value="0.9">0.9</option>
                                <option value="1.0">1.0 (Full influence)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; background: var(--bg-tertiary); padding: 16px; border-radius: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fa-solid fa-bolt" style="color: var(--accent-primary);"></i>
                    <span style="font-size: 14px;">Cost: 10 Credits</span>
                </div>
                <div style="font-size: 14px; color: var(--text-secondary);">
                    High Quality &bull; ACE-Step v1.5
                </div>
            </div>

            <button type="submit" id="create-btn" class="btn-primary"
                style="width: 100%; padding: 18px; font-size: 18px; border-radius: 20px; box-shadow: 0 10px 20px rgba(236, 72, 153, 0.2);">
                Create Music
            </button>
        </form>
        <div id="gen-status" style="margin-top: 20px; text-align: center; display: none;"></div>
    </div>

    <!-- Recent Songs -->
    <div id="recent-songs" style="margin-top: 32px;">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--text-secondary);">Your Recent Songs</h3>
        <div id="songs-list" style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($recent_songs as $song): ?>
            <div class="create-song-row" data-song-id="<?php echo $song['id']; ?>" data-status="<?php echo $song['status']; ?>" data-created-at="<?php echo $song['created_at']; ?>"
                style="display: flex; align-items: center; gap: 16px; background: var(--bg-secondary); padding: 12px 20px; border-radius: 16px; border: 1px solid var(--border-color);">
                <div style="width: 44px; height: 44px; border-radius: 8px; overflow: hidden; background: #222; flex-shrink: 0;">
                    <?php if ($song['status'] === 'pending'): ?>
                        <div class="loader" style="width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.1); border-top-color: var(--accent-primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 13px auto;"></div>
                    <?php else: ?>
                        <img src="<?php echo $song['image_url'] ?: 'https://picsum.photos/seed/'.$song['id'].'/100/100'; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h4 style="font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($song['title']); ?></h4>
                    <p style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($song['tags'] ?: 'No tags'); ?></p>
                </div>
                <?php if ($song['status'] === 'completed'): ?>
                    <button onclick="event.stopPropagation(); playCreateSong(<?php echo htmlspecialchars(json_encode($song)); ?>)" style="background: white; color: black; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0;"><i class="fa-solid fa-play"></i></button>
                <?php elseif ($song['status'] === 'pending'): ?>
                    <div style="display: flex; flex-direction: column; gap: 4px; min-width: 120px; flex-shrink: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="song-phase" style="font-size: 11px; color: var(--accent-primary);">Generating...</span>
                            <span class="song-progress-text" style="font-size: 11px; color: var(--text-secondary);">0%</span>
                        </div>
                        <div style="height: 4px; background: var(--bg-tertiary); border-radius: 2px; overflow: hidden;">
                            <div class="song-progress-fill" style="height: 100%; background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary)); border-radius: 2px; width: 0%; transition: width 0.5s ease;"></div>
                        </div>
                        <span class="song-elapsed" style="font-size: 11px; color: var(--text-secondary);">0s</span>
                    </div>
                <?php else: ?>
                    <span style="font-size: 12px; color: #f87171; flex-shrink: 0;">Failed</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recent_songs)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary); font-size: 14px;">No songs yet. Create your first one above!</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function playCreateSong(song) {
        if (song.status !== 'completed' || !song.audio_url) return;
        const coverUrl = song.image_url || 'https://picsum.photos/seed/' + song.id + '/400/400';
        updatePlayer(song.audio_url, song.title, coverUrl);
    }

    // Start polling for any pending songs on page load
    document.querySelectorAll('.create-song-row[data-status="pending"]').forEach(row => {
        const id = row.getAttribute('data-song-id');
        const title = row.querySelector('h4').innerText;
        const startedAt = row.getAttribute('data-created-at');
        if (typeof startPendingPoll === 'function') startPendingPoll(id, title, startedAt);
    });

    document.getElementById('generate-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('create-btn');
        const status = document.getElementById('gen-status');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
        status.style.display = 'block';
        status.innerHTML = '<span style="color: var(--text-secondary)">Starting generation...</span>';

        try {
            const response = await fetch('api/generate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    prompt: document.getElementById('prompt').value,
                    tags: document.getElementById('tags').value,
                    title: document.getElementById('title').value,
                    guidance_scale: document.getElementById('guidance_scale').value,
                    temperature: document.getElementById('temperature').value,
                    top_p: document.getElementById('top_p').value,
                    top_k: document.getElementById('top_k').value,
                    source_audio_strength: document.getElementById('source_audio_strength').value,
                    csrf_token: document.getElementById('csrf_token').value
                })
            });

            const result = await response.json();
            if (result.success) {
                const title = document.getElementById('title').value || 'Untitled Song';
                const songId = result.song_id;

                // Start background polling immediately (persists across page change)
                const now = new Date().toISOString();
                if (typeof startPendingPoll === 'function') startPendingPoll(songId, title, now);

                // Show success then redirect to library
                showToast('Song generation started!', 'success');
                setTimeout(() => loadPage('library'), 800);
                return;
            } else {
                status.innerHTML = `<span style="color: #f87171">${result.error}</span>`;
            }
        } catch (err) {
            status.innerHTML = '<span style="color: #f87171">Network error. Try again.</span>';
        }

        btn.disabled = false;
        btn.innerHTML = 'Create Music';
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    async function refreshCreateSongs() {
        try {
            const res = await fetch('api/get_songs.php');
            const data = await res.json();
            if (!data.success) return;
            const list = document.getElementById('songs-list');
            if (!list) return;
            const songs = data.songs.slice(0, 10);
            list.innerHTML = songs.map(song => {
                const img = song.image_url || 'https://picsum.photos/seed/' + song.id + '/100/100';
                const t = escapeHtml(song.title || 'Untitled');
                const tg = escapeHtml(song.tags || 'No tags');
                const statusRight = song.status === 'pending'
                    ? `<div style="display: flex; flex-direction: column; gap: 4px; min-width: 120px; flex-shrink: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="song-phase" style="font-size: 11px; color: var(--accent-primary);">Generating...</span>
                            <span class="song-progress-text" style="font-size: 11px; color: var(--text-secondary);">0%</span>
                        </div>
                        <div style="height: 4px; background: var(--bg-tertiary); border-radius: 2px; overflow: hidden;">
                            <div class="song-progress-fill" style="height: 100%; background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary)); border-radius: 2px; width: 0%; transition: width 0.5s ease;"></div>
                        </div>
                        <span class="song-elapsed" style="font-size: 11px; color: var(--text-secondary);">0s</span>
                       </div>`
                    : song.status === 'completed'
                        ? `<button onclick="event.stopPropagation(); playCreateSong(${JSON.stringify(song).replace(/"/g, '&quot;')})" style="background: white; color: black; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0;"><i class="fa-solid fa-play"></i></button>`
                        : `<span style="font-size: 12px; color: #f87171; flex-shrink: 0;">Failed</span>`;
                return `
                <div class="create-song-row" data-song-id="${song.id}" data-status="${song.status}" data-created-at="${song.created_at}"
                    style="display: flex; align-items: center; gap: 16px; background: var(--bg-secondary); padding: 12px 20px; border-radius: 16px; border: 1px solid var(--border-color);">
                    <div style="width: 44px; height: 44px; border-radius: 8px; overflow: hidden; background: #222; flex-shrink: 0;">
                        ${song.status === 'pending'
                            ? '<div class="loader" style="width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.1); border-top-color: var(--accent-primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 13px auto;"></div>'
                            : `<img src="${img}" style="width: 100%; height: 100%; object-fit: cover;">`}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h4 style="font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${t}</h4>
                        <p style="font-size: 12px; color: var(--text-secondary);">${tg}</p>
                    </div>
                    ${statusRight}
                </div>`;
            }).join('');
            // Re-start polling for any remaining pending songs
            document.querySelectorAll('.create-song-row[data-status="pending"]').forEach(row => {
                const id = row.getAttribute('data-song-id');
                const title = row.querySelector('h4').innerText;
                const startedAt = row.getAttribute('data-created-at');
                if (typeof startPendingPoll === 'function') startPendingPoll(id, title, startedAt);
            });
        } catch(e) {}
    }
</script>
<style>
@keyframes slideIn { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
