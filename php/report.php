<?php
session_start();
require 'db_connect.php';
require '../vendor/autoload.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$validTypes = ['post', 'asset', 'comment', 'reply', 'user'];
$contentType = $_POST['content_type'] ?? '';
$contentId = (int) ($_POST['content_id'] ?? 0);
$reason = htmlspecialchars($_POST['reason'] ?? '');
$details = htmlspecialchars($_POST['details'] ?? '');
$reporterId = (int) $_SESSION['user_id'];

// Validate input
if (!in_array($contentType, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid content type']);
    exit;
}

if (empty($reason)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Reason is required']);
    exit;
}

// Prevent users from reporting themselves
if ($contentType === 'user' && $contentId === $reporterId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cannot report yourself']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Save report to database
    $stmt = $conn->prepare("INSERT INTO reports 
        (reporter_id, content_type, content_id, reason, details) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isiss', $reporterId, $contentType, $contentId, $reason, $details);
    $stmt->execute();

    // Get content details for email
    $contentLink = '';
    $contentInfo = [];
    $authorId = null;

    if (in_array($contentType, ['comment', 'reply'])) {
        // Handle comments and replies
        $commentTable = ($contentType === 'reply') ? 'comments' : 'comments';
        $assetCommentTable = ($contentType === 'reply') ? 'comments_asset' : 'comments_asset';

        // Check both comment tables
        $stmt = $conn->prepare("SELECT * FROM $commentTable WHERE id = ?");
        $stmt->bind_param('i', $contentId);
        $stmt->execute();
        $contentInfo = $stmt->get_result()->fetch_assoc();

        if (!$contentInfo) {
            $stmt = $conn->prepare("SELECT * FROM $assetCommentTable WHERE id = ?");
            $stmt->bind_param('i', $contentId);
            $stmt->execute();
            $contentInfo = $stmt->get_result()->fetch_assoc();
        }

        if ($contentInfo) {
            $isAssetComment = isset($contentInfo['asset_id']);
            $baseUrl = 'http://localhost/moonarrowstudios/php/';
            
            if ($isAssetComment) {
                $contentLink = $baseUrl . "view_asset.php?id={$contentInfo['asset_id']}";
                if ($contentType === 'reply') {
                    $contentLink .= "&comment={$contentInfo['parent_id']}&reply=$contentId";
                } else {
                    $contentLink .= "&comment=$contentId";
                }
            } else {
                $contentLink = $baseUrl . "view_post.php?id={$contentInfo['post_id']}";
                if ($contentType === 'reply') {
                    $contentLink .= "&comment={$contentInfo['parent_id']}&reply=$contentId";
                } else {
                    $contentLink .= "&comment=$contentId";
                }
            }
            $authorId = $contentInfo['user_id'] ?? null;
        }
    } elseif ($contentType === 'user') {
        // Handle user reports
        $baseUrl = 'http://localhost/moonarrowstudios/php/';
        $contentLink = $baseUrl . "profile.php?id=$contentId";
        $authorId = $contentId; // This is the ID of the user being reported
    } else {
        // Handle posts and assets
        $baseUrl = 'http://localhost/moonarrowstudios/php/';
        $contentLink = $baseUrl . (
            $contentType === 'asset' 
            ? "view_asset.php?id=$contentId" 
            : "view_post.php?id=$contentId"
        );
    }

    // Update reported count in respective table
    $tableMap = [
        'post' => ['table' => 'posts', 'id_field' => 'id'],
        'asset' => ['table' => 'assets', 'id_field' => 'id'],
        'comment' => ['table' => 'comments', 'id_field' => 'id'],
        'reply' => ['table' => 'comments', 'id_field' => 'id'],
        'user' => ['table' => 'users', 'id_field' => 'user_id']
    ];
    
    $tableInfo = $tableMap[$contentType];
    $updateQuery = "UPDATE {$tableInfo['table']} 
                   SET reported_count = reported_count + 1 
                   WHERE {$tableInfo['id_field']} = ?";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('i', $contentId);
    $stmt->execute();

    // Get reporter's username
    $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $reporterId);
    $stmt->execute();
    $reporterResult = $stmt->get_result();
    $reporterUsername = $reporterResult->fetch_assoc()['username'] ?? 'Unknown';

    // Get author's user_id if not already set
    if (!$authorId && in_array($contentType, ['post', 'asset'])) {
        $table = ($contentType === 'post') ? 'posts' : 'assets';
        $stmt = $conn->prepare("SELECT user_id FROM $table WHERE id = ?");
        $stmt->bind_param('i', $contentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $authorId = $result->fetch_assoc()['user_id'] ?? null;
    }

    // Get author's username
    $authorUsername = 'Unknown';
    if ($authorId) {
        $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $authorId);
        $stmt->execute();
        $authorResult = $stmt->get_result();
        $authorUsername = $authorResult->fetch_assoc()['username'] ?? 'Unknown';
    }

    // Send email
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'moonarrowstudios@gmail.com';
        $mail->Password = 'jbws akjv bxvr xxac';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('noreply@moonarrowstudios.com', 'Reporting System');
        $mail->addAddress('moonarrowstudiosreports@gmail.com');
        
        $mail->isHTML(true);
        $mail->Subject = "New Content Report";

        $htmlContent = '
        <div style="font-family: Arial, sans-serif; text-align: center; background-color: #18141D; padding: 20px; color: #FFFFFF;">
            <div style="max-width: 500px; margin: auto; background-color: #24222A; border: 1px solid #333; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);">
                <div style="margin-bottom: 20px;">
                    <img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" alt="MoonArrow Studios Logo" style="width: 120px; height: auto;">
                </div>
                <h2 style="font-size: 20px; color: #FFFFFF; margin-bottom: 10px;">New Content Report</h2>
                <div style="text-align: left; background-color: #2C2A32; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 7px 0;">
                        <strong>Reporter ID:</strong> ' . $reporterId . '
                    </p>
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 7px 0;">
                        <strong>Reporter Username:</strong> ' . htmlspecialchars($reporterUsername) . '
                    </p>
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 7px 0;">
                        <strong>Reported User ID:</strong> ' . htmlspecialchars($authorId) . '
                    </p>
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 7px 0;">
                        <strong>Reported Username:</strong> ' . htmlspecialchars($authorUsername) . '
                    </p>
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 7px 0;">
                        <strong>Type:</strong> ' . ucfirst($contentType) . '
                    </p>
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 7px 0;">
                        <strong>Reason:</strong> ' . $reason . '
                    </p>
                    <p style="font-size: 14px; color: #CCCCCC; line-height: 1.6; margin: 7px 0;">
                        <strong>Details:</strong><br>' . nl2br($details) . '
                    </p>';

        if ($contentLink) {
            $htmlContent .= '
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="' . $contentLink . '" 
                            style="display: inline-block; padding: 10px 20px; font-size: 14px; color: #FFFFFF; text-decoration: none; background-color: #007BFF; border-radius: 4px;">
                            View Reported Content
                        </a>
                    </div>';
        }

        $htmlContent .= '
                </div>
                <p style="font-size: 12px; color: #777777; margin-top: 20px;">
                    This is an automated message. Please review this report at your earliest convenience.
                </p>
                <hr style="border-top: 1px solid #444; margin: 20px 0;">
                <p style="font-size: 12px; color: #555555;">
                    &copy; 2024 MoonArrow Studios. All rights reserved.
                </p>
            </div>
        </div>';

        $textContent = "New Content Report\n\n"
            . "Reporter ID: $reporterId\n"
            . "Reporter Username: $reporterUsername\n"
            . "Reported User ID: $authorId\n"
            . "Reported Username: $authorUsername\n"
            . "Type: " . ucfirst($contentType) . "\n"
            . "Reason: $reason\n"
            . "Details: $details\n"
            . ($contentLink ? "Link: $contentLink" : '');

        $mail->Body = $htmlContent;
        $mail->AltBody = $textContent;
        
        $mail->send();
    } catch (Exception $e) {
        error_log('Email send error: ' . $e->getMessage());
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    error_log('Report error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}