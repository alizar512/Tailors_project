<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$feedback = $feedback ? trim((string)$feedback) : '';

if ($feedback === '') {
    header("Location: index.php?feedback=error#contact");
    exit();
}

$to = "silah.orders@gmail.com";
$emailSubject = "Silah Feedback";
$emailBody =
    "New Feedback:\n\n" .
    (string)$feedback . "\n";

try {
    silah_send_email($to, $emailSubject, $emailBody);
} catch (Exception $e) {
}

if ($pdo) {
    try {
        try {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS contacts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120),
                    email VARCHAR(160),
                    message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (Exception $e) {
        }

        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute(['Feedback', '', "Feedback:\n" . (string)$feedback]);
    } catch (Exception $e) {
    }
}

header("Location: index.php?feedback=sent#contact");
exit();
?>
