<?php
require_once __DIR__ . '/auth.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM songs WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$allSongs = $stmt->fetchAll();

$liked = $pdo->prepare("SELECT s.* FROM likes l JOIN songs s ON l.song_id = s.id WHERE l.user_id = ? ORDER BY l.created_at DESC");
$liked->execute([$user_id]);
$likedSongs = $liked->fetchAll();
?>
<div class="library-tabs" style="display: flex; gap: 16px; margin-bottom: 24px;">
    <button class="library-tab active" data-t="tab_my_songs" data-tab="mine" onclick="switchLibTab('mine')" style="padding: 10px 20px; border-radius: 12px; border: none; background: var(--bg-tertiary); color: var(--text-primary); font-weight: 600; cursor: pointer; font-family: var(--font-main);"><?php echo t('tab_my_songs'); ?></button>
    <button class="library-tab" data-t="tab_liked" data-tab="liked" onclick="switchLibTab('liked')" style="padding: 10px 20px; border-radius: 12px; border: none; background: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; font-family: var(--font-main);"><?php echo t('tab_liked'); ?></button>
</div>

<div style="display: flex; gap: 24px; height: calc(100vh - 160px);">
    <!-- Song List -->
    <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; overflow: hidden;">
        <div id="lib-mine" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
            <?php if (empty($allSongs)): ?>
