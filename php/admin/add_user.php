<?php
// Start session
session_start();

// Set base URL
$baseUrl = '/moonarrowstudios/';

// Check if user is logged in and is an admin
require_once '../db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $baseUrl . "../sign_in/sign_in_html.php");
    exit();
}

// Verify admin status
$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_role);
$stmt->fetch();
$stmt->close();

// Redirect if not admin
if ($user_role !== 'admin') {
    header("Location: " . $baseUrl);
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    $errors = [];
    
    // Validate username (alphanumeric, 3-20 chars)
    if (empty($username) || strlen($username) < 3 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username must be 3-20 characters and contain only letters, numbers, and underscores.";
    }
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Validate role
    if ($role !== 'admin' && $role !== 'user') {
        $errors[] = "Invalid role selected.";
    }
    
    // Validate status
    if ($status !== 'active' && $status !== 'suspended') {
        $errors[] = "Invalid status selected.";
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Username already exists. Please choose a different username.";
    }
    $stmt->close();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email address already exists in our system.";
    }
    $stmt->close();
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
        header("Location: manage_users.php");
        exit();
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User '$username' has been added successfully!";
    } else {
        $_SESSION['error_messages'] = ["Database error: " . $conn->error];
    }
    $stmt->close();
    
    // Redirect back to manage users page
    header("Location: manage_users.php");
    exit();
} else {
    // If not a POST request, redirect to manage users page
    header("Location: manage_users.php");
    exit();
}
?>