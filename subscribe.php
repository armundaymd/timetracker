<?php
require 'config.php';

// Check token (Security)
$token = $_GET['token'] ?? '';
$stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) { die("Invalid Token"); }

$user_id = $user['id'];
$stmt = $pdo->prepare("SELECT title, start_time, end_time FROM tasks WHERE user_id = ?");
$stmt->execute([$user_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output ICS (no caching so calendar apps get fresh data when they refetch)
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="my_tasks.ics"');
header('Cache-Control: public, max-age=300'); // Hint: refetch after 5 min (clients may ignore)

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//My Time Tracker//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "X-WR-CALNAME:My Personal Tasks\r\n";
echo "REFRESH-INTERVAL;VALUE=DURATION:PT15M\r\n"; // Suggest 15-min refresh (RFC 7986; not all clients support)

foreach ($events as $event) {
    $start = gmdate('Ymd\THis\Z', strtotime($event['start_time']));
    $end = $event['end_time'] ? gmdate('Ymd\THis\Z', strtotime($event['end_time'])) : gmdate('Ymd\THis\Z');
    
    echo "BEGIN:VEVENT\r\n";
    echo "UID:" . md5($event['start_time'] . $event['title']) . "@mytracker.com\r\n";
    echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
    echo "DTSTART:$start\r\n";
    echo "DTEND:$end\r\n";
    echo "SUMMARY:" . $event['title'] . "\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
?>