<div style="text-align: center; padding: 80px 0; color: var(--text-secondary);"><i class="fa-solid fa-music" style="font-size: 48px; margin-bottom: 16px; display: block;"></i><p data-t="no_songs"><?php echo t('no_songs'); ?></p></div>
            <?php else: ?>
                <div id="lib-mine-list" class="workspace-songs" style="flex: 1; overflow-y: auto; padding-right: 8px;">
                <?php foreach ($allSongs as $index => $song): ?>
                    <?php $cover = $song['image_url'] ?: 'https://picsum.photos/seed/'.$song['id'].'/400/400'; ?>
                    <div class="song-card-list" data-song-id="<?php echo $song['id']; ?>" data-audio-url="<?php echo htmlspecialchars($song['audio_url'] ?? ''); ?>" onclick="playSong(<?php echo $song['id']; ?>, '<?php echo addslashes($song['title']); ?>', '<?php echo $cover; ?>', '<?php echo $song['audio_url']; ?>'); showSongDetails(<?php echo htmlspecialchars(json_encode($song)); ?>, <?php echo $index; ?>)">
                        <div class="song-card-cover" style="background-image: url('<?php echo $cover; ?>');">
                            <?php if ($song['status'] === 'completed'): ?>
                            <div class="play-overlay"><i class="fa-solid fa-play"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="song-card-info">
                            <div class="title"><?php echo htmlspecialchars($song['title']); ?> <span class="version-badge"><?php echo t('ace_version'); ?></span></div>
                            <div class="meta"><?php echo htmlspecialchars($song['tags'] ?: t('no_tags')); ?></div>
                        </div>
                        <div class="song-card-actions">
                            <?php if ($song['status'] === 'completed'): ?>
                                <span class="duration" data-audio-url="<?php echo htmlspecialchars($song['audio_url'] ?? ''); ?>"></span>
                                <button class="action-btn" onclick="event.stopPropagation(); playSong(<?php echo $song['id']; ?>, '<?php echo addslashes($song['title']); ?>', '<?php echo $cover; ?>', '<?php echo $song['audio_url']; ?>')"><i class="fa-solid fa-play"></i></button>
                                <div class="song-menu-wrap">
                                    <button class="action-btn" onclick="event.stopPropagation(); toggleSongMenu(this)"><i class="fa-solid fa-ellipsis"></i></button>
                                    <div class="song-menu">
                                        <a href="api/download.php?id=<?php echo $song['id']; ?>" onclick="event.stopPropagation();" download><i class="fa-solid fa-download"></i> <?php echo t('download'); ?></a>
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); shareSong(<?php echo $song['id']; ?>); closeSongMenu();"><i class="fa-solid fa-share-nodes"></i> <?php echo t('share'); ?></a>
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); deleteSong(<?php echo $song['id']; ?>); closeSongMenu();" style="color:#f87171;"><i class="fa-solid fa-trash"></i> <?php echo t('delete'); ?></a>
                                    </div>
                                </div>
                            <?php elseif ($song['status'] === 'pending'): ?>
                                <span class="song-elapsed" style="font-size:12px; color:var(--text-secondary);">0s</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="lib-liked" style="display: none;">
            <?php if (empty($likedSongs)): ?>
                <div style="text-align: center; padding: 80px 0; color: var(--text-secondary);"><i class="fa-solid fa-heart" style="font-size: 48px; margin-bottom: 16px; display: block;"></i><p data-t="no_liked_songs"><?php echo t('no_liked_songs'); ?></p></div>
            <?php else: ?>
                <div id="lib-liked-list" class="workspace-songs">
                <?php foreach ($likedSongs as $index => $song): ?>
                    <?php $lcover = $song['image_url'] ?: 'https://picsum.photos/seed/'.$song['id'].'/400/400'; ?>
                    <div class="song-card-list" data-song-id="<?php echo $song['id']; ?>" data-audio-url="<?php echo htmlspecialchars($song['audio_url'] ?? ''); ?>" onclick="playSong(<?php echo $song['id']; ?>, '<?php echo addslashes($song['title']); ?>', '<?php echo $lcover; ?>', '<?php echo $song['audio_url']; ?>'); showSongDetails(<?php echo htmlspecialchars(json_encode($song)); ?>, <?php echo $index; ?>)">
                        <div class="song-card-cover" style="background-image: url('<?php echo $lcover; ?>');">
                            <?php if ($song['status'] === 'completed'): ?>
                            <div class="play-overlay"><i class="fa-solid fa-play"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="song-card-info">
                            <div class="title"><?php echo htmlspecialchars($song['title']); ?> <span class="version-badge"><?php echo t('ace_version'); ?></span></div>
                            <div class="meta"><?php echo htmlspecialchars($song['tags'] ?: t('no_tags')); ?></div>
                        </div>
                        <div class="song-card-actions">
                            <?php if ($song['status'] === 'completed'): ?>
                                <button class="action-btn" onclick="event.stopPropagation(); playSong(<?php echo $song['id']; ?>, '<?php echo addslashes($song['title']); ?>', '<?php echo $lcover; ?>', '<?php echo $song['audio_url']; ?>')"><i class="fa-solid fa-play"></i></button>
                                <div class="song-menu-wrap">
                                    <button class="action-btn" onclick="event.stopPropagation(); toggleSongMenu(this)"><i class="fa-solid fa-ellipsis"></i></button>
                                    <div class="song-menu">
                                        <a href="api/download.php?id=<?php echo $song['id']; ?>" onclick="event.stopPropagation();" download><i class="fa-solid fa-download"></i> <?php echo t('download'); ?></a>
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); shareSong(<?php echo $song['id']; ?>); closeSongMenu();"><i class="fa-solid fa-share-nodes"></i> <?php echo t('share'); ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Sidebar: Song Details -->
    <div id="song-details" style="width: 320px; flex-shrink: 0; background: var(--bg-secondary); border-radius: 20px; border: 1px solid var(--border-color); overflow-y: auto;">
        <div id="details-empty" style="text-align: center; padding: 80px 20px; color: var(--text-secondary);">
            <i class="fa-solid fa-music" style="font-size: 48px; margin-bottom: 20px; opacity: 0.15;"></i>
            <p data-t="select_song_view" style="font-size: 15px;"><?php echo t('select_song_view'); ?></p>
        </div>
        <div id="details-content" style="display: none; flex-direction: column; padding: 24px;">
            <div class="detail-cover">
                <img id="lib-detail-image" src="" alt="">
            </div>
            <div class="detail-play-actions">
                <div class="detail-stat" id="lib-detail-likes" onclick="libToggleLike()" style="cursor:pointer;">
                    <i class="fa-regular fa-heart" id="lib-detail-heart-icon"></i> <span id="lib-detail-like-count">0</span>
                </div>
                <div class="detail-stat" onclick="libToggleComments()" style="cursor:pointer;">
                    <i class="fa-regular fa-comment"></i> <span id="lib-detail-comment-count">0</span>
                </div>
            </div>
            <h2 class="detail-title" id="lib-detail-title"></h2>
            <p class="detail-username" id="lib-detail-username"></p>
            <div class="detail-style-tags" id="lib-detail-tags"></div>
            <div id="lib-detail-comments-section" style="margin-top: 16px; display: none;">
                <h4 data-t="comments" style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-secondary); margin-bottom: 12px; font-weight: 600;"><?php echo t('comments'); ?></h4>
                <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <input type="text" data-t-placeholder="write_comment" id="lib-comment-input" placeholder="<?php echo t('write_comment'); ?>" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 99px; padding: 10px 16px; color: white; font-size: 13px; outline: none; font-family: var(--font-main);">
                    <button class="btn-primary" data-t="post" style="padding: 10px 16px; border-radius: 99px; font-size: 12px;" onclick="postLibComment()"><?php echo t('post'); ?></button>
                </div>
                <div id="lib-detail-comments-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto;"></div>
            </div>
            <div class="detail-lyrics">
                <div class="lyrics-text" id="lib-detail-lyrics"></div>
            </div>
        </div>
    </div>
