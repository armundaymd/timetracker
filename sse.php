<?php
require 'config.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // disable nginx buffering

// SSE uses GET; authenticate via token (session doesn't persist on EventSource)
$token = $_GET['token'] ?? '';
$stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();
if (!$user) {
    echo "data: " . json_encode(['error' => 'Invalid token']) . "\n\n";
    ob_flush();
    flush();
    exit;
}
$user_id = (int) $user['id'];

$last_state = null;
$stmt = $pdo->prepare("SELECT is_running, title, start_time FROM tasks WHERE user_id = ? AND is_running = 1 LIMIT 1");

while (true) {
    if (connection_aborted()) break;

    $stmt->execute([$user_id]);
    $current_task = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_json = json_encode($current_task);

    if ($current_json !== $last_state) {
        echo "data: " . $current_json . "\n\n";
        $last_state = $current_json;
        if (ob_get_level()) ob_flush();
        flush();
    }
    sleep(3);
}
