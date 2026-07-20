<?php
require_once __DIR__ . '/auth.php';

$user = get_current_user_data();
if (!$user) {
    echo '<div data-t="please_login" style="padding: 24px;">' . t('please_login') . '</div>';
    exit;
}

$avatar = $user['avatar_url'] ?: 'assets/images/default-avatar.jpg';
?>
<div class="profile-container" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - var(--player-height) - 40px); padding: 20px;">
    <div style="width: 100%; max-width: 480px; background: var(--bg-secondary); padding: 48px 40px; border-radius: 28px; border: 1px solid var(--border-color); box-shadow: 0 8px 32px rgba(0,0,0,0.2);">

        <div style="text-align: center; margin-bottom: 36px;">
            <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; margin: 0 auto 16px; border: 3px solid var(--accent-primary); position: relative; cursor: pointer;">
                <img id="profile-preview" src="<?php echo $avatar; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <label for="avatar-input" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.2s; color: white; font-size: 14px; cursor: pointer;">
                    <i class="fa-solid fa-camera"></i>
                </label>
                <input type="file" id="avatar-input" style="display: none;" accept="image/*" onchange="previewAvatar(this)">
            </div>
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 2px;"><?php echo htmlspecialchars($user['username']); ?></h3>
            <p data-t="account_settings" style="font-size: 13px; color: var(--text-muted);"><?php echo t('account_settings'); ?></p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label data-t="username" style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600;"><?php echo t('username'); ?></label>
                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: var(--text-secondary); cursor: not-allowed; font-size: 14px; font-family: var(--font-main);">
            </div>

            <div>
                <label data-t="email" style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600;"><?php echo t('email'); ?></label>
                <input type="email" id="profile-email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="your@email.com" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; font-family: var(--font-main); outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--accent-primary)'" onblur="this.style.borderColor='var(--border-color)'">
            </div>

            <div style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 4px;">
                <label data-t="new_password" style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600;"><?php echo t('new_password'); ?></label>
                <input type="password" id="new-password" data-t-placeholder="leave_blank_keep" placeholder="<?php echo t('leave_blank_keep'); ?>" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; font-family: var(--font-main); outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--accent-primary)'" onblur="this.style.borderColor='var(--border-color)'">
            </div>

            <div>
                <label data-t="confirm_password" style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary); margin-bottom: 8px; font-weight: 600;"><?php echo t('confirm_password'); ?></label>
                <input type="password" id="confirm-password" data-t-placeholder="confirm_new_pass" placeholder="<?php echo t('confirm_new_pass'); ?>" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 16px; color: white; font-size: 14px; font-family: var(--font-main); outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--accent-primary)'" onblur="this.style.borderColor='var(--border-color)'">
            </div>

            <button class="btn-primary" data-t="save_changes" onclick="saveProfile()" style="padding: 14px; font-size: 15px; font-weight: 600; border-radius: 14px; margin-top: 8px; width: 100%;"><?php echo t('save_changes'); ?></button>
            <div id="profile-msg" style="font-size: 14px; text-align: center;"></div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

async function saveProfile() {
    const email = document.getElementById('profile-email').value.trim();
    const password = document.getElementById('new-password').value;
    const confirm = document.getElementById('confirm-password').value;
    const avatarFile = document.getElementById('avatar-input').files[0];
    const msg = document.getElementById('profile-msg');

    if (password && password !== confirm) {
        msg.innerHTML = '<span style="color: #f87171;"><?php echo t('passwords_no_match'); ?></span>';
        return;
    }

    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        msg.innerHTML = '<span style="color: #f87171;"><?php echo t('valid_email'); ?></span>';
        return;
    }

    const formData = new FormData();
    if (email) formData.append('email', email);
    if (password) formData.append('password', password);
    if (avatarFile) formData.append('avatar', avatarFile);

    msg.innerHTML = '<span style="color: var(--text-secondary);"><?php echo t('saving'); ?></span>';

    try {
        const res = await fetch('api/update_profile.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            msg.innerHTML = '<span style="color: #10b981;"><?php echo t('profile_updated'); ?></span>';
            if (data.avatar_url) {
                const sidebarAvatar = document.querySelector('.user-avatar');
                if (sidebarAvatar) {
                    sidebarAvatar.style.backgroundImage = `url('${data.avatar_url}')`;
                    sidebarAvatar.style.backgroundSize = 'cover';
                    sidebarAvatar.style.backgroundPosition = 'center';
                    sidebarAvatar.textContent = '';
                }
            }
        } else {
            msg.innerHTML = `<span style="color: #f87171;">${data.error}</span>`;
        }
    } catch (e) {
        msg.innerHTML = '<span style="color: #f87171;"><?php echo t('error_occurred'); ?></span>';
    }
}
</script>