</div>

<script>
window._libPlaylist = <?php echo json_encode(array_map(function($s) { $s['image_url'] = $s['image_url'] ?: 'https://picsum.photos/seed/'.$s['id'].'/400/400'; return $s; }, $allSongs), JSON_HEX_TAG); ?>;
window.currentDetailSongId = null;

// Update sidebar details when song changes via next/prev
window.onSongChange = function(song, index) {
    const detailsEmpty = document.getElementById('details-empty');
    if (!detailsEmpty) return;
    detailsEmpty.style.display = 'none';
    const content = document.getElementById('details-content');
    content.style.display = 'flex';

    const coverUrl = song.image_url || 'https://picsum.photos/seed/' + song.id + '/400/400';
    document.getElementById('lib-detail-image').src = coverUrl;
    document.getElementById('lib-detail-title').innerText = song.title || <?php echo json_encode(t('untitled')); ?>;
    document.getElementById('lib-detail-lyrics').innerText = <?php echo json_encode(t('loading')); ?>;
    window.currentDetailSongId = song.id;
    document.getElementById('lib-detail-comments-section').style.display = 'none';
    libRenderTags('lib-detail-tags', '');

    fetch('api/get_song.php?id=' + song.id).then(r => r.json()).then(d => {
        if (!d.success) return;
        const s = d.song;
        document.getElementById('lib-detail-lyrics').innerText = s.lyrics || <?php echo json_encode(t('no_lyrics_available')); ?>;
        libRenderTags('lib-detail-tags', s.tags);
        libUpdateLikeUI(s.liked, s.like_count);
        document.getElementById('lib-detail-comment-count').textContent = s.comment_count || 0;
        localStorage.setItem('lastDetailSong', JSON.stringify({id: song.id, title: s.title, image_url: coverUrl, audio_url: s.audio_url, tags: s.tags || '', lyrics: s.lyrics || ''}));
    }).catch(() => {});

    document.querySelectorAll('.song-row').forEach(row => {
        row.style.borderColor = '';
        row.style.background = '';
    });
    const activeRow = document.querySelector(`.song-row[data-song-id="${song.id}"]`);
    if (activeRow) {
        activeRow.style.borderColor = 'var(--accent-primary)';
        activeRow.style.background = 'var(--bg-tertiary)';
    }
    // Save for restore on next visit
    localStorage.setItem('lastDetailSong', JSON.stringify({
        id: song.id,
        title: song.title,
        image_url: song.image_url,
        audio_url: song.audio_url,
        tags: song.tags || '',
        lyrics: song.lyrics || ''
    }));
};

