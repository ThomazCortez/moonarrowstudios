<?php
// Start the session
session_start();

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the homepage after logging out
header("Location: /moonarrowstudios/index.php");
exit();
?>
