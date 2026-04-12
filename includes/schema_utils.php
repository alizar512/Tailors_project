<?php
function silah_ensure_table($pdo, $ddl) {
    if (!$pdo) return false;
    try {
        $pdo->exec($ddl);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function silah_has_column($pdo, $table, $column) {
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $stmt->execute([(string)$table, (string)$column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function silah_ensure_column($pdo, $table, $column, $ddl) {
    if (!$pdo) return false;
    try {
        if (silah_has_column($pdo, $table, $column)) {
            return true;
        }
        $pdo->exec($ddl);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
