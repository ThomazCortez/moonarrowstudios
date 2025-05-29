<?php
// notification_functions.php - Create this as a separate file
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendNotificationEmail($conn, $recipient_user_id, $type, $context_data) {
    // Get recipient's notification preferences and email
    $stmt = $conn->prepare("SELECT email, username, follow_notifications, comment_notifications, asset_comment_notifications, reply_notifications, notification_frequency FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $recipient_user_id);
    $stmt->execute();
    $recipient = $stmt->get_result()->fetch_assoc();
    
    if (!$recipient || !filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Check if user wants this type of notification
    switch ($type) {
        case 'follow':
            if ($recipient['follow_notifications'] != 1) return false;
            break;
        case 'comment_post':
            if ($recipient['comment_notifications'] != 1) return false;
            break;
        case 'comment_asset':
            if ($recipient['asset_comment_notifications'] != 1) return false;
            break;
        case 'reply':
            if ($recipient['reply_notifications'] != 1) return false;
            break;
        default:
            return false;
    }
    
    // Get sender information
    $stmt = $conn->prepare("SELECT username, profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $context_data['sender_id']);
    $stmt->execute();
    $sender = $stmt->get_result()->fetch_assoc();
    
    if (!$sender) return false;
    
    // Helper function to clean content for email display
    function cleanContentForEmail($content) {
        // Strip HTML tags and decode HTML entities
        $cleaned = html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8');
        // Remove extra whitespace and normalize line breaks
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));
        return $cleaned;
    }
    
    // Prepare email content based on notification type
    $subject = '';
    $body = '';
    $button_text = '';
    $button_url = '';
    
    switch ($type) {
        case 'follow':
            $subject = 'New Follower - ' . $sender['username'] . ' is now following you';
            $body = '<p>' . htmlspecialchars($sender['username']) . ' started following you on MoonArrow Studios!</p>';
            $button_text = 'View Profile';
            $button_url = 'http://localhost/moonarrowstudios/php/profile.php?id=' . $context_data['sender_id'];
            break;
            
        case 'comment_post':
            $subject = 'New Comment - ' . $sender['username'] . ' commented on your post';
            $body = '<p>' . htmlspecialchars($sender['username']) . ' commented on your post:</p>';
            
            // Clean the comment content
            $clean_comment = cleanContentForEmail($context_data['comment_content']);
            $truncated_comment = substr($clean_comment, 0, 150) . (strlen($clean_comment) > 150 ? '...' : '');
            
            $body .= '<blockquote style="background-color: #2a2a2a; padding: 15px; border-left: 4px solid #007BFF; margin: 15px 0; font-style: italic;">';
            $body .= '"' . htmlspecialchars($truncated_comment) . '"';
            $body .= '</blockquote>';
            $button_text = 'View Comment';
            $button_url = 'http://localhost/moonarrowstudios/php/admin/view_comment.php?id=' . $context_data['comment_id'];
            break;
            
        case 'comment_asset':
            $subject = 'New Comment - ' . $sender['username'] . ' commented on your asset';
            $body = '<p>' . htmlspecialchars($sender['username']) . ' commented on your asset:</p>';
            
            // Clean the comment content
            $clean_comment = cleanContentForEmail($context_data['comment_content']);
            $truncated_comment = substr($clean_comment, 0, 150) . (strlen($clean_comment) > 150 ? '...' : '');
            
            $body .= '<blockquote style="background-color: #2a2a2a; padding: 15px; border-left: 4px solid #007BFF; margin: 15px 0; font-style: italic;">';
            $body .= '"' . htmlspecialchars($truncated_comment) . '"';
            $body .= '</blockquote>';
            $button_text = 'View Comment';
            $button_url = 'http://localhost/moonarrowstudios/php/admin/view_comment.php?id=' . $context_data['comment_id'];
            break;
            
        case 'reply':
            $subject = 'New Reply - ' . $sender['username'] . ' replied to your comment';
            $body = '<p>' . htmlspecialchars($sender['username']) . ' replied to your comment:</p>';
            
            // Clean the reply content
            $clean_reply = cleanContentForEmail($context_data['reply_content']);
            $truncated_reply = substr($clean_reply, 0, 150) . (strlen($clean_reply) > 150 ? '...' : '');
            
            $body .= '<blockquote style="background-color: #2a2a2a; padding: 15px; border-left: 4px solid #007BFF; margin: 15px 0; font-style: italic;">';
            $body .= '"' . htmlspecialchars($truncated_reply) . '"';
            $body .= '</blockquote>';
            $button_text = 'View Reply';
            $button_url = 'http://localhost/moonarrowstudios/php/admin/view_comment.php?id=' . $context_data['reply_id'];
            break;
    }
    
    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'moonarrowstudios@gmail.com';
        $mail->Password   = 'jbws akjv bxvr xxac';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('noreply@moonnarrowstudios.com', 'MoonArrow Studios');
        $mail->addAddress($recipient['email']);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
            <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
                <div style="margin-bottom: 20px;">
                    <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
                </div>
                <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">🔔 Notification</h2>
                <div style="text-align: center;">
                    ' . $body . '
                </div>
                <a href="' . $button_url . '" 
                    style="display: inline-block; margin-top: 20px; padding: 12px 25px; font-size: 14px; color: #FFFFFF; text-decoration: none; background-color: #007BFF; border-radius: 4px;">
                    ' . $button_text . '
                </a>
                <p style="font-size: 12px; color: #777777; margin-top: 20px;">
                    Don\'t want to receive these notifications? You can update your preferences in your account settings.
                </p>
                <hr style="border-top: 1px solid #444; margin: 20px 0;">
                <p style="font-size: 12px; color: #555555;">
                    &copy; 2024 MoonArrow Studios. All rights reserved.
                </p>
            </div>
        </div>';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Notification email error: " . $e->getMessage());
        return false;
    }
}

// Function to trigger follow notification
function notifyNewFollower($conn, $follower_id, $following_id) {
    $context_data = array(
        'sender_id' => $follower_id
    );
    
    sendNotificationEmail($conn, $following_id, 'follow', $context_data);
}

// Function to trigger comment notification
function notifyNewComment($conn, $commenter_id, $post_owner_id, $comment_id, $comment_content, $is_asset = false) {
    $context_data = array(
        'sender_id' => $commenter_id,
        'comment_id' => $comment_id,
        'comment_content' => $comment_content
    );
    
    $type = $is_asset ? 'comment_asset' : 'comment_post';
    sendNotificationEmail($conn, $post_owner_id, $type, $context_data);
}

// Function to trigger reply notification
function notifyNewReply($conn, $replier_id, $original_commenter_id, $reply_id, $reply_content) {
    $context_data = array(
        'sender_id' => $replier_id,
        'reply_id' => $reply_id,
        'reply_content' => $reply_content
    );
    
    sendNotificationEmail($conn, $original_commenter_id, 'reply', $context_data);
}
?>