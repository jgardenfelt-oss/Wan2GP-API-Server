<?php
/**
 * Polling endpoint for ACE-Step 1.5 tasks
 */
require_once '../includes/auth.php';
require_once '../includes/generation_utils.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$song_id = $_GET['song_id'] ?? null;

if (!$song_id) {
    echo json_encode(['error' => 'Song ID required']);
    exit;
}

try {
    // Get task_id and created_at for this song
    $stmt = $pdo->prepare("SELECT task_id, status, created_at FROM songs WHERE id = ? AND user_id = ?");
    $stmt->execute([$song_id, $user_id]);
    $song = $stmt->fetch();

    if (!$song) {
        echo json_encode(['error' => 'Song not found']);
        exit;
    }

    if ($song['status'] === 'completed') {
        echo json_encode(['status' => 'completed', 'audio_url' => $song['audio_url'] ?? '']);
        exit;
    }

    if ($song['status'] === 'failed') {
        // Recover if local file actually exists
        $local_filename = "uploads/song_" . $song_id . ".mp3";
        $full_path = dirname(__DIR__) . "/" . $local_filename;
        if (file_exists($full_path) && filesize($full_path) > 1000) {
            $stmt = $pdo->prepare("UPDATE songs SET audio_url = ?, status = 'completed' WHERE id = ?");
            $stmt->execute([$local_filename, $song_id]);
            echo json_encode(['status' => 'completed', 'audio_url' => $local_filename]);
        } else {
            echo json_encode(['status' => 'failed']);
        }
        exit;
    }

    // Query ACE-Step API
    $api_result = query_acestep_task($song['task_id']);

    if (isset($api_result['error']) && !empty($api_result['error'])) {
        $stmt = $pdo->prepare("UPDATE songs SET status = 'failed' WHERE id = ?");
        $stmt->execute([$song_id]);
        echo json_encode(['status' => 'failed', 'error' => $api_result['error']]);
    } elseif (!empty($api_result['completed'])) {
        $local_filename = "uploads/song_" . $song_id . ".mp3";
        $full_path = dirname(__DIR__) . "/" . $local_filename;

        // Check if file already exists locally
        if (file_exists($full_path) && filesize($full_path) > 1000) {
            $final_audio_url = $local_filename;

            $stmt = $pdo->prepare("UPDATE songs SET audio_url = ?, image_url = ?, status = 'completed' WHERE id = ?");
            $stmt->execute([$final_audio_url, $api_result['image_url'] ?? '', $song_id]);
            echo json_encode(['status' => 'completed', 'audio_url' => $final_audio_url]);
        } else {
            // Try to download from WanGP
            $audio_url = $api_result['audio_url'];
            if (!is_dir(dirname($full_path))) mkdir(dirname($full_path), 0755, true);

            $ch = curl_init($audio_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $parsedAudio = parse_url($audio_url);
            $audioHost = $parsedAudio['host'] ?? '';
            if ($audioHost !== '127.0.0.1' && $audioHost !== 'localhost') {
                $auth = get_api_server_auth();
                if (!empty($auth['user'])) {
                    curl_setopt($ch, CURLOPT_USERPWD, $auth['user'] . ':' . $auth['pass']);
                }
            }

            $audio_content = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($audio_content && $http_code == 200 && strlen($audio_content) > 1000) {
                file_put_contents($full_path, $audio_content);
                $final_audio_url = $local_filename;

                $stmt = $pdo->prepare("UPDATE songs SET audio_url = ?, image_url = ?, status = 'completed' WHERE id = ?");
                $stmt->execute([$final_audio_url, $api_result['image_url'] ?? '', $song_id]);
                echo json_encode(['status' => 'completed', 'audio_url' => $final_audio_url]);
            } else {
                $errorMsg = "Audio download failed";
                if ($curl_error) $errorMsg .= ": $curl_error";
                elseif ($http_code != 200) $errorMsg .= ": HTTP $http_code";

                $stmt = $pdo->prepare("UPDATE songs SET status = 'failed' WHERE id = ?");
                $stmt->execute([$song_id]);
                echo json_encode(['status' => 'failed', 'error' => $errorMsg]);
            }
        }
    } else {
        echo json_encode([
            'status' => 'pending',
            'progress' => $api_result['progress'] ?? 0,
            'phase' => $api_result['raw_data']['phase'] ?? '',
            'started_at' => $song['created_at']
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error']);
}
?>
