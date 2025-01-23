<?php
// Include database connection
include '../db_connect.php';
require '../../vendor/autoload.php'; // Include PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password for security
    $role = 'user'; // Default role

    // Validate inputs
    if (!empty($username) && !empty($email) && !empty($password)) {
        // Check if the email already exists
        $check_email_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $check_email_stmt->store_result();

        if ($check_email_stmt->num_rows > 0) {
            header("Location: ../sign_up/sign_up_html.php?alert=This email is already registered. Please use a different email.&type=danger");
            exit;
        } else {
            // Check if the username already exists
            $check_username_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $check_username_stmt->bind_param("s", $username);
            $check_username_stmt->execute();
            $check_username_stmt->store_result();

            if ($check_username_stmt->num_rows > 0) {
                header("Location: ../sign_up/sign_up_html.php?alert=This username is already taken. Please choose a different username.&type=danger");
                exit;
            } else {
                // Prepare the SQL statement for insertion
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $username, $email, $password, $role);

                if ($stmt->execute()) {
                    // Send welcome email
                    $mail = new PHPMailer(true);
                    try {
                        //Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
                        $mail->SMTPAuth = true;
                        $mail->Username = 'moonarrowstudios@gmail.com'; // Your Gmail address
                        $mail->Password = 'jbws akjv bxvr xxac'; // Your Gmail password or app password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        //Recipients
                        $mail->setFrom('noreply@moonnarrowstudios.com', 'MoonArrow Studios');
                        $mail->addAddress($email, $username); // Add the user's email

                        //Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Welcome to MoonArrow Studios!';
                        $mail->Body = '
<div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
    <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
        <!-- Logo Section -->
        <div style="margin-bottom: 20px;">
            <img src="https://i.postimg.cc/cLbHLSL3/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
        </div>
        <!-- Content Section -->
        <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">Welcome to MoonArrow Studios, ' . htmlspecialchars($username) . '!</h2>
        <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
            We\'re thrilled to have you join us. Explore our forums, create posts, explore assets for your projects, interact with the community, and most importantly, have fun!
        </p>
        <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
            Best regards,<br>MoonArrow Studios Team
        </p>
        <hr style="border-top: 1px solid #444; margin: 20px 0;">
        <p style="font-size: 12px; color: #555555;">
            &copy; 2024 MoonArrow Studios. All rights reserved.
        </p>
    </div>
</div>';

                        $mail->send();
                        header("Location: ../sign_up/sign_up_html.php?alert=Sign up successful!&type=success");
                        exit;
                    } catch (Exception $e) {
                        header("Location:../sign_up/sign_up_html.php?alert=Sign up successful, but email could not be sent. Error: {$mail->ErrorInfo}&type=warning");
                        exit;
                    }
                } else {
                    header("Location: ../sign_up/sign_up_html.php?alert=Error: " . $stmt->error . "&type=danger");
                    exit;
                }

                $stmt->close();
            }

            $check_username_stmt->close();
        }

        $check_email_stmt->close();
    } else {
        header("Location: ../sign_up/sign_up_html.php?alert=Please fill in all fields!&type=warning");
        exit;
    }
}

$conn->close();
?>