function switchLibTab(tab) {
    document.querySelectorAll('.library-tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.library-tab[data-tab="${tab}"]`).classList.add('active');
    document.getElementById('lib-mine').style.display = tab === 'mine' ? 'block' : 'none';
    document.getElementById('lib-liked').style.display = tab === 'liked' ? 'block' : 'none';
}

function showSongDetails(song, index) {
    const detailsPanel = document.getElementById('song-details');
    detailsPanel.style.display = 'block';
    document.getElementById('details-empty').style.display = 'none';
    const content = document.getElementById('details-content');
    content.style.display = 'flex';

    window.currentDetailSongId = song.id;
    const coverUrl = song.image_url || 'https://picsum.photos/seed/' + song.id + '/400/400';
    document.getElementById('lib-detail-image').src = coverUrl;
    document.getElementById('lib-detail-title').innerText = song.title;
    document.getElementById('lib-detail-lyrics').innerText = song.lyrics || 'No lyrics available.';
    libRenderTags('lib-detail-tags', song.tags);

    const isComplete = song.status === 'completed' && song.audio_url;

    if (isComplete) {
        libPlaySong(song.id, song.title, coverUrl, song.audio_url);
    }

    localStorage.setItem('lastDetailSong', JSON.stringify({id: song.id, title: song.title, image_url: coverUrl, audio_url: song.audio_url, tags: song.tags || '', lyrics: song.lyrics || ''}));

    document.getElementById('lib-detail-comments-section').style.display = 'none';
    fetch('api/likes.php?action=status&song_id=' + song.id).then(r => r.json()).then(d => {
        if (d.success) libUpdateLikeUI(d.liked, d.count);
    }).catch(() => {});

    document.querySelectorAll('.song-row').forEach(row => {
        row.style.borderColor = '';
        row.style.background = '';
    });
    const activeRow = document.querySelector(`.song-row[data-song-id="${song.id}"]`);
    if (activeRow) {
        activeRow.style.borderColor = 'var(--accent-primary)';
        activeRow.style.background = 'var(--bg-tertiary)';
    }
}

function buildSongRow(song, index) {
    const cover = song.image_url || 'https://picsum.photos/seed/' + song.id + '/400/400';
    const t = song.title || '<?php echo t('untitled'); ?>';
    const tags = song.tags || '<?php echo t('no_tags'); ?>';
    let actions = '';
    if (song.status === 'completed') {
        actions = `<span class="duration" data-audio-url="${escapeHtml(song.audio_url || '')}"></span>
            <button class="action-btn" onclick="event.stopPropagation(); playSong(${song.id}, '${t.replace(/'/g,"\\'")}', '${cover}', '${song.audio_url}')"><i class="fa-solid fa-play"></i></button>
            <div class="song-menu-wrap">
                <button class="action-btn" onclick="event.stopPropagation(); toggleSongMenu(this)"><i class="fa-solid fa-ellipsis"></i></button>
                <div class="song-menu">
                    <a href="api/download.php?id=${song.id}" onclick="event.stopPropagation();" download><i class="fa-solid fa-download"></i> <?php echo t('download'); ?></a>
                    <a href="javascript:void(0)" onclick="event.stopPropagation(); shareSong(${song.id}); closeSongMenu();"><i class="fa-solid fa-share-nodes"></i> <?php echo t('share'); ?></a>
                    <a href="javascript:void(0)" onclick="event.stopPropagation(); deleteSong(${song.id}); closeSongMenu();" style="color:#f87171;"><i class="fa-solid fa-trash"></i> <?php echo t('delete'); ?></a>
                </div>
            </div>`;
    } else if (song.status === 'pending') {
        actions = `<span class="song-elapsed" style="font-size:12px; color:var(--text-secondary);">0s</span>`;
    }
    return `<div class="song-card-list" data-song-id="${song.id}" data-status="${song.status}" data-audio-url="${escapeHtml(song.audio_url || '')}" data-created-at="${song.created_at}" onclick="playSong(${song.id}, '${t.replace(/'/g,"\\'")}', '${cover}', '${song.audio_url}'); showSongDetails(${JSON.stringify(song).replace(/"/g,"&quot;")}, ${index})">
        <div class="song-card-cover" style="background-image: url('${cover}');">
            ${song.status === 'completed' ? '<div class="play-overlay"><i class="fa-solid fa-play"></i></div>' : ''}
        </div>
        <div class="song-card-info">
            <div class="title">${escapeHtml(t)} <span class="version-badge"><?php echo t('ace_version'); ?></span></div>
            <div class="meta">${escapeHtml(tags)}</div>
        </div>
        <div class="song-card-actions">${actions}</div>
    </div>`;
}

async function refreshLibrary() {
    try {
        const res = await fetch('api/get_songs.php?user_only=1');
        const data = await res.json();
        const songs = data.songs || [];
        window._libPlaylist = songs.map(s => ({...s, image_url: s.image_url || 'https://picsum.photos/seed/' + s.id + '/400/400'}));

        const container = document.getElementById('lib-mine-list');
        if (!container) return;
        if (!songs.length) {
            container.innerHTML = '';
            document.getElementById('lib-mine').querySelector('div[style*="text-align: center"]')?.remove();
            document.getElementById('lib-mine').insertAdjacentHTML('afterbegin', '<div style="text-align: center; padding: 80px 0; color: var(--text-secondary);"><i class="fa-solid fa-music" style="font-size: 48px; margin-bottom: 16px; display: block;"></i><p data-t="no_songs"><?php echo t('no_songs'); ?></p></div>');
            return;
        }
        container.innerHTML = songs.map((s, i) => buildSongRow(s, i)).join('');

        // Re-poll pending songs
        songs.filter(s => s.status === 'pending').forEach(s => {
            if (typeof startPendingPoll === 'function') startPendingPoll(s.id, s.title || '<?php echo t('untitled'); ?>', s.created_at);
        });

        loadLibraryDurations();
    } catch(e) {}
}

async function shareSong(id) {
    try {
        const res = await fetch('api/toggle_public.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ song_id: id })
        });
        const data = await res.json();
        if(data.success) {
            showToast(data.is_public ? <?php echo json_encode(t('shared_explore')); ?> : <?php echo json_encode(t('removed_explore')); ?>, 'success');
            refreshLibrary();
        }
    } catch(e) { showToast(<?php echo json_encode(t('failed_sharing')); ?>, 'error'); }
}

