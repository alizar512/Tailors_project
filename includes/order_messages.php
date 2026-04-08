<?php

function silah_ensure_order_messages_table($pdo) {
    if (!$pdo) return;

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS order_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                sender_type VARCHAR(20) NOT NULL,
                sender_name VARCHAR(100),
                sender_email VARCHAR(120),
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_messages_order_id (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (Exception $e) {
        return;
    }

    try {
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );

        $cols = [
            'sender_type' => "ALTER TABLE order_messages ADD COLUMN sender_type VARCHAR(20) NOT NULL DEFAULT 'system'",
            'sender_name' => "ALTER TABLE order_messages ADD COLUMN sender_name VARCHAR(100) NULL",
            'sender_email' => "ALTER TABLE order_messages ADD COLUMN sender_email VARCHAR(120) NULL",
            'created_at' => "ALTER TABLE order_messages ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ];

        foreach ($cols as $name => $ddl) {
            $check->execute(['order_messages', $name]);
            if ((int)$check->fetchColumn() === 0) {
                try { $pdo->exec($ddl); } catch (Exception $e) {}
            }
        }
    } catch (Exception $e) {
    }
}

