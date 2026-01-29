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

    // 7. CREATE MANUAL ENTRY (returns new id for undo)
    elseif ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, start_time, end_time, is_running) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$user_id, $data['title'], $data['start'], $data['end']]);
        echo json_encode(['status' => 'created', 'id' => (int) $pdo->lastInsertId()]);
    }

    // 8. AUTOCOMPLETE HISTORY
    elseif ($action === 'history') {
        $stmt = $pdo->prepare("SELECT DISTINCT title FROM tasks WHERE user_id = ? ORDER BY start_time DESC LIMIT 20");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // 9. TOGGLE FAVORITE (star/unstar current task title)
    elseif ($action === 'toggle_favorite') {
        $title = trim($data['title'] ?? '');
        if ($title === '') {
            echo json_encode(['error' => 'Title required']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND title = ?");
        $stmt->execute([$user_id, $title]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND title = ?")->execute([$user_id, $title]);
            echo json_encode(['starred' => false]);
        } else {
            $pdo->prepare("INSERT INTO user_favorites (user_id, title) VALUES (?, ?)")->execute([$user_id, $title]);
            echo json_encode(['starred' => true]);
        }
    }

    // 10. GET FAVORITES
    elseif ($action === 'favorites') {
        $stmt = $pdo->prepare("SELECT title FROM user_favorites WHERE user_id = ? ORDER BY title");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // 11. GET QUICK BUTTONS (for display next to task input)
    elseif ($action === 'quick_buttons') {
        $stmt = $pdo->prepare("SELECT title FROM user_quick_buttons WHERE user_id = ? ORDER BY sort_order, id");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // 12. SAVE QUICK BUTTONS (from Settings page)
    elseif ($action === 'save_quick_buttons') {
        $titles = $data['titles'] ?? [];
        if (!is_array($titles)) $titles = [];
        $titles = array_values(array_filter(array_map('trim', $titles)));
        $pdo->prepare("DELETE FROM user_quick_buttons WHERE user_id = ?")->execute([$user_id]);
        $stmt = $pdo->prepare("INSERT INTO user_quick_buttons (user_id, title, sort_order) VALUES (?, ?, ?)");
        foreach ($titles as $i => $title) {
            if ($title !== '') $stmt->execute([$user_id, $title, $i]);
        }
        echo json_encode(['status' => 'saved']);
    }

    // 13. ANALYTICS (time by activity for date range)
    elseif ($action === 'analytics') {
        $start = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $end = $_GET['end'] ?? date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT title,
                   SUM(TIMESTAMPDIFF(SECOND, start_time, COALESCE(end_time, NOW()))) AS seconds
            FROM tasks
            WHERE user_id = ? AND is_running = 0
              AND start_time >= ? AND start_time < DATE_ADD(?, INTERVAL 1 DAY)
              AND end_time IS NOT NULL
            GROUP BY title
            ORDER BY seconds DESC
        ");
        $stmt->execute([$user_id, $start, $end]);
        $by_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_seconds = array_sum(array_column($by_activity, 'seconds'));
        // Include running task in total if in range
        $stmt = $pdo->prepare("SELECT start_time FROM tasks WHERE user_id = ? AND is_running = 1 LIMIT 1");
        $stmt->execute([$user_id]);
        $running = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($running) {
            $run_start = strtotime($running['start_time']);
            $range_start = strtotime($start);
            $range_end = strtotime($end . ' 23:59:59');
            if ($run_start >= $range_start && $run_start <= $range_end) {
                $total_seconds += (time() - $run_start);
            }
        }
        echo json_encode([
            'start' => $start,
            'end' => $end,
            'total_seconds' => (int) $total_seconds,
            'by_activity' => $by_activity
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>