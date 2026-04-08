<?php

function silah_ensure_notifications_table($pdo) {
    if (!$pdo) return;
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_type VARCHAR(20) NOT NULL DEFAULT 'admin',
                recipient_id INT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM('order', 'tailor', 'system') DEFAULT 'system',
                link VARCHAR(255),
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notifications_recipient (recipient_type, recipient_id, is_read),
                INDEX idx_notifications_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Exception $e) {
        return;
    }

    try { $pdo->exec("ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) NOT NULL DEFAULT 'admin'"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE notifications ADD COLUMN recipient_id INT NULL"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE notifications ADD INDEX idx_notifications_recipient (recipient_type, recipient_id, is_read)"); } catch (Exception $e) {}
}

function silah_add_notification($pdo, $recipientType, $recipientId, $title, $message, $type, $link) {
    if (!$pdo) return false;
    silah_ensure_notifications_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    $title = (string)$title;
    $message = (string)$message;
    $type = $type ? (string)$type : 'system';
    $link = $link !== null ? (string)$link : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (recipient_type, recipient_id, title, message, type, link, is_read) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$recipientType, $recipientId, $title, $message, $type, $link]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function silah_unread_notifications_count($pdo, $recipientType, $recipientId) {
    if (!$pdo) return 0;
    silah_ensure_notifications_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    try {
        if ($recipientId === null) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_type = ? AND recipient_id IS NULL AND is_read = 0");
            $stmt->execute([$recipientType]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0");
            $stmt->execute([$recipientType, $recipientId]);
        }
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function silah_mark_notifications_read($pdo, $recipientType, $recipientId) {
    if (!$pdo) return;
    silah_ensure_notifications_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    try {
        if ($recipientId === null) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_type = ? AND recipient_id IS NULL AND is_read = 0");
            $stmt->execute([$recipientType]);
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0");
            $stmt->execute([$recipientType, $recipientId]);
        }
    } catch (Exception $e) {
    }
}

function silah_get_recent_notifications($pdo, $recipientType, $recipientId, $limit = 5) {
    if (!$pdo) return [];
    silah_ensure_notifications_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    $limit = is_numeric($limit) ? (int)$limit : 5;
    if ($limit <= 0) $limit = 5;
    if ($limit > 20) $limit = 20;

    try {
        if ($recipientId === null) {
            $stmt = $pdo->prepare("SELECT id, title, message, type, link, is_read, created_at FROM notifications WHERE recipient_type = ? AND recipient_id IS NULL ORDER BY created_at DESC, id DESC LIMIT " . $limit);
            $stmt->execute([$recipientType]);
        } else {
            $stmt = $pdo->prepare("SELECT id, title, message, type, link, is_read, created_at FROM notifications WHERE recipient_type = ? AND recipient_id = ? ORDER BY created_at DESC, id DESC LIMIT " . $limit);
            $stmt->execute([$recipientType, $recipientId]);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Exception $e) {
        return [];
    }
}

function silah_ensure_notification_throttle_table($pdo) {
    if (!$pdo) return;
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS notification_throttle (
                recipient_type VARCHAR(20) NOT NULL,
                recipient_id INT NULL,
                event_key VARCHAR(120) NOT NULL,
                last_notified_at TIMESTAMP NULL,
                last_emailed_at TIMESTAMP NULL,
                PRIMARY KEY (recipient_type, recipient_id, event_key),
                INDEX idx_throttle_notified (last_notified_at),
                INDEX idx_throttle_emailed (last_emailed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Exception $e) {
    }
}

function silah_should_notify($pdo, $recipientType, $recipientId, $eventKey, $cooldownSeconds) {
    if (!$pdo) return true;
    silah_ensure_notification_throttle_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    $eventKey = trim((string)$eventKey);
    $cooldownSeconds = is_numeric($cooldownSeconds) ? (int)$cooldownSeconds : 60;
    if ($cooldownSeconds < 0) $cooldownSeconds = 0;
    if ($eventKey === '') return true;

    try {
        if ($recipientId === null) {
            $stmt = $pdo->prepare("SELECT last_notified_at FROM notification_throttle WHERE recipient_type = ? AND recipient_id IS NULL AND event_key = ? LIMIT 1");
            $stmt->execute([$recipientType, $eventKey]);
        } else {
            $stmt = $pdo->prepare("SELECT last_notified_at FROM notification_throttle WHERE recipient_type = ? AND recipient_id = ? AND event_key = ? LIMIT 1");
            $stmt->execute([$recipientType, $recipientId, $eventKey]);
        }
        $ts = $stmt->fetchColumn();
        if (!$ts) return true;
        $last = strtotime((string)$ts);
        if ($last <= 0) return true;
        return (time() - $last) >= $cooldownSeconds;
    } catch (Exception $e) {
        return true;
    }
}

function silah_record_notified($pdo, $recipientType, $recipientId, $eventKey) {
    if (!$pdo) return;
    silah_ensure_notification_throttle_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    $eventKey = trim((string)$eventKey);
    if ($eventKey === '') return;

    try {
        if ($recipientId === null) {
            $stmt = $pdo->prepare("INSERT INTO notification_throttle (recipient_type, recipient_id, event_key, last_notified_at) VALUES (?, NULL, ?, NOW()) ON DUPLICATE KEY UPDATE last_notified_at = NOW()");
            $stmt->execute([$recipientType, $eventKey]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO notification_throttle (recipient_type, recipient_id, event_key, last_notified_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE last_notified_at = NOW()");
            $stmt->execute([$recipientType, $recipientId, $eventKey]);
        }
    } catch (Exception $e) {
    }
}

function silah_should_email($pdo, $recipientType, $recipientId, $eventKey, $cooldownSeconds) {
    if (!$pdo) return true;
    silah_ensure_notification_throttle_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    $eventKey = trim((string)$eventKey);
    $cooldownSeconds = is_numeric($cooldownSeconds) ? (int)$cooldownSeconds : 120;
    if ($cooldownSeconds < 0) $cooldownSeconds = 0;
    if ($eventKey === '') return true;

    try {
        if ($recipientId === null) {
            $stmt = $pdo->prepare("SELECT last_emailed_at FROM notification_throttle WHERE recipient_type = ? AND recipient_id IS NULL AND event_key = ? LIMIT 1");
            $stmt->execute([$recipientType, $eventKey]);
        } else {
            $stmt = $pdo->prepare("SELECT last_emailed_at FROM notification_throttle WHERE recipient_type = ? AND recipient_id = ? AND event_key = ? LIMIT 1");
            $stmt->execute([$recipientType, $recipientId, $eventKey]);
        }
        $ts = $stmt->fetchColumn();
        if (!$ts) return true;
        $last = strtotime((string)$ts);
        if ($last <= 0) return true;
        return (time() - $last) >= $cooldownSeconds;
    } catch (Exception $e) {
        return true;
    }
}

function silah_record_emailed($pdo, $recipientType, $recipientId, $eventKey) {
    if (!$pdo) return;
    silah_ensure_notification_throttle_table($pdo);
    $recipientType = $recipientType ? (string)$recipientType : 'admin';
    $recipientId = $recipientId !== null ? (int)$recipientId : null;
    $eventKey = trim((string)$eventKey);
    if ($eventKey === '') return;

    try {
        if ($recipientId === null) {
            $stmt = $pdo->prepare("INSERT INTO notification_throttle (recipient_type, recipient_id, event_key, last_emailed_at) VALUES (?, NULL, ?, NOW()) ON DUPLICATE KEY UPDATE last_emailed_at = NOW()");
            $stmt->execute([$recipientType, $eventKey]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO notification_throttle (recipient_type, recipient_id, event_key, last_emailed_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE last_emailed_at = NOW()");
            $stmt->execute([$recipientType, $recipientId, $eventKey]);
        }
    } catch (Exception $e) {
    }
}
