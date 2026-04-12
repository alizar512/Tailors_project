<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/schema_utils.php';

$appId = isset($_GET['app_id']) && is_numeric($_GET['app_id']) ? (int)$_GET['app_id'] : 0;
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
$fileId = isset($_GET['file_id']) && is_numeric($_GET['file_id']) ? (int)$_GET['file_id'] : 0;

if ($appId <= 0 || !$pdo) {
    http_response_code(404);
    exit;
}

silah_ensure_column($pdo, 'tailor_applications', 'profile_image_blob', "ALTER TABLE tailor_applications ADD COLUMN profile_image_blob LONGBLOB NULL");
silah_ensure_column($pdo, 'tailor_applications', 'profile_image_mime', "ALTER TABLE tailor_applications ADD COLUMN profile_image_mime VARCHAR(100) NULL");

silah_ensure_table($pdo,
    "CREATE TABLE IF NOT EXISTS tailor_application_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        file_kind VARCHAR(30) NOT NULL,
        mime VARCHAR(100),
        blob LONGBLOB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_app_files_application_id (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

try {
    if ($type === 'profile') {
        $stmt = $pdo->prepare("SELECT name, profile_image, profile_image_blob, profile_image_mime FROM tailor_applications WHERE id = ? LIMIT 1");
        $stmt->execute([$appId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            exit;
        }
        $blob = isset($row['profile_image_blob']) ? $row['profile_image_blob'] : null;
        $mime = isset($row['profile_image_mime']) ? trim((string)$row['profile_image_mime']) : '';
        if ($blob !== null && $blob !== '') {
            if ($mime === '') $mime = 'image/jpeg';
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=604800');
            echo $blob;
            exit;
        }
        $path = isset($row['profile_image']) ? trim((string)$row['profile_image']) : '';
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
    }

    if ($fileId > 0) {
        $stmt = $pdo->prepare("SELECT mime, blob FROM tailor_application_files WHERE id = ? AND application_id = ? LIMIT 1");
        $stmt->execute([$fileId, $appId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            exit;
        }
        $blob = isset($row['blob']) ? $row['blob'] : null;
        $mime = isset($row['mime']) ? trim((string)$row['mime']) : '';
        if ($blob === null || $blob === '') {
            http_response_code(404);
            exit;
        }
        if ($mime === '') $mime = 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=604800');
        echo $blob;
        exit;
    }

    http_response_code(404);
    exit;
} catch (Exception $e) {
    http_response_code(404);
    exit;
}
?>