async function deleteSong(id) {
    if(!confirm(<?php echo json_encode(t('delete_this_song')); ?>)) return;
    try {
        await fetch(`api/delete_song.php?id=${id}`, { method: 'DELETE' });
        showToast(<?php echo json_encode(t('deleted_song')); ?>, 'success');
        document.getElementById('song-details').style.display = 'none';
        refreshLibrary();
    } catch(e) { showToast(<?php echo json_encode(t('failed_delete_this')); ?>, 'error'); }
}

function libRenderTags(containerId, tagsStr) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    container.classList.remove('expanded');
    const existingBtn = container.parentElement.querySelector('.detail-show-more');
    if (existingBtn) existingBtn.remove();
    if (!tagsStr) return;
    tagsStr.split(',').forEach(tag => {
        tag = tag.trim();
        if (tag) {
            const el = document.createElement('span');
            el.className = 'detail-style-tag';
            el.textContent = tag;
            container.appendChild(el);
        }
    });
    requestAnimationFrame(() => {
        if (container.scrollHeight > 48) {
            const btn = document.createElement('span');
            btn.className = 'detail-show-more';
            btn.textContent = '<?php echo t('show_all'); ?>';
            btn.onclick = function() {
                container.classList.toggle('expanded');
                this.textContent = container.classList.contains('expanded') ? '<?php echo t('show_less'); ?>' : '<?php echo t('show_all'); ?>';
            };
            container.parentElement.insertBefore(btn, container.nextSibling);
        }
    });
}

function libUpdateLikeUI(liked, count) {
    const icon = document.getElementById('lib-detail-heart-icon');
    const countEl = document.getElementById('lib-detail-like-count');
    const likesEl = document.getElementById('lib-detail-likes');
    if (liked) {
        icon.className = 'fa-solid fa-heart';
        likesEl.classList.add('liked');
    } else {
        icon.className = 'fa-regular fa-heart';
        likesEl.classList.remove('liked');
    }
    countEl.textContent = count || 0;
}

async function libToggleLike() {
    if (!window.currentDetailSongId) return;
    try {
        const res = await fetch('api/likes.php?action=toggle&song_id=' + window.currentDetailSongId);
        const data = await res.json();
        if (data.success) libUpdateLikeUI(data.liked, data.count);
    } catch(e) {}
}

function libToggleComments() {
    const section = document.getElementById('lib-detail-comments-section');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
    if (section.style.display === 'block') libLoadComments(window.currentDetailSongId);
}

async function postLibComment() {
    if (!window.currentDetailSongId) return;
    const input = document.getElementById('lib-comment-input');
    const comment = input.value.trim();
    if (!comment) return;
    const parentId = window._libReplyToCommentId || null;
    try {
        const formData = new FormData();
        formData.append('song_id', window.currentDetailSongId);
        formData.append('comment', comment);
        if (parentId) formData.append('parent_id', parentId);
        const res = await fetch('api/likes.php?action=comment', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            input.value = '';
            window._libReplyToCommentId = null;
            const replyInfo = document.getElementById('lib-reply-info');
            if (replyInfo) replyInfo.remove();
            input.placeholder = '<?php echo t('write_comment'); ?>';
            document.getElementById('lib-detail-comment-count').textContent = data.count;
            libLoadComments(window.currentDetailSongId);
        }
    } catch(e) {}
}

