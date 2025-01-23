<?php
// Start the session at the top of the page to check for user login state
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "moonarrowstudios";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
include 'header.php';
?>

<!DOCTYPE html>
<head>
    <title>Settings - MoonArrow Studios</title>
</head>
<body>
  
</body>
</html>