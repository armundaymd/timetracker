<?php
require 'config.php';
header('Content-Type: application/json');

// Check Login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

try {
    // 1. GET STATUS (Current Timer)
    if ($action === 'status') {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND is_running = 1 LIMIT 1");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    // 2. START TIMER
    elseif ($action === 'start') {
        // Stop current
        $pdo->prepare("UPDATE tasks SET end_time = NOW(), is_running = 0 WHERE user_id = ? AND is_running = 1")->execute([$user_id]);
        
        // Start new
        $title = $_POST['title'] ?? 'Untitled Task';
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, start_time, is_running) VALUES (?, ?, NOW(), 1)");
        $stmt->execute([$user_id, $title]);
        echo json_encode(['status' => 'success']);
    }

    // 3. STOP TIMER
    elseif ($action === 'stop') {
        $pdo->prepare("UPDATE tasks SET end_time = NOW(), is_running = 0 WHERE user_id = ? AND is_running = 1")->execute([$user_id]);
        echo json_encode(['status' => 'stopped']);
    }

    // 4. GET CALENDAR EVENTS
    elseif ($action === 'events') {
        $start = $_GET['start'];
        $end = $_GET['end'];
        $stmt = $pdo->prepare("SELECT id, title, start_time as start, end_time as end, is_running FROM tasks WHERE user_id = ? AND start_time BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start, $end]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fix for currently running tasks (FullCalendar needs an end date to render blocks properly, or we leave it null)
        foreach($events as &$event) {
            if($event['is_running']) {
                $event['className'] = 'bg-danger border-danger'; // Highlight running task red
                $event['end'] = date('Y-m-d H:i:s'); // Show up to "now"
            }
        }
        echo json_encode($events);
    }

    // 5. UPDATE EVENT (Drag/Drop/Resize/Edit)
    elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, start_time = ?, end_time = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['title'], $data['start'], $data['end'], $data['id'], $user_id]);
        echo json_encode(['status' => 'updated']);
    }

    // 6. DELETE EVENT
    elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['id'], $user_id]);
        echo json_encode(['status' => 'deleted']);
    }

    // 7. CREATE MANUAL ENTRY
    elseif ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, start_time, end_time, is_running) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$user_id, $data['title'], $data['start'], $data['end']]);
        echo json_encode(['status' => 'created']);
    }

    // 8. AUTOCOMPLETE HISTORY
    elseif ($action === 'history') {
        $stmt = $pdo->prepare("SELECT DISTINCT title FROM tasks WHERE user_id = ? ORDER BY start_time DESC LIMIT 20");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>