<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';

$type = $_GET['type'] ?? 'orders';

if ($pdo) {
    if ($type === 'orders') {
        $stmt = $pdo->query("SELECT id, customer_name, customer_email, service_type, status, total_price, created_at FROM orders ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = "silah_orders_" . date('Y-m-d') . ".csv";
    } else {
        $stmt = $pdo->query("SELECT id, name, location, experience_years, email, phone, created_at FROM tailors ORDER BY created_at DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = "silah_tailors_" . date('Y-m-d') . ".csv";
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    
    // Header row
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }

    // Data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
