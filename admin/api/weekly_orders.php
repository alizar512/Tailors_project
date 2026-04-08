<?php
require_once '../auth_check.php';
require_once '../../includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!$pdo) {
    echo json_encode(['ok' => false, 'error' => 'db_error']);
    exit;
}

try { $pdo->exec("ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"); } catch (Exception $e) {}

$tz = new DateTimeZone(date_default_timezone_get());
$start = new DateTime('monday this week', $tz);
$start->setTime(0, 0, 0);
$end = (clone $start)->modify('+7 days');

$labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$counts = array_fill(0, 7, 0);

try {
    $stmt = $pdo->prepare(
        "SELECT DATE(created_at) AS d, COUNT(*) AS c
         FROM orders
         WHERE created_at >= ? AND created_at < ?
         GROUP BY DATE(created_at)"
    );
    $stmt->execute([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r) {
        $d = isset($r['d']) ? (string)$r['d'] : '';
        $c = isset($r['c']) ? (int)$r['c'] : 0;
        if ($d !== '') $map[$d] = $c;
    }

    for ($i = 0; $i < 7; $i++) {
        $day = (clone $start)->modify('+' . $i . ' days')->format('Y-m-d');
        $counts[$i] = isset($map[$day]) ? (int)$map[$day] : 0;
    }
} catch (Exception $e) {
}

echo json_encode([
    'ok' => true,
    'labels' => $labels,
    'data' => $counts,
    'range' => [
        'start' => $start->format('Y-m-d'),
        'end' => (clone $end)->modify('-1 day')->format('Y-m-d'),
    ],
    'updated_at' => (new DateTime('now', $tz))->format(DateTime::ATOM),
]);

