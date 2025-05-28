<?php
// confirm_account_deletion.php
session_start();
include 'db_connect.php';

$message = '';
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token and check if it's not expired
    $stmt = $conn->prepare("SELECT user_id, email FROM account_deletions WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $deletion_data = $result->fetch_assoc();
        $user_id = $deletion_data['user_id'];
        $email = $deletion_data['email'];
        
        // Get user information before deletion
        $stmt = $conn->prepare("SELECT username, profile_picture, banner FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_data = $stmt->get_result()->fetch_assoc();
        
        if ($user_data) {
            // Begin transaction for safe deletion
            $conn->begin_transaction();
            
            try {
                // Delete user's profile picture if exists
                if ($user_data['profile_picture']) {
                    $profile_pic_path = str_replace('\moonarrowstudios', '..', $user_data['profile_picture']);
                    if (file_exists($profile_pic_path)) {
                        unlink($profile_pic_path);
                    }
                }
                
                // Delete user's banner if exists
                if ($user_data['banner']) {
                    $banner_path = str_replace('\moonarrowstudios', '..', $user_data['banner']);
                    if (file_exists($banner_path)) {
                        unlink($banner_path);
                    }
                }
                
                // Delete related data (adjust these based on your database structure)
                // Note: If you have foreign key constraints with CASCADE, some of these might be automatic
                
                // Delete from password_resets
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                
                // Delete from email_changes
                $stmt = $conn->prepare("DELETE FROM email_changes WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Delete from account_deletions (cleanup)
                $stmt = $conn->prepare("DELETE FROM account_deletions WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Add any other related table deletions here
                // Examples (uncomment and modify as needed):
                /*
                // Delete user posts
                $stmt = $conn->prepare("DELETE FROM posts WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Delete user comments
                $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Delete user messages
                $stmt = $conn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
                $stmt->bind_param("ii", $user_id, $user_id);
                $stmt->execute();
                
                // Delete user followers/following relationships
                $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?");
                $stmt->bind_param("ii", $user_id, $user_id);
                $stmt->execute();
                
                // Delete user likes
                $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Delete user sessions (if you store them in database)
                $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                */
                
                // Finally, delete the user account
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                // Log out if the current session belongs to the deleted user
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                    session_destroy();
                }
                
                $message = "Account successfully deleted. We're sorry to see you go!";
                
                // Optional: Send farewell email
                require '../vendor/autoload.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'moonarrowstudios@gmail.com';
                    $mail->Password   = 'jbws akjv bxvr xxac';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    $mail->setFrom('noreply@moonnarrowstudios.com', 'MoonArrow Studios');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Account Successfully Deleted';
                    $mail->Body = '
                    <div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
                        <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
                            <div style="margin-bottom: 20px;">
                                <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
                            </div>
                            <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">Account Successfully Deleted</h2>
                            <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                                Your MoonArrow Studios account (' . htmlspecialchars($user_data['username']) . ') has been permanently deleted as requested.
                            </p>
                            <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                                All your data has been removed from our systems. Thank you for being part of our community.
                            </p>
                            <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6;">
                                If you change your mind, you\'re always welcome to create a new account with us in the future.
                            </p>
                            <hr style="border-top: 1px solid #444; margin: 20px 0;">
                            <p style="font-size: 12px; color: #555555;">
                                &copy; 2024 MoonArrow Studios. All rights reserved.
                            </p>
                        </div>
                    </div>';

                    $mail->send();
                } catch (Exception $e) {
                    // Email failed, but account is already deleted
                    error_log("Farewell email failed: " . $e->getMessage());
                }
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error = "An error occurred during account deletion. Please try again or contact support.";
                error_log("Account deletion error: " . $e->getMessage());
            }
        } else {
            $error = "User account not found.";
        }
    } else {
        $error = "Invalid or expired deletion link. Deletion links expire after 24 hours for security.";
    }
} else {
    $error = "No deletion token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deletion - MoonArrow Studios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #18141D;
            color: #FFFFFF;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card {
            background-color: #24222A;
            border: 1px solid #333;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
        }
        
        .btn-primary {
            background-color: #007BFF;
            border-color: #007BFF;
        }
        
        .btn-primary:hover {
            background-color: #0056B3;
            border-color: #0056B3;
        }
        
        .text-success {
            color: #28A745 !important;
        }
        
        .text-danger {
            color: #DC3545 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 150px; height: auto;">
                </div>
                
                <?php if ($message): ?>
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h3 class="text-success">Account Deleted</h3>
                        <p class="text-light"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                    <a href="../index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i> Return to Home
                    </a>
                <?php elseif ($error): ?>
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h3 class="text-danger">Deletion Failed</h3>
                        <p class="text-light"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                    <a href="../settings.php?tab=security" class="btn btn-primary me-2">
                        <i class="fas fa-arrow-left me-2"></i> Back to Settings
                    </a>
                    <a href="../index.php" class="btn btn-outline-light">
                        <i class="fas fa-home me-2"></i> Home
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>