<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/schema_utils.php';

$orderId = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$field = isset($_GET['field']) ? trim((string)$_GET['field']) : '';

$fields = [
    'reference' => ['img_col' => 'reference_image', 'blob_col' => 'reference_image_blob', 'mime_col' => 'reference_image_mime'],
    'payment' => ['img_col' => 'payment_proof_image', 'blob_col' => 'payment_proof_blob', 'mime_col' => 'payment_proof_mime'],
    'balance' => ['img_col' => 'balance_payment_proof_image', 'blob_col' => 'balance_payment_proof_blob', 'mime_col' => 'balance_payment_proof_mime'],
    'cargo' => ['img_col' => 'cargo_receipt_image', 'blob_col' => 'cargo_receipt_blob', 'mime_col' => 'cargo_receipt_mime'],
];

if ($orderId <= 0 || !isset($fields[$field]) || !$pdo) {
    http_response_code(404);
    exit;
}

$cfg = $fields[$field];
silah_ensure_column($pdo, 'orders', $cfg['blob_col'], "ALTER TABLE orders ADD COLUMN {$cfg['blob_col']} LONGBLOB NULL");
silah_ensure_column($pdo, 'orders', $cfg['mime_col'], "ALTER TABLE orders ADD COLUMN {$cfg['mime_col']} VARCHAR(100) NULL");

try {
    $stmt = $pdo->prepare("SELECT customer_email, {$cfg['img_col']}, {$cfg['blob_col']}, {$cfg['mime_col']} FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        exit;
    }

    $blob = isset($row[$cfg['blob_col']]) ? $row[$cfg['blob_col']] : null;
    $mime = isset($row[$cfg['mime_col']]) ? trim((string)$row[$cfg['mime_col']]) : '';
    if ($blob !== null && $blob !== '') {
        if ($mime === '') $mime = 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=604800');
        echo $blob;
        exit;
    }

    $path = isset($row[$cfg['img_col']]) ? trim((string)$row[$cfg['img_col']]) : '';
    if ($path !== '' && (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0)) {
        header('Location: ' . $path, true, 302);
        exit;
    }
    if ($path !== '') {
        $rel = ltrim($path, '/');
        if (strpos($rel, '..') === false && (strpos($rel, 'uploads/') === 0 || strpos($rel, 'images/') === 0)) {
            $full = __DIR__ . '/' . $rel;
            if (is_file($full)) {
                $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
                $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
                $ct = isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
                header('Content-Type: ' . $ct);
                header('Cache-Control: public, max-age=604800');
                readfile($full);
                exit;
            }
        }
    }

    http_response_code(404);
    exit;
} catch (Exception $e) {
    http_response_code(404);
    exit;
}
?>

