<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require '../../vendor/autoload.php'; 
require '../config.php'; // Include your database configuration

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot_password.php?alert=Invalid email address.&type=danger");
        exit();
    }

    // Check if the email exists in your user table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: forgot_password.php?alert=Email not found.&type=warning");
        exit();
    }

    // Generate a secure token
    $token = bin2hex(random_bytes(32)); // 64-character token

    // Save token in the database
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
    $stmt->execute([$email, $token]);

    // Send email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moonarrowstudios@gmail.com';
        $mail->Password   = 'jbws akjv bxvr xxac'; // Replace with a secure app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('noreply@moonnarrowstudios.com', 'MoonArrow Studios');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = '
<div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
    <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
        <!-- Logo Section -->
        <div style="margin-bottom: 20px;">
            <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
        </div>
        <!-- Content Section -->
        <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">Reset Your Password</h2>
        <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
            We received a request to reset your password. Click the button below to proceed.
        </p>
        <a href="http://localhost/moonarrowstudios/php/password_reset/reset_password.php?token=' . $token . '" 
            style="display: inline-block; margin-top: 20px; padding: 12px 25px; font-size: 14px; color: #FFFFFF; text-decoration: none; background-color: #007BFF; border-radius: 4px;">
            Reset Password
        </a>
        <p style="font-size: 12px; color: #777777; margin-top: 20px;">
            If you didn\'t request a password reset, please ignore this email. For assistance, contact our support team.
        </p>
        <hr style="border-top: 1px solid #444; margin: 20px 0;">
        <p style="font-size: 12px; color: #555555;">
            &copy; 2024 MoonArrow Studios. All rights reserved.
        </p>
    </div>
</div>';

        $mail->AltBody = 'Visit the following link to reset your password: http://localhost/reset_password.php?token=' . $token;

        $mail->send();
        header("Location: forgot_password.php?alert=Password reset email sent successfully.&type=success");
    } catch (Exception $e) {
        header("Location: forgot_password.php?alert=Failed to send email: {$mail->ErrorInfo}&type=danger");
    }
} else {
    header("Location: forgot_password.php?alert=Invalid request method.&type=warning");
}