<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php'; // Sesuaikan path ke vendor

if ($argc < 4) {
    exit("Usage: php send_email.php <to> <name> <token>");
}

$to = $argv[1];
$name = $argv[2];
$token = $argv[3];

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'admin@malmanech.com';
    $mail->Password = 'SuperBrembo13()';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('admin@malmanech.com', 'Mal + Team');
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = 'Verify Your Mal + Account';
    $verification_link = "http://malmanech.com/index.php?page=verify&token=" . $token;
    $mail->Body = "
    <html>
    <head>
        <title>Verify Your Mal + Account</title>
    </head>
    <body>
        <h2>Hello $name,</h2>
        <p>Thank you for registering with Mal +!</p>
        <p>Please click the link below to verify your email and activate your account:</p>
        <p><a href='$verification_link'>Verify Email</a></p>
        <p>If you didn't register, please ignore this email.</p>
        <p>Best regards,<br>The Mal + Team</p>
    </body>
    </html>";

    $mail->send();
} catch (Exception $e) {
    error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
}