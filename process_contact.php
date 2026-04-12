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

            silah_send_email($to, $emailSubject, $emailBody);

            header("Location: index.php?contact=sent#contact");
            exit();
        } catch (Exception $e) {
            try { silah_send_email($to, $emailSubject, $emailBody); } catch (Exception $e2) {}
            header("Location: index.php?contact=error#contact");
            exit();
        }
    } else {
        try { silah_send_email($to, $emailSubject, $emailBody); } catch (Exception $e) {}
        header("Location: index.php?contact=sent#contact");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
