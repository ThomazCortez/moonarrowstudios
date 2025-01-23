<?php
// Include database connection
include 'db_connect.php';

session_start(); // Start session to manage user login state

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $db_username, $db_password, $role);
            $stmt->fetch();

            if (password_verify($password, $db_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $role;

                header("Location: index.php");
                exit();
            } else {
                // Redirect with error alert
                header("Location: sign_in_html.php?alert=Invalid+password&type=danger");
                exit();
            }
        } else {
            // Redirect with error alert
            header("Location: sign_in_html.php?alert=User+not+found&type=warning");
            exit();
        }

        $stmt->close();
    } else {
        // Redirect with info alert
        header("Location: sign_in_html.php?alert=Please+fill+in+all+fields&type=info");
        exit();
    }
}

$conn->close();
?>