<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer autoload file from Composer
require 'vendor/autoload.php';

// Initialize message variable
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = trim($_POST['to'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Test Email from FarmBridge AI');
    $body = trim($_POST['body'] ?? 'This is a test email.');

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $msg = '<div style="color:red;">❌ Invalid recipient email address.</div>';
    } else {
        $mail = new PHPMailer(true);
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'oficialbertin@gmail.com'; // your Gmail
            $mail->Password = 'ltbk zbpq mgsx asxw'; // your Gmail app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Sender info
            $mail->setFrom('oficialbertin@gmail.com', 'FarmBridge AI Rwanda');

            // Recipient
            $mail->addAddress($to);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = nl2br($body);
            $mail->AltBody = strip_tags($body);

            // Send the email
            $mail->send();
            $msg = '<div style="color:green;">✅ Email sent successfully to ' . htmlspecialchars($to) . '</div>';
        } catch (Exception $e) {
            $msg = '<div style="color:red;">❌ Email failed to send. Error: ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Test Email | FarmBridge AI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 60px auto;
            max-width: 600px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2e7d32;
        }
        input, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }
        button {
            background: #2e7d32;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #256528;
        }
    </style>
</head>
<body>
    <h2>📧 Send Email via FarmBridge AI SMTP</h2>
    <?= $msg ?>
    <form method="post">
        <label>Recipient Email:</label>
        <input type="email" name="to" placeholder="example@gmail.com" required>

        <label>Subject:</label>
        <input type="text" name="subject" placeholder="Email subject" required>

        <label>Message:</label>
        <textarea name="body" rows="5" placeholder="Write your message..." required></textarea>

        <button type="submit">Send Email</button>
    </form>
</body>
</html>
