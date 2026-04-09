<?php
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$tailor_id = isset($_GET['tailor_id']) && is_numeric($_GET['tailor_id']) ? (int)$_GET['tailor_id'] : 0;
if ($tailor_id <= 0 || !$pdo) {
    echo json_encode(['success' => false, 'services' => []]);
    exit;
}

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tailor_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tailor_id INT NOT NULL,
            service_name VARCHAR(120) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tailor_services_tailor_id (tailor_id),
            INDEX idx_tailor_services_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) {
}

$services = [];
try {
    $stmt = $pdo->prepare("SELECT id, service_name, price FROM tailor_services WHERE tailor_id = ? AND is_active = 1 ORDER BY service_name ASC");
    $stmt->execute([$tailor_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $services[] = [
            'id' => (int)$r['id'],
            'name' => (string)$r['service_name'],
            'price' => (float)$r['price'],
        ];
    }
} catch (Exception $e) {
    $services = [];
}

if (empty($services)) {
    $base = 0.0;
    try {
        $stmt = $pdo->prepare("SELECT price_range_min FROM tailors WHERE id = ? LIMIT 1");
        $stmt->execute([$tailor_id]);
        $base = (float)$stmt->fetchColumn();
    } catch (Exception $e) {
        $base = 0.0;
    }
    if ($base <= 0) {
        $base = 1000.0;
    }

    $seed = [
        ['Stitching', $base],
        ['Alteration', $base * 0.4],
        ['Customized Formal Wear', $base * 1.8],
        ['Customized Bridal Wear', $base * 3.0],
        ['Children Wear', $base * 0.8],
        ['Gents Shalwar Kameez', $base * 1.2],
        ['Dyeing', $base * 0.5],
        ['3-piece suit', $base * 2.5],
        ['Shirt', $base * 0.6],
        ['Other', $base],
    ];

    try {
        $ins = $pdo->prepare("INSERT INTO tailor_services (tailor_id, service_name, price, is_active) VALUES (?, ?, ?, 1)");
        foreach ($seed as $row) {
            $name = isset($row[0]) ? (string)$row[0] : '';
            $price = isset($row[1]) ? (float)$row[1] : 0.0;
            if ($name === '' || $price < 0) {
                continue;
            }
            $ins->execute([$tailor_id, $name, $price]);
        }
    } catch (Exception $e) {
    }

    $services = [];
    try {
        $stmt = $pdo->prepare("SELECT id, service_name, price FROM tailor_services WHERE tailor_id = ? AND is_active = 1 ORDER BY service_name ASC");
        $stmt->execute([$tailor_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $services[] = [
                'id' => (int)$r['id'],
                'name' => (string)$r['service_name'],
                'price' => (float)$r['price'],
            ];
        }
    } catch (Exception $e) {
        $services = [];
    }
}

echo json_encode(['success' => true, 'services' => $services]);
exit;
