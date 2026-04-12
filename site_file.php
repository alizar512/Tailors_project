<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/file_store.php';

$key = isset($_GET['key']) ? trim((string)$_GET['key']) : '';
if ($key === '' || !$pdo) {
    http_response_code(404);
    exit;
}

silah_ensure_site_files_table($pdo);
try {
    $stmt = $pdo->prepare("SELECT mime, blob FROM site_files WHERE skey = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !isset($row['blob']) || $row['blob'] === null || $row['blob'] === '') {
        http_response_code(404);
        exit;
    }
    $mime = isset($row['mime']) && trim((string)$row['mime']) !== '' ? (string)$row['mime'] : 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=604800');
    echo $row['blob'];
    exit;
} catch (Exception $e) {
    http_response_code(404);
    exit;
}
?>

