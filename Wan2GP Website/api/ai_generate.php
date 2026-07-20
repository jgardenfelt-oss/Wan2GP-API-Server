<?php
require_once '../includes/auth.php';
require_once '../includes/generation_utils.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$prompt = trim($data['prompt'] ?? '');
$title = trim($data['title'] ?? '');

if (empty($prompt)) {
    echo json_encode(['error' => 'Please enter a description first']);
    exit;
}

$api_key = get_google_api_key();
if (empty($api_key)) {
    echo json_encode(['error' => 'AI generation is not configured on this site']);
    exit;
}

if ($type === 'lyrics') {
    $sys_prompt = "You are a professional songwriter. Write complete song lyrics based on this description.\n\n";
    if ($title) $sys_prompt .= "Song title: $title\n";
    $sys_prompt .= "Description: $prompt\n\n";
    $sys_prompt .= "Write the full lyrics with section tags like [Verse 1], [Chorus], [Verse 2], [Bridge], [Outro]. Make it catchy and memorable. Write at least 2 verses and a chorus. Only return the lyrics, no extra text.";
} elseif ($type === 'style') {
    $sys_prompt = "You are a professional music producer. Create a detailed production style description for an AI music generator based on this input.\n\n";
    if ($title) $sys_prompt .= "Song title: $title\n";
    $sys_prompt .= "Description: $prompt\n\n";
    $sys_prompt .= "Include: genre, tempo/BPM, mood, key instruments, vocal style, energy level, key, time signature, and production elements. Be descriptive and specific. Only return the style description, no extra text.";
} else {
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;
$payload = [
    "contents" => [["parts" => [["text" => $sys_prompt]]]],
    "generationConfig" => ["temperature" => 0.9, "maxOutputTokens" => 2048]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    echo json_encode(['error' => 'AI connection error: ' . $curlError]);
    exit;
}
if ($http_code >= 400) {
    $result = json_decode($response, true);
    $msg = $result['error']['message'] ?? ("HTTP $http_code");
    echo json_encode(['error' => 'AI API error: ' . $msg]);
    exit;
}

$result = json_decode($response, true);
if ($result === null) {
    echo json_encode(['error' => 'Invalid response from AI service']);
    exit;
}
if (isset($result['error'])) {
    echo json_encode(['error' => 'AI error: ' . ($result['error']['message'] ?? json_encode($result['error']))]);
    exit;
}

$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
if (empty($text)) {
    echo json_encode(['error' => 'AI returned an empty response']);
    exit;
}

$text = trim($text);
$text = preg_replace('/^```[a-z]*\n?/i', '', $text);
$text = preg_replace('/\n?```$/', '', $text);

echo json_encode(['success' => true, 'text' => trim($text)]);
