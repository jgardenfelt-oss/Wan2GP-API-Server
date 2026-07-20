<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once 'includes/auth.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user = get_current_user_data();

if ($user && $user['is_banned']) {
    header("Location: logout.php?error=banned");
    exit;
}

$siteName = get_site_setting('site_name', 'Suno');
$siteLogo = get_site_setting('site_logo', '');

$stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'active_skin'");
$activeSkin = $stmt->fetchColumn() ?: 'default';
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $activeSkin)) $activeSkin = 'default';
$skinCssPath = 'skins/' . $activeSkin . '/skin.css';
if (!file_exists(__DIR__ . '/' . $skinCssPath)) $skinCssPath = 'skins/default/skin.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?> - Create Music</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css?v=2">
    <link id="skin-css" rel="stylesheet" href="<?php echo $skinCssPath; ?>?v=2">
</head>
<body>
    <div id="toast-container" style="position: fixed; top: 24px; right: 24px; z-index: 10000; display: flex; flex-direction: column; gap: 12px;"></div>
    <script>
        const T = <?php echo json_encode([
    'is_ready' => t('is_ready'),
    'failed_generate' => t('failed_generate'),
    'generating' => t('generating'),
    'untitled_song' => t('untitled_song'),
    'no_lyrics' => t('no_lyrics'),
    'workspace' => t('workspace'),
    'my_library' => t('my_library'),
    'nav_explore' => t('nav_explore'),
    'nav_admin' => t('nav_admin'),
    'nav_settings' => t('nav_settings'),
    'nav_users' => t('nav_users'),
    'search_results' => t('search_results'),
    'nav_friends' => t('nav_friends'),
    'failed_to_load_page' => t('failed_to_load_page'),
    'check_connection' => t('check_connection'),
    'search_min_chars' => t('search_min_chars'),
    'no_songs_create' => t('no_songs_create'),
    'no_tags' => t('no_tags'),
    'ace_version' => t('ace_version'),
    'download' => t('download'),
    'share' => t('share'),
    'delete' => t('delete'),
    'songs_count' => t('songs_count'),
    'shared_explore' => t('shared_explore'),
    'removed_explore' => t('removed_explore'),
    'failed_sharing' => t('failed_sharing'),
    'failed_delete' => t('failed_delete'),
    'network_error' => t('network_error'),
    'describe_first' => t('describe_first'),
    'generating_lyrics' => t('generating_lyrics'),
    'generating_style' => t('generating_style'),
    'lyrics_generated' => t('lyrics_generated'),
    'style_generated' => t('style_generated'),
    'network_error_try' => t('network_error'),
    'show_all' => t('show_all'),
    'show_less' => t('show_less'),
    'untitled' => t('untitled'),
    'playing_now' => t('playing_now'),
    'paused' => t('paused'),
    'finished' => t('finished'),
    'no_lyrics_available' => t('no_lyrics_available'),
    'loading' => t('loading'),
    'write_comment' => t('write_comment'),
    'replying_to' => t('replying_to'),
    'cancel' => t('cancel'),
    'delete_comment_confirm' => t('delete_comment_confirm'),
    'no_comments' => t('no_comments'),
    'reply' => t('reply'),
    'creating' => t('creating'),
    'create_btn' => t('create_btn'),
    'song_generation_started' => t('song_generation_started'),
    'network_error_please' => t('network_error'),
    'song_is_generating' => t('song_is_generating'),
    'failed_audio' => t('failed_audio'),
    'delete_song_confirm' => t('delete_song_confirm'),
    'select_song_details' => t('select_song_details'),
    'no_lyrics_available2' => t('no_lyrics_available'),
    'default_rules' => t('default_rules'),
    'site_rules_title' => t('site_rules_title'),
    'site_rules_accept' => t('site_rules_accept'),
    'accept_start' => t('accept_start'),
    'branding_updated' => t('branding_updated'),
    'credits_added' => t('credits_added'),
    'confirm_toggle_admin' => t('change_admin_rights'),
    'confirm_toggle_ban' => t('change_ban_status'),
    'rules_updated' => t('rules_updated'),
    'confirm_delete_song' => t('delete_song_perm'),
    'google_api_saved' => t('google_key_saved'),
    'api_url_saved' => t('api_url_saved'),
    'api_auth_saved' => t('api_auth_saved'),
], JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        async function applyTranslations(lang) {
            try {
                const res = await fetch('api/translations.php?lang=' + lang);
                const data = await res.json();
                Object.assign(T, data);
                document.querySelectorAll('[data-t]').forEach(el => {
                    const key = el.getAttribute('data-t');
                    if (data[key] !== undefined) {
                        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                            if (el.placeholder !== undefined && el.hasAttribute('data-t-placeholder')) {
                                el.placeholder = data[key];
                            } else {
                                el.value = data[key];
                            }
                        } else {
                            el.textContent = data[key];
                        }
                    }
                });
                document.querySelectorAll('[data-t-title]').forEach(el => {
                    const key = el.getAttribute('data-t-title');
                    if (data[key] !== undefined) el.title = data[key];
                });
                document.querySelectorAll('[data-t-html]').forEach(el => {
                    const key = el.getAttribute('data-t-html');
                    if (data[key] !== undefined) el.innerHTML = data[key];
                });
            } catch(e) { console.error('Translation error:', e); }
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
            const colors = { success: '#4ade80', error: '#f87171', info: 'var(--accent-primary)' };
            toast.innerHTML = '<i class="fa-solid ' + icons[type] + '" style="color: ' + colors[type] + '; font-size: 18px;"></i><span style="font-size: 14px;">' + message + '</span>';
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        window._pendingSongs = {};
        function formatElapsed(seconds) {
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return m > 0 ? m + 'm ' + s + 's' : s + 's';
        }

        function startPendingPoll(songId, songTitle, startedAt) {
            if (window._pendingSongs[songId]) return;
            window._pendingSongs[songId] = true;
            let failCount = 0;
            const startTime = startedAt ? new Date(startedAt).getTime() : Date.now();

            const elapsedTimer = setInterval(() => {
                const elapsed = Math.floor((Date.now() - startTime) / 1000);
                const el = document.querySelector('[data-song-id="' + songId + '"] .song-elapsed');
                if (el) el.textContent = formatElapsed(elapsed);
                else clearInterval(elapsedTimer);
            }, 1000);
            if (window._activeIntervals) window._activeIntervals.push(elapsedTimer);

            const interval = setInterval(async () => {
                try {
                    const res = await fetch('api/check_status.php?song_id=' + songId);
                    const data = await res.json();
                    if (data.status === 'completed') {
                        clearInterval(interval); clearInterval(elapsedTimer);
                        delete window._pendingSongs[songId];
                        showToast('"' + songTitle + '" ' + T.is_ready, 'success');
                        if (typeof loadWorkspaceSongs === 'function') loadWorkspaceSongs();
                        if (typeof refreshLibrary === 'function') refreshLibrary();
                    } else if (data.status === 'failed') {
                        clearInterval(interval); clearInterval(elapsedTimer);
                        delete window._pendingSongs[songId];
                        showToast('"' + songTitle + '" ' + T.failed_generate, 'error');
                        if (typeof loadWorkspaceSongs === 'function') loadWorkspaceSongs();
                        if (typeof refreshLibrary === 'function') refreshLibrary();
                    } else {
                        const progress = data.progress || 0;
                        const phase = data.phase || '';
                        const progressBar = document.querySelector('[data-song-id="' + songId + '"] .song-progress-fill');
                        if (progressBar) progressBar.style.width = progress + '%';
                        const progressText = document.querySelector('[data-song-id="' + songId + '"] .song-progress-text');
                        if (progressText) progressText.textContent = progress + '%';
                        const phaseEl = document.querySelector('[data-song-id="' + songId + '"] .song-phase');
                        if (phaseEl) phaseEl.textContent = phase || T.generating;
                    }
                } catch (e) {
                    failCount++;
                    if (failCount > 30) {
                        clearInterval(interval); clearInterval(elapsedTimer);
                        delete window._pendingSongs[songId];
                    }
                }
            }, 10000);
            if (window._activeIntervals) window._activeIntervals.push(interval);
        }

        async function checkAllPendingSongs() {
            try {
                const res = await fetch('api/get_pending_songs.php');
                const data = await res.json();
                if (data.success && data.songs) {
                    data.songs.forEach(song => startPendingPoll(song.id, song.title, song.created_at));
                }
            } catch(e) {}
        }
    </script>

    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-area" onclick="loadPage('home')">
                <div class="site-logo">
                    <?php if ($siteLogo): ?>
                        <img src="<?php echo htmlspecialchars($siteLogo); ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fa-solid fa-music"></i>
                    <?php endif; ?>
                </div>
                <span class="logo-text"><?php echo htmlspecialchars($siteName); ?></span>
            </div>

            <div class="sidebar-credits" onclick="loadPage('profile')">
                <div class="credits-left">
                    <i class="fa-solid fa-bolt"></i>
                    <span data-t="unlimited"><?php echo $user['is_admin'] ? t('unlimited') : $user['credits']; ?></span>
                </div>
                <?php if ($user['is_admin']): ?>
                    <span class="credits-admin" data-t="admin_badge"><?php echo t('admin_badge'); ?></span>
                <?php endif; ?>
            </div>

            <nav class="nav-group">
                <a href="javascript:void(0)" class="nav-item active" onclick="loadPage('home')">
                    <i class="fa-solid fa-house"></i> <span data-t="nav_home"><?php echo t('nav_home'); ?></span>
                </a>
                <a href="javascript:void(0)" class="nav-item" onclick="loadPage('explore')">
                    <i class="fa-solid fa-compass"></i> <span data-t="nav_explore"><?php echo t('nav_explore'); ?></span>
                </a>
                <a href="javascript:void(0)" class="nav-item" onclick="loadPage('create')">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> <span data-t="nav_create"><?php echo t('nav_create'); ?></span>
                </a>
                <a href="javascript:void(0)" class="nav-item" onclick="loadPage('library')">
                    <i class="fa-solid fa-book-open"></i> <span data-t="nav_library"><?php echo t('nav_library'); ?></span>
                </a>
                <a href="javascript:void(0)" class="nav-item" onclick="loadPage('search')">
                    <i class="fa-solid fa-magnifying-glass"></i> <span data-t="nav_search"><?php echo t('nav_search'); ?></span>
                </a>
                <a href="javascript:void(0)" class="nav-item" onclick="loadPage('friends')">
                    <i class="fa-solid fa-user-group"></i> <span data-t="nav_friends"><?php echo t('nav_friends'); ?></span>
                </a>
                <a href="javascript:void(0)" class="nav-item" onclick="loadPage('users')">
                    <i class="fa-solid fa-users"></i> <span data-t="nav_users"><?php echo t('nav_users'); ?></span>
                </a>
            </nav>

            <div class="nav-separator"></div>

            <div class="sidebar-footer">
                <a href="javascript:void(0)" class="sidebar-link" onclick="loadPage('profile')">
                    <i class="fa-solid fa-gear"></i> <span data-t="nav_settings"><?php echo t('nav_settings'); ?></span>
                </a>
                <?php if ($user['is_admin']): ?>
                <a href="javascript:void(0)" class="sidebar-link" onclick="loadPage('admin')">
                    <i class="fa-solid fa-shield-halved"></i> <span data-t="nav_admin"><?php echo t('nav_admin'); ?></span>
                </a>
                <?php endif; ?>
            </div>

            <div class="user-profile" onclick="loadPage('profile')">
                <?php $avatar = $user['avatar_url'] ?: 'assets/images/default-avatar.jpg'; ?>
                <div class="user-avatar" style="background-image: url('<?php echo htmlspecialchars($avatar); ?>'); background-size: cover; background-position: center;">
                </div>
                <div class="user-info">
                    <div class="name"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="credits-badge" data-t="credits" style="<?php echo (!$user['is_admin'] && $user['credits'] < 10) ? 'background: rgba(248,113,113,0.12); color: #f87171;' : ''; ?>"><?php echo $user['is_admin'] ? t('unlimited') : ($user['credits'] . ' ' . t('credits')); ?></div>
                </div>
                <a href="logout.php" class="logout-btn" onclick="event.stopPropagation();" title="<?php echo t('nav_logout'); ?>" data-t="nav_logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <!-- CREATE PANEL -->
        <aside class="create-panel" id="create-panel">
            <div class="create-panel-scroll">
            <div class="create-tabs">
                <button class="create-tab active" data-tab="describe" onclick="switchCreateTab('describe')" data-t="create_tab_describe"><?php echo t('create_tab_describe'); ?></button>
                <button class="create-tab" data-tab="custom" onclick="switchCreateTab('custom')" data-t="create_tab_custom"><?php echo t('create_tab_custom'); ?></button>
            </div>

            <!-- Describe Tab -->
            <div id="tab-describe">
                <input type="text" class="title-input" id="cp-title" placeholder="<?php echo t('song_title'); ?>" data-t-placeholder="song_title" value="">

                <div class="create-input-row">
                    <button class="create-input-btn" data-t="audio_btn"><i class="fa-solid fa-microphone"></i> <?php echo t('audio_btn'); ?></button>
                    <button class="create-input-btn" data-t="persona_btn"><i class="fa-solid fa-user"></i> <?php echo t('persona_btn'); ?></button>
                    <button class="create-input-btn" data-t="inspo_btn"><i class="fa-solid fa-lightbulb"></i> <?php echo t('inspo_btn'); ?></button>
                </div>

                <div class="section-header">
                    <h3 data-t="lyrics_section"><i class="fa-solid fa-chevron-down"></i> <?php echo t('lyrics_section'); ?></h3>
                    <div class="section-actions">
                        <button title="Undo"><i class="fa-solid fa-rotate-left"></i></button>
                        <button title="Save"><i class="fa-regular fa-bookmark"></i></button>
                        <button title="Shuffle"><i class="fa-solid fa-shuffle"></i></button>
                        <button class="active" title="AI Generate" onclick="aiGenerate('lyrics')"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                    </div>
                </div>
                <textarea class="prompt-area" id="cp-prompt" placeholder="Acoustic folk with finger-picked guitar, warm cello, and gentle vocal harmonies" style="min-height: 120px;"></textarea>

                <div class="tags-container" id="cp-tags-container">
                    <span class="tag-pill" onclick="toggleTag(this)">r&b</span>
                    <span class="tag-pill" onclick="toggleTag(this)">ambient sounds</span>
                    <span class="tag-pill" onclick="toggleTag(this)">german metal</span>
                    <span class="tag-pill" onclick="toggleTag(this)">lo-fi</span>
                    <span class="tag-pill" onclick="toggleTag(this)">jazz</span>
                    <span class="tag-pill" onclick="toggleTag(this)">classic</span>
                </div>

                <div class="section-header">
                    <h3 data-t="styles_section"><i class="fa-solid fa-chevron-down"></i> <?php echo t('styles_section'); ?></h3>
                    <div class="section-actions">
                        <button title="Undo"><i class="fa-solid fa-rotate-left"></i></button>
                        <button title="Save"><i class="fa-regular fa-bookmark"></i></button>
                        <button title="Shuffle"><i class="fa-solid fa-shuffle"></i></button>
                        <button class="active" title="AI Generate" onclick="aiGenerate('style')"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                    </div>
                </div>
                <textarea class="prompt-area" id="cp-styles" placeholder="Contemporary R&B song at 92 BPM in F minor. Male lead vocals with warm, soulful tone..." style="min-height: 80px;"></textarea>

                <div class="toggle-row" style="margin-top: 12px;">
                    <label class="toggle-switch">
                        <input type="checkbox" id="cp-instrumental">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="toggle-label" data-t="instrumental"><?php echo t('instrumental'); ?></span>
                </div>

                <div class="more-options">
                    <button class="more-options-toggle" onclick="toggleMoreOptions(this)" data-t="more_options">
                        <i class="fa-solid fa-chevron-right"></i> <?php echo t('more_options'); ?>
                    </button>
                    <div class="more-options-content">
                        <div class="musical-params">
                            <div class="param-group">
                                <label data-t="bpm"><?php echo t('bpm'); ?></label>
                                <input type="number" id="cp-bpm" min="40" max="300" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                            </div>
                            <div class="param-group">
                                <label data-t="key"><?php echo t('key'); ?></label>
                                <select id="cp-key">
                                    <option value=""><?php echo t('auto'); ?></option>
                                    <option value="C major">C major</option><option value="C minor">C minor</option>
                                    <option value="C# major">C# major</option><option value="C# minor">C# minor</option>
                                    <option value="D major">D major</option><option value="D minor">D minor</option>
                                    <option value="Eb major">Eb major</option><option value="Eb minor">Eb minor</option>
                                    <option value="E major">E major</option><option value="E minor">E minor</option>
                                    <option value="F major">F major</option><option value="F minor">F minor</option>
                                    <option value="F# major">F# major</option><option value="F# minor">F# minor</option>
                                    <option value="G major">G major</option><option value="G minor">G minor</option>
                                    <option value="Ab major">Ab major</option><option value="Ab minor">Ab minor</option>
                                    <option value="A major">A major</option><option value="A minor">A minor</option>
                                    <option value="Bb major">Bb major</option><option value="Bb minor">Bb minor</option>
                                    <option value="B major">B major</option><option value="B minor">B minor</option>
                                </select>
                            </div>
                            <div class="param-group">
                                <label data-t="time"><?php echo t('time'); ?></label>
                                <select id="cp-time">
                                    <option value=""><?php echo t('auto'); ?></option>
                                    <option value="4/4">4/4</option>
                                    <option value="3/4">3/4</option>
                                    <option value="6/8">6/8</option>
                                    <option value="2/4">2/4</option>
                                    <option value="5/4">5/4</option>
                                    <option value="7/8">7/8</option>
                                </select>
                            </div>
                        </div>
                        <div class="musical-params" style="margin-top: 12px;">
                            <div class="param-group">
                                <label data-t="guidance"><?php echo t('guidance'); ?></label>
                                <input type="number" id="cp-guidance" min="0" max="20" step="0.1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                            </div>
                            <div class="param-group">
                                <label data-t="temperature"><?php echo t('temperature'); ?></label>
                                <input type="number" id="cp-temperature" min="0.1" max="1.5" step="0.1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                            </div>
                            <div class="param-group">
                                <label data-t="top_p"><?php echo t('top_p'); ?></label>
                                <input type="number" id="cp-top-p" min="0" max="1" step="0.01" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                            </div>
                            <div class="param-group">
                                <label data-t="top_k"><?php echo t('top_k'); ?></label>
                                <input type="number" id="cp-top-k" min="0" max="100" step="1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                            </div>
                        </div>
                        <div class="musical-params" style="margin-top: 8px;">
                            <div class="param-group">
                                <label data-t="source_audio_strength"><?php echo t('source_audio_strength'); ?></label>
                                <input type="number" id="cp-source-audio" min="0" max="1" step="0.1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                            </div>
                        </div>
                    </div>
                </div>

                <p style="text-align: center; font-size: 12px; color: var(--text-muted); margin-bottom: 16px;"><span data-t="want_custom_lyrics"><?php echo t('want_custom_lyrics'); ?></span> <a href="javascript:void(0)" onclick="switchCreateTab('custom')" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;" data-t="use_custom_mode"><?php echo t('use_custom_mode'); ?></a></p>
            </div>

            <!-- Custom Tab -->
            <div id="tab-custom" style="display: none;">
                <input type="text" class="title-input" id="cp-title-custom" placeholder="<?php echo t('song_title'); ?>" data-t-placeholder="song_title" value="">
                <textarea class="prompt-area" id="cp-custom-prompt" placeholder="<?php echo t('describe_placeholder'); ?>" data-t-placeholder="describe_placeholder" style="min-height: 60px; margin-bottom: 12px;"></textarea>
                <textarea class="prompt-area" id="cp-lyrics" placeholder="[Verse 1]&#10;Enter your lyrics here...&#10;&#10;[Chorus]&#10;Add your chorus..." style="min-height: 160px;"></textarea>
                <div class="toggle-row" style="margin-top: 12px;">
                    <label class="toggle-switch">
                        <input type="checkbox" id="cp-custom-instrumental">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="toggle-label" data-t="instrumental"><?php echo t('instrumental'); ?></span>
                </div>
                <div class="musical-params">
                    <div class="param-group">
                        <label data-t="bpm"><?php echo t('bpm'); ?></label>
                        <input type="number" id="cp-custom-bpm" min="40" max="300" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                    </div>
                    <div class="param-group">
                        <label data-t="key"><?php echo t('key'); ?></label>
                        <select id="cp-custom-key">
                            <option value=""><?php echo t('auto'); ?></option>
                            <option value="C major">C major</option><option value="C minor">C minor</option>
                            <option value="C# major">C# major</option><option value="C# minor">C# minor</option>
                            <option value="D major">D major</option><option value="D minor">D minor</option>
                            <option value="Eb major">Eb major</option><option value="Eb minor">Eb minor</option>
                            <option value="E major">E major</option><option value="E minor">E minor</option>
                            <option value="F major">F major</option><option value="F minor">F minor</option>
                            <option value="F# major">F# major</option><option value="F# minor">F# minor</option>
                            <option value="G major">G major</option><option value="G minor">G minor</option>
                            <option value="Ab major">Ab major</option><option value="Ab minor">Ab minor</option>
                            <option value="A major">A major</option><option value="A minor">A minor</option>
                            <option value="Bb major">Bb major</option><option value="Bb minor">Bb minor</option>
                            <option value="B major">B major</option><option value="B minor">B minor</option>
                        </select>
                    </div>
                    <div class="param-group">
                        <label data-t="time"><?php echo t('time'); ?></label>
                        <select id="cp-custom-time">
                            <option value=""><?php echo t('auto'); ?></option>
                            <option value="4/4">4/4</option>
                            <option value="3/4">3/4</option>
                            <option value="6/8">6/8</option>
                            <option value="2/4">2/4</option>
                            <option value="5/4">5/4</option>
                            <option value="7/8">7/8</option>
                        </select>
                    </div>
                </div>
                <div class="musical-params" style="margin-top: 12px;">
                    <div class="param-group">
                        <label data-t="guidance"><?php echo t('guidance'); ?></label>
                        <input type="number" id="cp-custom-guidance" min="0" max="20" step="0.1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                    </div>
                    <div class="param-group">
                        <label data-t="temperature"><?php echo t('temperature'); ?></label>
                        <input type="number" id="cp-custom-temperature" min="0.1" max="1.5" step="0.1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                    </div>
                    <div class="param-group">
                        <label data-t="top_p"><?php echo t('top_p'); ?></label>
                        <input type="number" id="cp-custom-top-p" min="0" max="1" step="0.01" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                    </div>
                    <div class="param-group">
                        <label data-t="top_k"><?php echo t('top_k'); ?></label>
                        <input type="number" id="cp-custom-top-k" min="0" max="100" step="1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                    </div>
                </div>
                <div class="musical-params" style="margin-top: 8px;">
                    <div class="param-group">
                        <label data-t="source_audio_strength"><?php echo t('source_audio_strength'); ?></label>
                        <input type="number" id="cp-custom-source-audio" min="0" max="1" step="0.1" value="" placeholder="<?php echo t('auto'); ?>" data-t-placeholder="auto">
                    </div>
                </div>
            </div>
            </div>

            <button class="create-btn" id="cp-create-btn" onclick="createSong()">
                <i class="fa-solid fa-wand-magic-sparkles"></i> <span data-t="create_btn"><?php echo t('create_btn'); ?></span>
            </button>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content" id="main-view">
            <header class="content-header">
                <div>
                    <h1 id="content-title" data-t="workspace"><?php echo t('workspace'); ?></h1>
                </div>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass" style="color: var(--text-secondary);"></i>
                    <input type="text" id="global-search" data-t-placeholder="search_placeholder" placeholder="<?php echo t('search_placeholder'); ?>" onkeydown="if(event.key==='Enter') doGlobalSearch()">
                </div>
            </header>
            <div id="content-body"></div>
        </main>

        <!-- SONG DETAIL PANEL -->
        <div class="song-detail-panel" id="song-detail-panel">
            <button class="detail-close" onclick="closeDetailPanel()"><i class="fa-solid fa-xmark"></i></button>
            <div id="detail-content">
                <div class="detail-cover">
                    <img id="detail-cover-img" src="" alt="">
                </div>
                <div class="detail-play-actions">
                    <div class="detail-stat" id="detail-likes" onclick="toggleLike()" style="cursor:pointer;">
                        <i class="fa-regular fa-heart" id="detail-heart-icon"></i> <span id="detail-like-count">0</span>
                    </div>
                    <div class="detail-stat" onclick="toggleComments()" style="cursor:pointer;">
                        <i class="fa-regular fa-comment"></i> <span id="detail-comment-count">0</span>
                    </div>
                </div>
                <h2 class="detail-title" id="detail-title"></h2>
                <p class="detail-username" id="detail-username"></p>
                <p class="detail-caption" data-t="add_caption"><?php echo t('add_caption'); ?></p>
                <button class="detail-remix-btn" id="detail-remix-btn" data-t="remix_edit">
                    <i class="fa-solid fa-music"></i> <?php echo t('remix_edit'); ?>
                </button>
                <div class="detail-style-tags" id="detail-tags"></div>
                <div class="detail-lyrics">
                    <div class="lyrics-text" id="detail-lyrics"></div>
                </div>

                <div id="detail-comments-section" style="margin-top: 16px; display: none;">
                    <h4 data-t="comments" style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-secondary); margin-bottom: 12px; font-weight: 600;"><?php echo t('comments'); ?></h4>
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <input type="text" id="comment-input" data-t-placeholder="write_comment" placeholder="<?php echo t('write_comment'); ?>" style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 99px; padding: 10px 16px; color: white; font-size: 13px; outline: none; font-family: var(--font-main);">
                        <button class="btn-primary" data-t="post" style="padding: 10px 16px; border-radius: 99px; font-size: 12px;" onclick="postComment()"><?php echo t('post'); ?></button>
                    </div>
                    <div id="detail-comments-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>

        <!-- PLAYER BAR -->
        <footer class="player-bar">
            <div class="player-track-info">
                <div class="track-img"></div>
                <div class="track-meta">
                    <h4 data-t="no_track_selected"><?php echo t('no_track_selected'); ?></h4>
                    <p data-t="select_song_play"><?php echo t('select_song_play'); ?></p>
                </div>
            </div>
            <div class="player-controls">
                <div class="control-btns">
                    <i class="fa-solid fa-shuffle" style="font-size: 14px; color: var(--text-secondary); cursor: pointer;"></i>
                    <i id="prev-btn" class="fa-solid fa-backward-step" style="cursor: pointer;"></i>
                    <div class="play-btn"><i class="fa-solid fa-play"></i></div>
                    <i id="next-btn" class="fa-solid fa-forward-step" style="cursor: pointer;"></i>
                    <i class="fa-solid fa-repeat" style="font-size: 14px; color: var(--text-secondary); cursor: pointer;"></i>
                </div>
                <div class="progress-container">
                    <span id="current-time">0:00</span>
                    <div class="progress-bar" id="progress-bar-container">
                        <div class="progress-fill"></div>
                    </div>
                    <span id="duration-time">0:00</span>
                </div>
            </div>
            <div class="player-actions">
                <i class="fa-solid fa-volume-high" id="volume-icon" style="cursor: pointer; width: 20px;"></i>
                <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.7" style="width: 80px; accent-color: white; cursor: pointer;">
            </div>
        </footer>
    </div>

    <!-- SITE RULES MODAL -->
    <?php if (!$user['rules_accepted']):
        $rules_text = get_site_setting('site_rules', 'Please follow the site rules.');
    ?>
    <div id="rules-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(12px);">
        <div style="background: var(--bg-secondary); padding: 48px; border-radius: 40px; max-width: 700px; width: 95%; border: 1px solid var(--border-color); box-shadow: 0 30px 60px rgba(0,0,0,0.6); display: flex; flex-direction: column; max-height: 85vh;">
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                <div style="width: 48px; height: 48px; background: var(--accent-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white;">
                    <i class="fa-solid fa-gavel"></i>
                </div>
                <div>
                    <h2 style="font-size: 28px; font-weight: 800; line-height: 1;" data-t="site_rules_title"><?php echo t('site_rules_title'); ?></h2>
                    <p style="color: var(--text-secondary); font-size: 14px; margin-top: 4px;" data-t="accept_start"><?php echo t('accept_start'); ?></p>
                </div>
            </div>
            <div class="custom-scrollbar" data-t="default_rules" style="background: var(--bg-tertiary); padding: 32px; border-radius: 24px; margin-bottom: 32px; font-size: 16px; line-height: 1.8; color: var(--text-primary); overflow-y: auto; flex: 1; white-space: pre-wrap;">
                <?php echo htmlspecialchars($rules_text); ?>
            </div>
            <button class="btn-primary" data-t="site_rules_accept" style="width: 100%; padding: 20px; font-size: 18px; border-radius: 20px; font-weight: 700;" onclick="acceptRules()"><?php echo t('site_rules_accept'); ?></button>
        </div>
    </div>
    <script>
    async function acceptRules() {
        const res = await fetch('api/accept_rules.php', { method: 'POST' });
        const data = await res.json();
        if(data.success) document.getElementById('rules-modal').style.display = 'none';
    }
    </script>
    <?php endif; ?>

    <script>
        const _origFetch = window.fetch;
        window.fetch = function(url, opts = {}) {
            opts.credentials = 'include';
            return _origFetch(url, opts);
        };

        window.BASE_URL = '<?php echo '/' . ltrim(BASE_URL, '/'); ?>';
        window._activeIntervals = [];
        function clearAllIntervals() {
            window._activeIntervals.forEach(id => clearInterval(id));
            window._activeIntervals = [];
            window._pendingSongs = {};
        }

        async function loadPage(page) {
            const contentBody = document.getElementById('content-body');
            const headerTitle = document.getElementById('content-title');
            clearAllIntervals();

            const createPanel = document.getElementById('create-panel');
            const appContainer = document.querySelector('.app-container');
            const detailPanel = document.getElementById('song-detail-panel');
            detailPanel.classList.remove('open');

            if (page === 'create' || page === 'library') {
                appContainer.classList.remove('panel-hidden');
                createPanel.style.display = '';
            } else {
                appContainer.classList.add('panel-hidden');
                createPanel.style.display = 'none';
            }

            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('onclick') && item.getAttribute('onclick').includes("'" + page + "'")) item.classList.add('active');
            });

            if (page === 'create') {
                headerTitle.textContent = T.workspace;
                contentBody.innerHTML = '<div id="workspace-songs"></div>';
                loadWorkspaceSongs();
                return;
            }

            const titles = { home: T.workspace, library: T.my_library, explore: T.nav_explore, admin: T.nav_admin, profile: T.nav_settings, users: T.nav_users, search: T.search_results, friends: T.nav_friends };
            headerTitle.textContent = titles[page.split('&')[0]] || 'Users';

            contentBody.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 300px;"><div class="loader"></div></div>';

            try {
                let url = '';
                if (page === 'library') url = 'includes/library_view.php';
                else if (page === 'explore') url = 'includes/explore_view.php';
                else if (page === 'admin') url = 'includes/admin_view.php';
                else if (page === 'profile') url = 'includes/profile_view.php';
                else if (page.startsWith('user_profile')) url = 'includes/user_profile_view.php' + '?' + page.split('&').slice(1).join('&');
                else if (page.startsWith('users')) url = 'includes/users_view.php' + '?' + page.split('&').slice(1).join('&');
                else if (page.startsWith('search')) url = 'includes/search_view.php' + '?' + page.split('&').slice(1).join('&');
                else if (page === 'friends') url = 'includes/friends_view.php';
                else url = 'includes/home_view.php';

                const response = await fetch(url);
                const html = await response.text();
                contentBody.innerHTML = html;

                const scripts = contentBody.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });

                checkAllPendingSongs();
            } catch (err) {
                contentBody.innerHTML = '<div style="text-align: center; padding: 100px;"><h2>' + T.failed_to_load_page + '</h2><p>' + T.check_connection + '</p></div>';
            }
        }

        function doGlobalSearch() {
            const q = document.getElementById('global-search').value.trim();
            if (q.length < 2) { showToast(T.search_min_chars, 'info'); return; }
            loadPage('search&q=' + encodeURIComponent(q));
        }

        async function loadWorkspaceSongs() {
            if (window._loadingWorkspace) return;
            window._loadingWorkspace = true;
            try {
                const res = await fetch('api/get_songs.php?user_only=1');
                const data = await res.json();
                const songs = data.songs || [];
                const container = document.getElementById('workspace-songs');
                if (!container) { window._loadingWorkspace = false; return; }
                window._libPlaylist = songs.filter(s => s.status === 'completed' && s.audio_url).map(s => ({id: s.id, title: s.title, audio_url: s.audio_url, image_url: s.image_url || 'https://picsum.photos/seed/' + s.id + '/400/400'}));
                if (!songs.length) {
                    container.innerHTML = '<div style="text-align: center; padding: 80px 0; color: var(--text-secondary);"><i class="fa-solid fa-music" style="font-size: 48px; margin-bottom: 16px; display: block;"></i><p>' + T.no_songs_create + '</p></div>';
                    return;
                }
                container.innerHTML = '<div class="workspace-songs">' + songs.map(song => {
                    const cover = song.image_url || 'https://picsum.photos/seed/' + song.id + '/400/400';
                    return '<div class="song-card-list" data-song-id="' + song.id + '" data-status="' + song.status + '" data-audio-url="' + escapeHtml(song.audio_url || '') + '" data-title="' + escapeHtml(song.title) + '" data-cover="' + escapeHtml(cover) + '">' +
                        '<div class="song-card-cover" style="background-image: url(\'' + cover + '\');">' +
                            (song.status === 'completed' ? '<div class="play-overlay"><i class="fa-solid fa-play"></i></div>' : '') +
                        '</div>' +
                        '<div class="song-card-info">' +
                            '<div class="title">' + escapeHtml(song.title) + ' <span class="version-badge">' + T.ace_version + '</span></div>' +
                            '<div class="meta">' + escapeHtml(song.tags || T.no_tags) + '</div>' +
                        '</div>' +
                        '<div class="song-card-actions">' +
                            (song.status === 'completed' ? '<span class="duration" data-audio-url="' + escapeHtml(song.audio_url || '') + '"></span>' : '') +
                            (song.status === 'completed' ? '<button class="action-btn play-btn-small"><i class="fa-solid fa-play"></i></button>' : '') +
                            (song.status === 'pending' ? '<span class="song-elapsed" style="font-size:12px; color:var(--text-secondary);">0s</span>' : '') +
                            (song.status === 'failed' ? '<button class="action-btn delete-btn-small" style="color:#f87171;"><i class="fa-solid fa-trash"></i></button>' : '') +
                            (song.status === 'completed' ? '<div class="song-menu-wrap"><button class="action-btn menu-btn-small"><i class="fa-solid fa-ellipsis"></i></button><div class="song-menu"><a href="api/download.php?id=' + song.id + '" onclick="event.stopPropagation();" download><i class="fa-solid fa-download"></i> ' + T.download + '</a><a href="javascript:void(0)" class="share-btn-small"><i class="fa-solid fa-share-nodes"></i> ' + T.share + '</a><a href="javascript:void(0)" class="delete-btn-menu-small" style="color:#f87171;"><i class="fa-solid fa-trash"></i> ' + T.delete + '</a></div></div>' : '') +
                        '</div>' +
                    '</div>';
                }).join('') + '</div>' +
                '<div class="workspace-count">' + songs.length + ' ' + T.songs_count + '</div>';

                if (songs.some(s => s.status === 'pending')) {
                    songs.filter(s => s.status === 'pending').forEach(s => startPendingPoll(s.id, s.title, s.created_at));
                }

                loadWorkspaceDurations();
            } catch(e) { console.error('loadWorkspaceSongs error:', e); }
            window._loadingWorkspace = false;
        }

        function loadWorkspaceDurations() {
            document.querySelectorAll('#workspace-songs .song-card-actions .duration[data-audio-url]').forEach(span => {
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

        async function deleteFailedSong(songId) {
            try {
                const res = await fetch('api/delete_song.php?id=' + songId);
                const data = await res.json();
                if (data.success) loadWorkspaceSongs();
                else showToast(data.error || T.failed_delete, 'error');
            } catch(e) { showToast(T.network_error, 'error'); }
        }

        async function shareSong(id) {
            try {
                const res = await fetch('api/toggle_public.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ song_id: id })
                });
                const data = await res.json();
                if(data.success) showToast(data.is_public ? T.shared_explore : T.removed_explore, 'success');
            } catch(e) { showToast(T.failed_sharing, 'error'); }
        }

        function switchCreateTab(tab) {
            document.querySelectorAll('.create-tab').forEach(t => t.classList.remove('active'));
            document.querySelector('.create-tab[data-tab="' + tab + '"]').classList.add('active');
            document.getElementById('tab-describe').style.display = tab === 'describe' ? 'block' : 'none';
            document.getElementById('tab-custom').style.display = tab === 'custom' ? 'block' : 'none';
        }

        function toggleTag(el) { el.classList.toggle('selected'); }

        async function aiGenerate(type) {
            const prompt = document.getElementById('cp-prompt').value.trim();
            const title = document.getElementById('cp-title').value.trim();
            if (!prompt) { showToast(T.describe_first, 'error'); return; }

            const activeTab = document.querySelector('.create-tab.active').dataset.tab;
            const targetId = type === 'lyrics' ? 'cp-prompt' : 'cp-styles';
            const targetEl = document.getElementById(targetId);
            const origPlaceholder = targetEl.placeholder;
            targetEl.placeholder = type === 'lyrics' ? T.generating_lyrics : T.generating_style;
            targetEl.disabled = true;

            try {
                const res = await fetch('api/ai_generate.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type, prompt, title })
                });
                const data = await res.json();
                if (data.error) { showToast(data.error, 'error'); return; }
                targetEl.value = data.text;
                showToast(type === 'lyrics' ? T.lyrics_generated : T.style_generated, 'success');
            } catch(e) {
                showToast(T.network_error_please, 'error');
            } finally {
                targetEl.placeholder = origPlaceholder;
                targetEl.disabled = false;
            }
        }

        document.getElementById('content-body').addEventListener('click', function(e) {
            const card = e.target.closest('.song-card-list');
            if (!card) return;
            const songId = parseInt(card.dataset.songId);
            const status = card.dataset.status;

            if (e.target.closest('.delete-btn-small')) {
                e.stopPropagation();
                deleteFailedSong(songId);
                return;
            }
            if (e.target.closest('.delete-btn-menu-small')) {
                e.stopPropagation();
                deleteFailedSong(songId);
                closeSongMenu();
                return;
            }
            if (e.target.closest('.share-btn-small')) {
                e.stopPropagation();
                shareSong(songId);
                closeSongMenu();
                return;
            }
            if (e.target.closest('.menu-btn-small')) {
                e.stopPropagation();
                toggleSongMenu(e.target.closest('.menu-btn-small'));
                return;
            }
            if (e.target.closest('.play-btn-small')) {
                e.stopPropagation();
                if (status === 'completed') {
                    playSong(songId, card.dataset.title, card.dataset.cover, card.dataset.audioUrl);
                }
                return;
            }

            if (status === 'completed') {
                playSong(songId, card.dataset.title, card.dataset.cover, card.dataset.audioUrl);
            } else {
                openSongDetail(songId);
            }
        });

        function toggleSongMenu(btn) {
            closeSongMenu();
            const menu = btn.nextElementSibling;
            menu.classList.toggle('open');
        }

        function closeSongMenu() {
            document.querySelectorAll('.song-menu.open').forEach(m => m.classList.remove('open'));
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.song-menu-wrap')) closeSongMenu();
        });

        function toggleMoreOptions(btn) {
            btn.classList.toggle('open');
            const content = btn.nextElementSibling;
            content.classList.toggle('open');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.innerText = text;
            return div.innerHTML;
        }

        function escapeJs(text) {
            return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
        }

        function renderTags(containerId, tagsStr) {
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
                    btn.textContent = T.show_all;
                    btn.onclick = function() {
                        container.classList.toggle('expanded');
                        this.textContent = container.classList.contains('expanded') ? T.show_less : T.show_all;
                    };
                    container.parentElement.insertBefore(btn, container.nextSibling);
                }
            });
        }

        async function createSong() {
            const activeTab = document.querySelector('.create-tab.active').dataset.tab;
            let prompt, lyrics, instrumental, title, bpm, musicalKey, timeSig;
            let guidance_scale, temperature, top_p, top_k, source_audio_strength;

            if (activeTab === 'describe') {
                title = document.getElementById('cp-title').value.trim() || T.untitled_song;
                const selectedTags = Array.from(document.querySelectorAll('.tag-pill.selected')).map(t => t.textContent);
                prompt = document.getElementById('cp-prompt').value.trim();
                const styles = document.getElementById('cp-styles').value.trim();
                if (styles) prompt = prompt ? prompt + '\n' + styles : styles;
                if (selectedTags.length) prompt = prompt ? prompt + '\nTags: ' + selectedTags.join(', ') : 'Tags: ' + selectedTags.join(', ');
                lyrics = '';
                instrumental = document.getElementById('cp-instrumental').checked;
                bpm = document.getElementById('cp-bpm').value || '';
                musicalKey = document.getElementById('cp-key').value || '';
                timeSig = document.getElementById('cp-time').value || '';
                guidance_scale = document.getElementById('cp-guidance').value || '';
                temperature = document.getElementById('cp-temperature').value || '';
                top_p = document.getElementById('cp-top-p').value || '';
                top_k = document.getElementById('cp-top-k').value || '';
                source_audio_strength = document.getElementById('cp-source-audio').value || '';
            } else {
                title = document.getElementById('cp-title-custom').value.trim() || T.untitled_song;
                prompt = document.getElementById('cp-custom-prompt').value.trim();
                lyrics = document.getElementById('cp-lyrics').value.trim();
                instrumental = document.getElementById('cp-custom-instrumental').checked;
                bpm = document.getElementById('cp-custom-bpm').value || '';
                musicalKey = document.getElementById('cp-custom-key').value || '';
                timeSig = document.getElementById('cp-custom-time').value || '';
                guidance_scale = document.getElementById('cp-custom-guidance').value || '';
                temperature = document.getElementById('cp-custom-temperature').value || '';
                top_p = document.getElementById('cp-custom-top-p').value || '';
                top_k = document.getElementById('cp-custom-top-k').value || '';
                source_audio_strength = document.getElementById('cp-custom-source-audio').value || '';
            }

            if (!prompt && !lyrics) { showToast('Please describe your song or enter lyrics', 'error'); return; }

            const btn = document.getElementById('cp-create-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + T.creating;

            try {
                const res = await fetch('api/generate.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title, prompt, lyrics, instrumental, bpm, key: musicalKey, time_signature: timeSig, guidance_scale, temperature, top_p, top_k, source_audio_strength, tags: Array.from(document.querySelectorAll('.tag-pill.selected')).map(t => t.textContent) })
                });
                const data = await res.json();
                if (data.error) { showToast(data.error, 'error'); return; }
                showToast('"' + data.title + '" is generating!', 'success');
                if (data.pending && data.pending.length) {
                    data.pending.forEach(s => startPendingPoll(s.id, s.title, s.created_at));
                }
                loadWorkspaceSongs();
                if (typeof refreshLibrary === 'function') refreshLibrary();
            } catch(e) {
                showToast(T.network_error_please, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> ' + T.create_btn;
            }
        }

        // SONG DETAIL PANEL
        let currentDetailSongId = null;

        async function openSongDetail(songId) {
            try {
                const res = await fetch('api/get_song.php?id=' + songId);
                const data = await res.json();
                if (!data.success) return;
                const song = data.song;
                const panel = document.getElementById('song-detail-panel');
                const coverUrl = song.image_url || 'https://picsum.photos/seed/' + songId + '/400/400';

                currentDetailSongId = songId;
                document.getElementById('detail-cover-img').src = coverUrl;
                document.getElementById('detail-title').textContent = song.title;
                document.getElementById('detail-username').textContent = song.username || '';
                document.getElementById('detail-lyrics').textContent = song.lyrics || T.no_lyrics_available;

                // Like state
                updateLikeUI(song.liked, song.like_count);
                document.getElementById('detail-comment-count').textContent = song.comment_count || 0;

                // Tags
                renderTags('detail-tags', song.tags);

                // Load comments
                loadComments(songId);

                localStorage.setItem('lastDetailSong', JSON.stringify({id: songId, title: song.title, image_url: coverUrl, audio_url: song.audio_url, tags: song.tags || '', lyrics: song.lyrics || '', username: song.username || '', liked: song.liked, like_count: song.like_count, comment_count: song.comment_count}));

                panel.classList.add('open');
            } catch(e) {}
        }

        function closeDetailPanel() {
            document.getElementById('song-detail-panel').classList.remove('open');
        }

        function updateLikeUI(liked, count) {
            const icon = document.getElementById('detail-heart-icon');
            const countEl = document.getElementById('detail-like-count');
            const likesEl = document.getElementById('detail-likes');
            if (liked) {
                icon.className = 'fa-solid fa-heart';
                likesEl.classList.add('liked');
            } else {
                icon.className = 'fa-regular fa-heart';
                likesEl.classList.remove('liked');
            }
            countEl.textContent = count || 0;
        }

        async function toggleLike() {
            if (!currentDetailSongId) return;
            try {
                const res = await fetch('api/likes.php?action=toggle&song_id=' + currentDetailSongId);
                const data = await res.json();
                if (data.success) updateLikeUI(data.liked, data.count);
            } catch(e) {}
        }

        function toggleComments() {
            const section = document.getElementById('detail-comments-section');
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }

        async function postComment() {
            if (!currentDetailSongId) return;
            const input = document.getElementById('comment-input');
            const comment = input.value.trim();
            if (!comment) return;
            const parentId = window._replyToCommentId || null;

            try {
                const formData = new FormData();
                formData.append('song_id', currentDetailSongId);
                formData.append('comment', comment);
                if (parentId) formData.append('parent_id', parentId);
                const res = await fetch('api/likes.php?action=comment', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    input.value = '';
                    window._replyToCommentId = null;
                    const replyInfo = document.getElementById('reply-info');
                    if (replyInfo) replyInfo.remove();
                    input.placeholder = T.write_comment;
                    document.getElementById('detail-comment-count').textContent = data.count;
                    loadComments(currentDetailSongId);
                }
            } catch(e) {}
        }

        function cancelReply() {
            window._replyToCommentId = null;
            const replyInfo = document.getElementById('reply-info');
            if (replyInfo) replyInfo.remove();
            document.getElementById('comment-input').placeholder = T.write_comment;
        }

        function replyToComment(commentId, username) {
            window._replyToCommentId = commentId;
            const input = document.getElementById('comment-input');
            input.placeholder = T.replying_to + ' ' + username + '...';
            input.focus();
            let replyInfo = document.getElementById('reply-info');
            if (!replyInfo) {
                replyInfo = document.createElement('div');
                replyInfo.id = 'reply-info';
                replyInfo.style.cssText = 'font-size: 11px; color: var(--accent-primary); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;';
                input.parentElement.insertBefore(replyInfo, input.parentElement.firstChild);
            }
            replyInfo.innerHTML = T.replying_to + ' <strong>' + escapeHtml(username) + '</strong> <span style="cursor:pointer; text-decoration: underline;" onclick="cancelReply()">' + T.cancel + '</span>';
        }

        async function deleteComment(commentId) {
            if (!confirm(T.delete_comment_confirm)) return;
            try {
                const formData = new FormData();
                formData.append('comment_id', commentId);
                const res = await fetch('api/likes.php?action=delete_comment', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('detail-comment-count').textContent = data.count;
                    loadComments(currentDetailSongId);
                }
            } catch(e) {}
        }

        async function loadComments(songId) {
            try {
                const res = await fetch('api/likes.php?action=get_comments&id=' + songId);
                const data = await res.json();
                const container = document.getElementById('detail-comments-list');
                if (!data.success || !data.comments.length) {
                    container.innerHTML = '<p style="color: var(--text-muted); font-size: 12px; text-align: center; padding: 12px;">' + T.no_comments + '</p>';
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
                        '<div style="width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; overflow: hidden; background: var(--bg-tertiary);">' +
                            '<img src="' + avatar + '" style="width:100%;height:100%;object-fit:cover;">' +
                        '</div>' +
                        '<div style="flex: 1; min-width: 0;">' +
                            '<div style="font-size: 12px; font-weight: 600;">' + escapeHtml(c.username) + ' <span style="color: var(--text-muted); font-weight: 400;">' + time + '</span></div>' +
                            '<div style="font-size: 13px; color: var(--text-secondary); margin-top: 2px;">' + escapeHtml(c.comment) + '</div>' +
                            '<div style="display: flex; gap: 12px; margin-top: 4px;">' +
                                (canReply ? '<span style="font-size: 11px; color: var(--text-muted); cursor: pointer;" onclick="replyToComment(' + c.id + ', \'' + escapeHtml(c.username).replace(/'/g, "\\'") + '\')">' + T.reply + '</span>' : '') +
                                (canDelete ? '<span style="font-size: 11px; color: #f87171; cursor: pointer;" onclick="deleteComment(' + c.id + ')">' + T.delete + '</span>' : '') +
                            '</div>';
                    if (c.replies && c.replies.length) {
                        html += '<div style="margin-top: 8px; padding-left: 12px; border-left: 2px solid var(--border-color);">';
                        c.replies.forEach(r => {
                            const rAvatar = r.avatar_url || 'assets/images/default-avatar.jpg';
                            const rTime = new Date(r.created_at).toLocaleDateString();
                            const rCanDelete = r.user_id == myId || ownerId == myId;
                            html += '<div style="display: flex; gap: 8px; padding: 6px 0;">' +
                                '<div style="width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; overflow: hidden; background: var(--bg-tertiary);">' +
                                    '<img src="' + rAvatar + '" style="width:100%;height:100%;object-fit:cover;">' +
                                '</div>' +
                                '<div style="flex: 1; min-width: 0;">' +
                                    '<div style="font-size: 11px; font-weight: 600;">' + escapeHtml(r.username) + ' <span style="color: var(--text-muted); font-weight: 400;">' + rTime + '</span></div>' +
                                    '<div style="font-size: 12px; color: var(--text-secondary); margin-top: 1px;">' + escapeHtml(r.comment) + '</div>' +
                                    (rCanDelete ? '<span style="font-size: 10px; color: #f87171; cursor: pointer;" onclick="deleteComment(' + r.id + ')">' + T.delete + '</span>' : '') +
                                '</div></div>';
                        });
                        html += '</div>';
                    }
                    html += '</div></div>';
                    return html;
                }).join('');
                document.getElementById('detail-comment-count').textContent = data.count;
            } catch(e) {}
        }

        // Handle Enter key in comment input
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.id === 'comment-input') {
                postComment();
            }
        });

        // PLAYER LOGIC
        const audio = new Audio();
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const source = audioCtx.createMediaElementSource(audio);
        const bassFilter = audioCtx.createBiquadFilter();
        bassFilter.type = 'lowshelf';
        bassFilter.frequency.value = 200;
        bassFilter.gain.value = -6;
        const gainNode = audioCtx.createGain();
        gainNode.gain.value = 2.5;
        source.connect(bassFilter);
        bassFilter.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        let isPlaying = false;
        let currentPlaylist = [];
        let currentIndex = -1;

        function savePlayerState() {
            if (currentIndex >= 0 && currentPlaylist[currentIndex]) {
                const song = currentPlaylist[currentIndex];
                localStorage.setItem('playerState', JSON.stringify({
                    songId: song.id, audio_url: song.audio_url, title: song.title,
                    image_url: song.image_url, currentTime: audio.currentTime,
                    volume: audio.volume, index: currentIndex
                }));
            }
        }

        function updatePlayer(url, title, img, playlist = [], index = -1) {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            if (url && !url.startsWith('http') && !url.startsWith('data:')) {
                url = window.location.origin + window.BASE_URL + '/' + url;
            }
            audio.src = url;
            document.querySelector('.track-meta h4').innerText = title || T.untitled;
            document.querySelector('.track-meta p').innerText = T.playing_now;
            const coverImg = img || 'assets/images/default-avatar.jpg';
            const trackImgEl = document.querySelector('.track-img');
            trackImgEl.style.backgroundImage = 'url("' + coverImg + '")';
            trackImgEl.style.backgroundSize = 'cover';
            trackImgEl.style.backgroundPosition = 'center';

            const songId = playlist[index] ? playlist[index].id : null;
            document.querySelectorAll('.song-card-list.now-playing').forEach(el => el.classList.remove('now-playing'));
            document.querySelectorAll('.song-row.now-playing').forEach(el => el.classList.remove('now-playing'));
            if (songId) {
                const activeCard = document.querySelector('.song-card-list[data-song-id="' + songId + '"]');
                if (activeCard) {
                    activeCard.classList.add('now-playing');
                    const icon = activeCard.querySelector('.play-overlay i');
                    if (icon) { icon.classList.remove('fa-play'); icon.classList.add('fa-pause'); }
                }
            }

            if (playlist.length > 0) {
                currentPlaylist = playlist;
                currentIndex = index;
            } else if (index === -1) {
                const existingIndex = currentPlaylist.findIndex(s => s.audio_url === url);
                if (existingIndex !== -1) currentIndex = existingIndex;
                else {
                    currentPlaylist.push({ id: Date.now(), audio_url: url, title: title, image_url: img });
                    currentIndex = currentPlaylist.length - 1;
                }
            }
            savePlayerState();

            const playingSongId = currentPlaylist[currentIndex] ? currentPlaylist[currentIndex].id : null;
            if (playingSongId) {
                updateDetailPanel(playingSongId, title, img);
            }

            if (audio.paused) togglePlay();
            else audio.play().catch(() => {});
        }

        function updateDetailPanel(songId, title, imgUrl) {
            const panel = document.getElementById('song-detail-panel');
            currentDetailSongId = songId;
            document.getElementById('detail-cover-img').src = imgUrl || 'assets/images/default-avatar.jpg';
            document.getElementById('detail-title').textContent = title;
            document.getElementById('detail-lyrics').textContent = T.loading;
            document.getElementById('detail-username').textContent = '';
            updateLikeUI(false, 0);
            document.getElementById('detail-comment-count').textContent = '0';
            renderTags('detail-tags', '');
            panel.classList.add('open');
            loadComments(songId);
            fetch('api/get_song.php?id=' + songId).then(r => r.json()).then(d => {
                if (!d.success) return;
                const song = d.song;
                document.getElementById('detail-username').textContent = song.username || '';
                document.getElementById('detail-lyrics').textContent = song.lyrics || T.no_lyrics_available;
                renderTags('detail-tags', song.tags);
                updateLikeUI(song.liked, song.like_count);
                document.getElementById('detail-comment-count').textContent = song.comment_count || 0;
                localStorage.setItem('lastDetailSong', JSON.stringify({id: songId, title: song.title, image_url: imgUrl, audio_url: song.audio_url, tags: song.tags || '', lyrics: song.lyrics || '', username: song.username || '', liked: song.liked, like_count: song.like_count, comment_count: song.comment_count}));
            }).catch(() => {});
        }

        window.playSong = function(id, title, imgUrl, audioUrl) {
            if (!audioUrl || audioUrl.startsWith('task:')) {
                showToast(T.song_is_generating, 'info');
                return;
            }
            if (!audioUrl.startsWith('http') && !audioUrl.startsWith('data:')) {
                audioUrl = window.location.origin + window.BASE_URL + '/' + audioUrl;
            }
            const playlist = window._libPlaylist || [];
            const index = playlist.findIndex(s => s.id === id);
            if (index >= 0) {
                updatePlayer(audioUrl, title, imgUrl, playlist, index);
            } else {
                const entry = [{id, title, audio_url: audioUrl, image_url: imgUrl}];
                updatePlayer(audioUrl, title, imgUrl, entry, 0);
            }
        };

        audio.onended = () => {
            const card = document.querySelector('.song-card-list.now-playing');
            if (card) {
                card.classList.remove('now-playing');
                const icon = card.querySelector('.play-overlay i');
                if (icon) { icon.classList.replace('fa-pause', 'fa-play'); }
            }
            playNext();
        };
        audio.onerror = () => { showToast(T.failed_audio, 'error'); };

        audio.ontimeupdate = () => {
            if (Math.floor(audio.currentTime) % 2 === 0) savePlayerState();
            const progress = (audio.currentTime / audio.duration) * 100;
            document.querySelector('.progress-fill').style.width = progress + '%';
            document.getElementById('current-time').innerText = formatTime(audio.currentTime);
            if (!isNaN(audio.duration)) document.getElementById('duration-time').innerText = formatTime(audio.duration);
        };

        function playNext() {
            if (currentPlaylist.length === 0) return;
            const nextIndex = currentIndex + 1;
            if (nextIndex < currentPlaylist.length) {
                const nextSong = currentPlaylist[nextIndex];
                updatePlayer(nextSong.audio_url, nextSong.title, nextSong.image_url, currentPlaylist, nextIndex);
            } else {
                document.querySelector('.track-meta p').innerText = T.finished;
                savePlayerState();
            }
        }

        function playPrevious() {
            if (audio.currentTime > 3) { audio.currentTime = 0; audio.play().catch(() => {}); return; }
            if (currentIndex > 0) {
                const prevSong = currentPlaylist[currentIndex - 1];
                updatePlayer(prevSong.audio_url, prevSong.title, prevSong.image_url, currentPlaylist, currentIndex - 1);
            }
        }

        document.getElementById('next-btn').onclick = playNext;
        document.getElementById('prev-btn').onclick = playPrevious;

        const volumeSlider = document.getElementById('volume-slider');
        const volumeIcon = document.getElementById('volume-icon');
        audio.volume = volumeSlider.value;
        volumeSlider.oninput = (e) => {
            const val = e.target.value;
            audio.volume = val;
            if (audioCtx.state === 'suspended') audioCtx.resume();
            if (val == 0) volumeIcon.className = 'fa-solid fa-volume-xmark';
            else if (val < 0.5) volumeIcon.className = 'fa-solid fa-volume-low';
            else volumeIcon.className = 'fa-solid fa-volume-high';
        };

        function togglePlay() {
            const btn = document.querySelector('.play-btn i');
            const trackStatus = document.querySelector('.track-meta p');
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const cardOverlay = document.querySelector('.song-card-list.now-playing .play-overlay i');
            if (audio.paused) {
                audio.play().catch(() => {});
                btn.classList.replace('fa-play', 'fa-pause');
                if (cardOverlay) { cardOverlay.classList.replace('fa-play', 'fa-pause'); cardOverlay.classList.replace('fa-play', 'fa-pause'); }
                if (trackStatus) trackStatus.innerText = T.playing_now;
            } else {
                audio.pause();
                btn.classList.replace('fa-pause', 'fa-play');
                if (cardOverlay) { cardOverlay.classList.replace('fa-pause', 'fa-play'); }
                if (trackStatus) trackStatus.innerText = T.paused;
                savePlayerState();
            }
        }

        document.querySelector('.play-btn').onclick = togglePlay;

        document.getElementById('progress-bar-container').onclick = (e) => {
            const rect = document.getElementById('progress-bar-container').getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            if (!isNaN(audio.duration)) audio.currentTime = pos * audio.duration;
        };

        function formatTime(secs) {
            const mins = Math.floor(secs / 60);
            const s = Math.floor(secs % 60);
            return mins + ':' + s.toString().padStart(2, '0');
        }

        // Restore player state on page load
        (function() {
            try {
                const ps = JSON.parse(localStorage.getItem('playerState'));
                if (!ps || !ps.audio_url) return;
                let audioUrl = ps.audio_url;
                if (!audioUrl.startsWith('http') && !audioUrl.startsWith('data:')) {
                    audioUrl = window.location.origin + window.BASE_URL + '/' + audioUrl;
                }
                audio.src = audioUrl;
                audio.currentTime = ps.currentTime || 0;
                audio.volume = ps.volume || 0.5;
                document.getElementById('volume-slider').value = ps.volume || 0.5;
                document.querySelector('.track-meta h4').innerText = ps.title || T.untitled;
                document.querySelector('.track-meta p').innerText = T.paused;
                const coverImg = ps.image_url || 'assets/images/default-cover.jpg';
                const trackImgEl = document.querySelector('.track-img');
                trackImgEl.style.backgroundImage = 'url("' + coverImg + '")';
                trackImgEl.style.backgroundSize = 'cover';
                trackImgEl.style.backgroundPosition = 'center';
                document.getElementById('current-time').innerText = formatTime(ps.currentTime || 0);
                currentPlaylist = [{id: ps.songId, audio_url: ps.audio_url, title: ps.title, image_url: ps.image_url}];
                currentIndex = 0;
            } catch(e) {}
        })();

        // Restore last detail panel song (only if a song was playing)
        (function() {
            try {
                const ps = localStorage.getItem('playerState');
                if (!ps) return;
                const saved = localStorage.getItem('lastDetailSong');
                if (!saved) return;
                const song = JSON.parse(saved);
                if (!song || !song.id) return;
                const panel = document.getElementById('song-detail-panel');
                currentDetailSongId = song.id;
                document.getElementById('detail-cover-img').src = song.image_url || 'assets/images/default-avatar.jpg';
                document.getElementById('detail-title').textContent = song.title;
                document.getElementById('detail-username').textContent = song.username || '';
                document.getElementById('detail-lyrics').textContent = song.lyrics || T.no_lyrics_available;
                updateLikeUI(song.liked, song.like_count);
                document.getElementById('detail-comment-count').textContent = song.comment_count || 0;
                renderTags('detail-tags', song.tags);
                panel.classList.add('open');
                loadComments(song.id);
            } catch(e) {}
        })();

        // Initial load
        loadPage('home');
    </script>
</body>
</html>
