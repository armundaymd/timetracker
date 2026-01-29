<?php
require 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];
if (!is_array($data)) $data = [];

// Resolve user: Bearer token (for native apps) or session (for web)
$user_id = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(\S+)/', $authHeader, $m)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
    $stmt->execute([$m[1]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $user_id = (int) $row['id'];
}
if ($user_id === null && !empty($_GET['token'])) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
    $stmt->execute([$_GET['token']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $user_id = (int) $row['id'];
}
if ($user_id === null && isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
}

// Login for native apps (no session): POST username + password, returns token
if ($action === 'login') {
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, password, api_token FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    echo json_encode(['token' => $user['api_token']]);
    exit;
}

if ($user_id === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

function resolve_project_from_title(PDO $pdo, $title, $user_id) {
    $parts = explode(':', $title, 2);
    if (count($parts) !== 2) return null;
    $name = trim($parts[0]);
    if ($name === '') return null;
    $stmt = $pdo->prepare("SELECT id FROM projects WHERE user_id = ? AND name = ?");
    $stmt->execute([$user_id, $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['id'] : null;
}

try {
    // 1. GET STATUS (Current Timer)
    if ($action === 'status') {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND is_running = 1 LIMIT 1");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }

    // 2. START TIMER
    elseif ($action === 'start') {
        $pdo->prepare("UPDATE tasks SET end_time = NOW(), is_running = 0 WHERE user_id = ? AND is_running = 1")->execute([$user_id]);
        $title = $_POST['title'] ?? 'Untitled Task';
        $project_id = resolve_project_from_title($pdo, $title, $user_id);
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, start_time, is_running, project_id) VALUES (?, ?, NOW(), 1, ?)");
        $stmt->execute([$user_id, $title, $project_id]);
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
        $stmt = $pdo->prepare("SELECT t.id, t.title, t.start_time as start, t.end_time as end, t.is_running, t.project_id, p.color as project_color FROM tasks t LEFT JOIN projects p ON t.project_id = p.id WHERE t.user_id = ? AND t.start_time BETWEEN ? AND ?");
        $stmt->execute([$user_id, $start, $end]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fix for currently running tasks (FullCalendar needs an end date to render blocks properly, or we leave it null)
        foreach($events as &$event) {
            if($event['is_running']) {
                $event['className'] = 'bg-danger border-danger';
                $event['end'] = date('Y-m-d H:i:s');
            }
            if (!empty($event['project_color'])) {
                $event['borderColor'] = $event['project_color'];
                $event['backgroundColor'] = $event['project_color'];
            }
        }
        echo json_encode($events);
    }

    // 5. UPDATE EVENT (Drag/Drop/Resize/Edit)
    elseif ($action === 'update') {
        $project_id = resolve_project_from_title($pdo, $data['title'] ?? '', $user_id);
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, start_time = ?, end_time = ?, project_id = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['title'], $data['start'], $data['end'], $project_id, $data['id'], $user_id]);
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
        $project_id = resolve_project_from_title($pdo, $data['title'] ?? '', $user_id);
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, start_time, end_time, is_running, project_id) VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->execute([$user_id, $data['title'], $data['start'], $data['end'], $project_id]);
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

    // 12. CHANGE PASSWORD (from Settings page)
    elseif ($action === 'change_password') {
        $current = $data['current_password'] ?? '';
        $new = $data['new_password'] ?? '';
        if ($current === '' || $new === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Current and new password required']);
            exit;
        }
        if (strlen($new) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 6 characters']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($current, $row['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Current password is incorrect']);
            exit;
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
        echo json_encode(['status' => 'updated']);
    }

    // 13. SAVE QUICK BUTTONS (from Settings page)
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

    // 14. ANALYTICS (time by activity for date range)
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
        // By project (for pie chart / breakdown)
        $stmtProj = $pdo->prepare("
            SELECT p.name, p.color, SUM(TIMESTAMPDIFF(SECOND, t.start_time, COALESCE(t.end_time, NOW()))) AS seconds
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.user_id = ? AND t.is_running = 0 AND t.start_time >= ? AND t.start_time < DATE_ADD(?, INTERVAL 1 DAY) AND t.end_time IS NOT NULL
            GROUP BY p.id, p.name, p.color
            HAVING p.id IS NOT NULL
            ORDER BY seconds DESC
        ");
        $stmtProj->execute([$user_id, $start, $end]);
        $by_project = $stmtProj->fetchAll(PDO::FETCH_ASSOC);

        // Heatmap: last 365 days, seconds per day (date => seconds)
        $stmtHeat = $pdo->prepare("
            SELECT DATE(start_time) AS d, SUM(TIMESTAMPDIFF(SECOND, start_time, COALESCE(end_time, NOW()))) AS seconds
            FROM tasks
            WHERE user_id = ? AND start_time >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
            GROUP BY DATE(start_time)
        ");
        $stmtHeat->execute([$user_id]);
        $heatmap_days = [];
        while ($row = $stmtHeat->fetch(PDO::FETCH_ASSOC)) {
            $heatmap_days[$row['d']] = (int) $row['seconds'];
        }

        echo json_encode([
            'start' => $start,
            'end' => $end,
            'total_seconds' => (int) $total_seconds,
            'by_activity' => $by_activity,
            'by_project' => $by_project,
            'heatmap_days' => $heatmap_days
        ]);
    }

    // 15. GET PROJECTS (for dropdown / categorization)
    elseif ($action === 'projects') {
        $stmt = $pdo->prepare("SELECT id, name, color FROM projects WHERE user_id = ? ORDER BY name");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // 16. SAVE PROJECT (create or update)
    elseif ($action === 'save_project') {
        $id = isset($data['id']) ? (int) $data['id'] : null;
        $name = trim($data['name'] ?? '');
        $color = trim($data['color'] ?? '#0ea5e9');
        if ($name === '') {
            echo json_encode(['error' => 'Project name required']);
            exit;
        }
        if ($id) {
            $stmt = $pdo->prepare("UPDATE projects SET name = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $color, $id, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO projects (user_id, name, color) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $name, $color]);
        }
        echo json_encode(['status' => 'saved']);
    }

    // 17. DELETE PROJECT
    elseif ($action === 'delete_project') {
        $id = (int) ($data['id'] ?? 0);
        $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
        $pdo->prepare("UPDATE tasks SET project_id = NULL WHERE project_id = ?")->execute([$id]);
        echo json_encode(['status' => 'deleted']);
    }

    // 18. EXPORT (backup all tasks as JSON)
    elseif ($action === 'export') {
        $stmt = $pdo->prepare("SELECT id, title, start_time, end_time, is_running, project_id FROM tasks WHERE user_id = ? ORDER BY start_time DESC");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="time_tracker_backup_' . date('Y-m-d') . '.json"');
        echo json_encode($rows, JSON_PRETTY_PRINT);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>