<?php
/**
 * Secure Audio Downloader
 * Checks permissions before serving the file.
 */
require_once '../includes/auth.php';

if (!is_logged_in()) {
    die("Unauthorized");
}

$song_id = $_GET['id'] ?? null;
if (!$song_id) die("ID required");

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT audio_url, title FROM songs WHERE id = ? AND user_id = ?");
    $stmt->execute([$song_id, $user_id]);
    $song = $stmt->fetch();

    if ($song && !empty($song['audio_url'])) {
        $file_path = "../" . $song['audio_url'];
        
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: audio/mpeg');
            header('Content-Disposition: attachment; filename="' . basename($song['title']) . '.mp3"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            die("File not found on server.");
        }
    } else {
        die("Unauthorized or song not ready.");
    }
} catch (Exception $e) {
    die("Error processing download.");
}
?>