function libCancelReply() {
    window._libReplyToCommentId = null;
    const replyInfo = document.getElementById('lib-reply-info');
    if (replyInfo) replyInfo.remove();
    document.getElementById('lib-comment-input').placeholder = '<?php echo t('write_comment'); ?>';
}

function libReplyToComment(commentId, username) {
    window._libReplyToCommentId = commentId;
    const input = document.getElementById('lib-comment-input');
    input.placeholder = '<?php echo t('replying_to'); ?>' + username + '...';
    input.focus();
    let replyInfo = document.getElementById('lib-reply-info');
    if (!replyInfo) {
        replyInfo = document.createElement('div');
        replyInfo.id = 'lib-reply-info';
        replyInfo.style.cssText = 'font-size: 11px; color: var(--accent-primary); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;';
        input.parentElement.insertBefore(replyInfo, input.parentElement.firstChild);
    }
    replyInfo.innerHTML = '<?php echo t('replying_to'); ?> <strong>' + escapeHtml(username) + '</strong> <span style="cursor:pointer; text-decoration: underline;" onclick="libCancelReply()"><?php echo t('cancel'); ?></span>';
}

async function libDeleteComment(commentId) {
    if (!confirm(<?php echo json_encode(t('delete_comment_confirm')); ?>)) return;
    try {
        const formData = new FormData();
        formData.append('comment_id', commentId);
        const res = await fetch('api/likes.php?action=delete_comment', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            document.getElementById('lib-detail-comment-count').textContent = data.count;
            libLoadComments(window.currentDetailSongId);
        }
    } catch(e) {}
}

