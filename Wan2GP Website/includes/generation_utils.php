<?php
/**
 * WanGP / ACE-Step 1.5 Integration Utilities
 * Uses the WanGP API server (configurable via admin panel)
 */

define('ACE_STEP_MODEL', 'ace_step_v1_5_turbo_lm_1_7b');

/**
 * Get the WanGP API server URL from database
 */
function get_api_base_url() {
    global $pdo;
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'api_server_url'");
    $url = $stmt->fetchColumn() ?: '';
    if (empty($url)) {
        $url = 'https://127.0.0.1:8001';
    }
    return rtrim($url, '/');
}

/**
 * Duration is now auto-determined by the LM (model_mode=4).
 * This function is kept for backward compatibility but is no longer used.
 */

/**
 * Make a request to the WanGP API
 */
function get_api_server_auth() {
    global $pdo;
    $user = '';
    $pass = '';
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'api_server_user'");
    $user = $stmt->fetchColumn() ?: '';
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'api_server_pass'");
    $pass = $stmt->fetchColumn() ?: '';
    return ['user' => $user, 'pass' => $pass];
}

function wgp_request($endpoint, $method = 'GET', $payload = null, $timeout = 30) {
    $url = get_api_base_url() . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Add Basic Auth if API server is not localhost
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';
    if ($host !== '127.0.0.1' && $host !== 'localhost') {
        $auth = get_api_server_auth();
        if (!empty($auth['user'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth['user'] . ':' . $auth['pass']);
        }
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) return ['error' => "API Connection Error: $error"];
    if ($http_code >= 400) return ['error' => "API returned HTTP $http_code: $response"];
    
    $result = json_decode($response, true);
    if ($result === null) return ['error' => "Invalid JSON response: $response"];
    
    return $result;
}

/**
 * Submit task to WanGP API (POST /create_task)
 * Uses ACE-Step 1.5 model for music generation
 * 
 * WanGP ACE-Step field mapping:
 *   prompt       = lyrics (with section tags like [Verse], [Chorus])
 *   alt_prompt   = style/tags description (e.g. "pop, male vocals, upbeat")
 *   model_mode   = 4 (LM auto-determines best duration from lyrics & caption)
 */
function submit_acestep_task($describe_prompt, $style_description, $title, $lyrics, $bpm = '', $key = '', $time_signature = '', $guidance_scale = '', $temperature = '', $top_p = '', $top_k = '', $source_audio_strength = '') {
    // alt_prompt = style/tags description for WanGP
    $alt_prompt = !empty($style_description) ? $style_description : ($describe_prompt ?: 'pop, vocal');
    
    $payload = [
        "prompt" => $lyrics,
        "alt_prompt" => $alt_prompt,
        "model_type" => ACE_STEP_MODEL,
        "model_mode" => 4,
        "num_inference_steps" => 8
    ];

    if (!empty($bpm)) $payload["bpm"] = intval($bpm);
    if (!empty($key)) $payload["keyscale"] = $key;
    if (!empty($time_signature)) {
        $ts = intval(explode('/', $time_signature)[0]);
        if (in_array($ts, [2, 3, 4, 6])) $payload["time_signature"] = $ts;
    }
    if (!empty($guidance_scale)) $payload["guidance_scale"] = floatval($guidance_scale);
    if (!empty($temperature)) $payload["temperature"] = floatval($temperature);
    if (!empty($top_p)) $payload["top_p"] = floatval($top_p);
    if (!empty($top_k)) $payload["top_k"] = intval($top_k);
    if (!empty($source_audio_strength)) $payload["source_audio_strength"] = floatval($source_audio_strength);

    $result = wgp_request('/create_task', 'POST', $payload, 300);
    
    if (isset($result['error'])) return $result;
    if (!isset($result['task_id'])) return ['error' => "Failed to get task_id. Response: " . json_encode($result)];

    return [
        'success' => true,
        'task_id' => $result['task_id']
    ];
}

/**
 * Query task status from WanGP API (GET /task_status/{task_id})
 * When completed, returns a download URL via /get_result/{task_id}
 */
function query_acestep_task($task_id) {
    $result = wgp_request('/task_status/' . $task_id, 'GET');
    
    if (isset($result['error']) && !empty($result['error'])) return $result;
    
    $status = $result['status'] ?? '';
    
    if ($status === 'completed') {
        // WanGP returns local Windows paths in output_files, which can't be fetched via HTTP.
        // Instead, use /get_result/{task_id} which returns the actual file content.
        $audio_url = get_api_base_url() . '/get_result/' . $task_id;
        
        return [
            'completed' => true,
            'audio_url' => $audio_url,
            'image_url' => '',
            'raw_data' => $result
        ];
    } elseif ($status === 'failed') {
        return ['error' => "Generation failed: " . ($result['error'] ?? 'Unknown error'), 'raw_data' => $result];
    }
    
    // Still running (queued, running, etc.)
    return ['completed' => false, 'progress' => $result['progress'] ?? 0, 'raw_data' => $result];
}

/**
 * Get the Google API key from database
 */
function get_google_api_key() {
    global $pdo;
    $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'google_api_key'");
    return $stmt->fetchColumn() ?: '';
}

/**
 * Generate full song with Gemini: lyrics + production style
 */
function generate_full_song_with_gemini($description, $title = '') {
    $api_key = get_google_api_key();
    if (empty($api_key)) {
        return ['error' => 'No Google API key configured'];
    }

    $prompt = "You are a professional music producer and songwriter. A user wants you to create a full song from a brief description.\n\n";
    if ($title) {
        $prompt .= "Song title: $title\n";
    }
    $prompt .= "User description: $description\n\n";
    $prompt .= "IMPORTANT: The description may include specific musical parameters like BPM, key, or time signature. You MUST respect these exactly in your output.\n\n";
    $prompt .= "Create a complete song package. Respond in this EXACT format:\n\n";
    $prompt .= "[STYLE]\n";
    $prompt .= "A detailed music production description for the AI music generator. Include: genre, tempo (match exact BPM if specified), mood, key instruments, vocal style (male/female, soft/powerful/raspy), energy level, key (match exact key if specified), time signature (match if specified), and any specific production elements. Be descriptive and specific.\n\n";
    $prompt .= "[LYRICS]\n";
    $prompt .= "Complete song lyrics with section tags like [Verse 1], [Chorus], [Verse 2], [Bridge], [Outro]. Make the chorus catchy and memorable. Write at least 2 verses and a chorus. Lyrics should match the style and mood described above.\n\n";
    $prompt .= "IMPORTANT: Use the exact [STYLE] and [LYRICS] tags. Do not add any other text outside these sections.";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.9,
            "maxOutputTokens" => 4096
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return ['error' => "Gemini API connection error: $error"];
    }
    if ($http_code >= 400) {
        return ['error' => "Gemini API returned HTTP $http_code: $response"];
    }

    $result = json_decode($response, true);
    if ($result === null) {
        return ['error' => "Invalid response from Gemini API"];
    }

    if (isset($result['error'])) {
        return ['error' => "Gemini API error: " . ($result['error']['message'] ?? json_encode($result['error']))];
    }

    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (empty($text)) {
        return ['error' => "Gemini returned empty response"];
    }

    $text = trim($text);
    $text = preg_replace('/^```[a-z]*\n?/i', '', $text);
    $text = preg_replace('/\n?```$/', '', $text);
    $text = trim($text);

    $style = '';
    $lyrics = '';

    if (preg_match('/\[STYLE\]\s*\n(.+?)(?=\n\[LYRICS\])/s', $text, $m)) {
        $style = trim($m[1]);
    }
    if (preg_match('/\[LYRICS\]\s*\n(.+)/s', $text, $m)) {
        $lyrics = trim($m[1]);
    }

    if (empty($lyrics)) {
        return ['error' => "Gemini did not return lyrics"];
    }
    if (empty($style)) {
        $style = $description;
    }

    return ['success' => true, 'lyrics' => $lyrics, 'style' => $style];
}
?>
