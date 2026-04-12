<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/schema_utils.php';

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 || !$pdo) {
    http_response_code(404);
    exit;
}

silah_ensure_column($pdo, 'portfolio_images', 'image_blob', "ALTER TABLE portfolio_images ADD COLUMN image_blob LONGBLOB NULL");
silah_ensure_column($pdo, 'portfolio_images', 'image_mime', "ALTER TABLE portfolio_images ADD COLUMN image_mime VARCHAR(100) NULL");

try {
    $stmt = $pdo->prepare("SELECT image_url, image_blob, image_mime FROM portfolio_images WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        exit;
    }

    $blob = isset($row['image_blob']) ? $row['image_blob'] : null;
    $mime = isset($row['image_mime']) ? trim((string)$row['image_mime']) : '';
    if ($blob !== null && $blob !== '') {
        if ($mime === '') $mime = 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=604800');
        echo $blob;
        exit;
    }

    $path = isset($row['image_url']) ? trim((string)$row['image_url']) : '';
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