async function libLoadComments(songId) {
    try {
        const res = await fetch('api/likes.php?action=get_comments&id=' + songId);
        const data = await res.json();
        const container = document.getElementById('lib-detail-comments-list');
        if (!data.success || !data.comments.length) {
            container.innerHTML = '<p style="color: var(--text-muted); font-size: 12px; text-align: center; padding: 12px;"><?php echo t('no_comments'); ?></p>';
            return;
        }
        const myId = data.current_user_id;
        const ownerId = data.owner_id;
        container.innerHTML = data.comments.map(c => {
            const avatar = c.avatar_url || 'assets/images/default-avatar.jpg';
            const time = new Date(c.created_at).toLocaleDateString();
            const canDelete = c.user_id == myId || ownerId == myId;
            const canReply = ownerId == myId;
            let html = '<div style="display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border-color);">' +
                '<img src="' + avatar + '" style="width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;">' +
                '<div style="flex: 1; min-width: 0;">' +
                    '<div style="font-size: 12px; font-weight: 600;">' + escapeHtml(c.username) + ' <span style="color: var(--text-muted); font-weight: 400;">' + time + '</span></div>' +
                    '<div style="font-size: 13px; color: var(--text-primary); margin-top: 2px;">' + escapeHtml(c.comment) + '</div>' +
                    '<div style="display: flex; gap: 12px; margin-top: 4px;">' +
                        (canReply ? '<span style="font-size: 11px; color: var(--text-muted); cursor: pointer;" onclick="libReplyToComment(' + c.id + ', \'' + escapeHtml(c.username).replace(/'/g, "\\'") + '\')"><?php echo t('reply'); ?></span>' : '') +
                        (canDelete ? '<span style="font-size: 11px; color: #f87171; cursor: pointer;" onclick="libDeleteComment(' + c.id + ')"><?php echo t('delete'); ?></span>' : '') +
                    '</div>';
            if (c.replies && c.replies.length) {
                html += '<div style="margin-top: 8px; padding-left: 12px; border-left: 2px solid var(--border-color);">';
                c.replies.forEach(r => {
                    const rAvatar = r.avatar_url || 'assets/images/default-avatar.jpg';
                    const rTime = new Date(r.created_at).toLocaleDateString();
                    const rCanDelete = r.user_id == myId || ownerId == myId;
                    html += '<div style="display: flex; gap: 8px; padding: 6px 0;">' +
                        '<img src="' + rAvatar + '" style="width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;">' +
                        '<div style="flex: 1; min-width: 0;">' +
                            '<div style="font-size: 11px; font-weight: 600;">' + escapeHtml(r.username) + ' <span style="color: var(--text-muted); font-weight: 400;">' + rTime + '</span></div>' +
                            '<div style="font-size: 12px; color: var(--text-primary); margin-top: 1px;">' + escapeHtml(r.comment) + '</div>' +
                            (rCanDelete ? '<span style="font-size: 10px; color: #f87171; cursor: pointer;" onclick="libDeleteComment(' + r.id + ')"><?php echo t('delete'); ?></span>' : '') +
                        '</div></div>';
                });
                html += '</div>';
            }
            html += '</div></div>';
            return html;
        }).join('');
        document.getElementById('lib-detail-comment-count').textContent = data.count;
    } catch(e) {}
}

function loadLibraryDurations() {
    document.querySelectorAll('.song-card-actions .duration[data-audio-url]').forEach(span => {
        if (span.dataset.loaded) return;
        const url = span.dataset.audioUrl;
        if (!url || url.startsWith('task:')) { span.textContent = '--:--'; return; }
        let fullUrl = url;
        if (!url.startsWith('http')) fullUrl = window.location.origin + window.BASE_URL + '/' + url;
        const audio = new Audio();
        audio.preload = 'metadata';
        audio.onloadedmetadata = function() {
            span.dataset.loaded = '1';
            if (!isNaN(audio.duration)) {
                const m = Math.floor(audio.duration / 60);
                const s = Math.floor(audio.duration % 60);
                span.textContent = m + ':' + (s < 10 ? '0' : '') + s;
            } else {
                span.textContent = '--:--';
            }
        };
        audio.onerror = function() { span.textContent = '--:--'; };
        audio.src = fullUrl;
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.id === 'lib-comment-input') {
        postLibComment();
    }
});

function libPlaySong(id, title, imgUrl, audioUrl) {
    if (!audioUrl || audioUrl.startsWith('task:')) {
        showToast('<?php echo t('song_is_generating'); ?>', 'info');
        return;
    }
    if (!audioUrl.startsWith('http')) audioUrl = window.location.origin + window.BASE_URL + '/' + audioUrl;
    const playlist = window._libPlaylist || [];
    const index = playlist.findIndex(s => s.id === id);
    document.querySelectorAll('.song-card-list.now-playing').forEach(el => el.classList.remove('now-playing'));
    const activeCard = document.querySelector('.song-card-list[data-song-id="' + id + '"]');
    if (activeCard) {
        activeCard.classList.add('now-playing');
        const icon = activeCard.querySelector('.play-overlay i');
        if (icon) { icon.classList.remove('fa-play'); icon.classList.add('fa-pause'); }
    }
    if (index >= 0) {
        updatePlayer(audioUrl, title, imgUrl, playlist, index);
    } else {
        const entry = [{id, title, audio_url: audioUrl, image_url: imgUrl}];
        updatePlayer(audioUrl, title, imgUrl, entry, 0);
    }
}

// Restore last viewed song in details panel
(function() {
    try {
        const saved = localStorage.getItem('lastDetailSong');
        if (!saved) return;
        const song = JSON.parse(saved);
        if (!song || !song.id) return;

        document.getElementById('details-empty').style.display = 'none';
        const content = document.getElementById('details-content');
        content.style.display = 'flex';

        const coverUrl = song.image_url || 'https://picsum.photos/seed/' + song.id + '/400/400';
        document.getElementById('lib-detail-image').src = coverUrl;
        document.getElementById('lib-detail-title').innerText = song.title || '<?php echo t('untitled'); ?>';
document.getElementById('lib-detail-lyrics').innerText = song.lyrics || <?php echo json_encode(t('no_lyrics_available')); ?>;
        window.currentDetailSongId = song.id;

        libRenderTags('lib-detail-tags', song.tags);

        fetch('api/likes.php?action=status&song_id=' + song.id).then(r => r.json()).then(d => {
            if (d.success) libUpdateLikeUI(d.liked, d.count);
        }).catch(() => {});
        fetch('api/likes.php?action=get_comments&id=' + song.id).then(r => r.json()).then(d => {
            if (d.success) document.getElementById('lib-detail-comment-count').textContent = d.count || 0;
        }).catch(() => {});

        const activeRow = document.querySelector('.song-row[data-song-id="' + song.id + '"]');
        if (activeRow) {
            activeRow.style.borderColor = 'var(--accent-primary)';
            activeRow.style.background = 'var(--bg-tertiary)';
        }
    } catch(e) {}
})();

loadLibraryDurations();
</script>
