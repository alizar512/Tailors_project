<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $full_message = "Subject: " . $subject . "\nMessage: " . $message;
    $to = "silah.orders@gmail.com";
    $emailSubject = "Silah Contact: " . (string)$subject;
    $emailBody =
        "New Contact Us message:\n\n" .
        "Name: " . (string)$name . "\n" .
        "Email: " . (string)$email . "\n" .
        "Subject: " . (string)$subject . "\n\n" .
        (string)$message . "\n";

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $full_message]);

            $sent = silah_send_email($to, $emailSubject, $emailBody, $email, $name);

            header("Location: index.php?contact=" . ($sent ? 'sent' : 'queued') . "#contact");
            exit();
        } catch (Exception $e) {
            $sent = false;
            try { $sent = silah_send_email($to, $emailSubject, $emailBody, $email, $name); } catch (Exception $e2) {}
            header("Location: index.php?contact=error#contact");
            exit();
        }
    } else {
        $sent = false;
        try { $sent = silah_send_email($to, $emailSubject, $emailBody, $email, $name); } catch (Exception $e) {}
        header("Location: index.php?contact=" . ($sent ? 'sent' : 'queued') . "#contact");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
