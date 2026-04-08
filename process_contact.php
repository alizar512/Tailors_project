<?php
require_once 'includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $full_message = "Subject: " . $subject . "\nMessage: " . $message;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $full_message]);
            
            echo "<script>alert('Thank you! Your message has been sent.'); window.location.href='index.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error submitting form. Please try again.'); window.location.href='index.php';</script>";
        }
    } else {
        echo "<script>alert('Thank you! (Demo Mode - DB not connected)'); window.location.href='index.php';</script>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>