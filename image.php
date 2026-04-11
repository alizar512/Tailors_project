<?php
require_once __DIR__ . '/includes/db_connect.php';

$kind = isset($_GET['kind']) ? trim((string)$_GET['kind']) : '';
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($kind !== 'tailor' || $id <= 0 || !$pdo) {
    http_response_code(404);
    exit;
}

try { $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_image_blob LONGBLOB NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE tailors ADD COLUMN profile_image_mime VARCHAR(100) NULL"); } catch (Exception $e) {}

try {
    $stmt = $pdo->prepare("SELECT name, profile_image, profile_image_blob, profile_image_mime FROM tailors WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
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
    if ($path !== '' && (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0)) {
        header('Location: ' . $path, true, 302);
        exit;
    }
    if ($path !== '' && strpos($path, 'http://') !== 0 && strpos($path, 'https://') !== 0) {
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

    $name = isset($row['name']) ? (string)$row['name'] : 'Tailor';
    $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=865294&color=fff';
    header('Location: ' . $fallback, true, 302);
    exit;
} catch (Exception $e) {
    http_response_code(404);
    exit;
}
?>
