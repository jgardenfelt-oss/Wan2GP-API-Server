<?php
require_once '../includes/auth.php';
require_once '../includes/generation_utils.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$title = $data['title'] ?? 'Untitled Song';
$instrumental = $data['instrumental'] ?? false;
$bpm = trim($data['bpm'] ?? '');
$key = trim($data['key'] ?? '');
$time_signature = trim($data['time_signature'] ?? '');

$guidance_scale = $data['guidance_scale'] ?? '';
$temperature = $data['temperature'] ?? '';
$top_p = $data['top_p'] ?? '';
$top_k = $data['top_k'] ?? '';
$source_audio_strength = $data['source_audio_strength'] ?? '';

// Describe tab sends: prompt (description) + tags (selected pills)
// Custom tab sends: prompt (style/mood) + lyrics (user lyrics) + tags (empty)
$describe_prompt = $data['prompt'] ?? '';
$lyrics = $data['lyrics'] ?? '';
$tags = is_array($data['tags']) ? implode(', ', $data['tags']) : ($data['tags'] ?? '');

// Build the prompt description (what the model uses as alt_prompt / style)
$prompt_description = $describe_prompt;
if (!empty($tags)) {
    $prompt_description = $prompt_description ? $prompt_description . ', ' . $tags : $tags;
}

// Append musical parameters to description
$musical_params = [];
if (!empty($bpm)) $musical_params[] = "$bpm BPM";
if (!empty($key)) $musical_params[] = "key of $key";
if (!empty($time_signature)) $musical_params[] = "$time_signature time";
if (!empty($musical_params)) {
    $prompt_description = $prompt_description ? $prompt_description . ', ' . implode(', ', $musical_params) : implode(', ', $musical_params);
}

if (empty($prompt_description) && empty($lyrics)) {
    echo json_encode(['error' => 'Please describe your song or enter lyrics']);
    exit;
}

// Describe mode: Gemini creates the full song (lyrics + style)
if (empty($lyrics) && !empty($prompt_description)) {
    $gemini = generate_full_song_with_gemini($prompt_description, $title);
    if (isset($gemini['success'])) {
        $lyrics = $gemini['lyrics'];
        $prompt_description = $gemini['style'];
    } else {
        $lyrics = "[Instrumental] " . $prompt_description;
    }
}

// Check credits
$user = get_current_user_data();
$is_admin = (bool)($user['is_admin'] ?? false);

if (!$is_admin && $user['credits'] < 10) {
    $last_update = strtotime($user['last_credit_update']);
    $next_refresh = $last_update + 86400;
    $hours_left = max(0, ceil(($next_refresh - time()) / 3600));
    $time_msg = $hours_left > 0 ? "Refreshes in {$hours_left}h." : 'Refreshes soon.';
    echo json_encode(['error' => "No credits left. {$time_msg} You get 50 credits every 24 hours."]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Deduct 10 credits ONLY for non-admins
    if (!$is_admin) {
        $stmt = $pdo->prepare("UPDATE users SET credits = credits - 10 WHERE id = ?");
        $stmt->execute([$user['id']]);
    }

    // Insert pending song (default private)
    $stmt = $pdo->prepare("INSERT INTO songs (user_id, title, lyrics, tags, status, is_public) VALUES (?, ?, ?, ?, 'pending', 0)");
    $stmt->execute([$user['id'], $title, $lyrics, $prompt_description]);
    $song_id = $pdo->lastInsertId();

    $pdo->commit();

    // Call WanGP API — use full prompt_description as style, not just tag pills
    $api_result = submit_acestep_task($describe_prompt, $prompt_description, $title, $lyrics, $bpm, $key, $time_signature, $guidance_scale, $temperature, $top_p, $top_k, $source_audio_strength);

    if (isset($api_result['success'])) {
        // Update song with task_id
        $stmt = $pdo->prepare("UPDATE songs SET task_id = ? WHERE id = ?");
        $stmt->execute([
            $api_result['task_id'],
            $song_id
        ]);

        echo json_encode([
            'success' => true,
            'title' => $title,
            'message' => 'Generation started',
            'song_id' => $song_id,
            'task_id' => $api_result['task_id'],
            'remaining_credits' => $user['credits'] - 10,
            'pending' => [['id' => $song_id, 'title' => $title, 'created_at' => date('c')]]
        ]);
    } else {
        // Mark as failed
        $stmt = $pdo->prepare("UPDATE songs SET status = 'failed' WHERE id = ?");
        $stmt->execute([$song_id]);

        echo json_encode(['error' => 'ACE-Step Error: ' . $api_result['error']]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>