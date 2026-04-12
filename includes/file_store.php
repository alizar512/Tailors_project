<?php
require_once __DIR__ . '/schema_utils.php';

function silah_ensure_site_files_table($pdo) {
    return silah_ensure_table($pdo,
        "CREATE TABLE IF NOT EXISTS site_files (
            skey VARCHAR(120) PRIMARY KEY,
            mime VARCHAR(100),
            blob LONGBLOB,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function silah_site_file_set($pdo, $key, $mime, $blob) {
    if (!$pdo) return false;
    silah_ensure_site_files_table($pdo);
    try {
        $stmt = $pdo->prepare("REPLACE INTO site_files (skey, mime, blob) VALUES (?, ?, ?)");
        return $stmt->execute([(string)$key, (string)$mime, $blob]);
    } catch (Exception $e) {
        return false;
    }
}

function silah_site_file_clear($pdo, $key) {
    if (!$pdo) return false;
    silah_ensure_site_files_table($pdo);
    try {
        $stmt = $pdo->prepare("DELETE FROM site_files WHERE skey = ? LIMIT 1");
        return $stmt->execute([(string)$key]);
    } catch (Exception $e) {
        return false;
    }
}
?>

