<?php
require '../config.php';

$token = $_GET['token'] ?? '';
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Validate token
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$token]);
    $resetEntry = $stmt->fetch();

    if (!$resetEntry) {
        $errorMessage = 'Invalid or expired token.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['password'];

    // Validate token
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$token]);
    $resetEntry = $stmt->fetch();

    if (!$resetEntry) {
        $errorMessage = 'Invalid or expired token.';
    } else {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update the user's password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $resetEntry['email']]);

        // Delete the token
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        $successMessage = 'Password has been reset successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <!-- Darkly Theme from Bootswatch -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/css2.css">
    <style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .reset-password-container {
        width: 100%;
        max-width: 400px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        padding: 30px;
    }

    .logo {
        display: block;
        margin: 0 auto 20px auto;
    }

    .alert {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1050;
        max-width: 90%;
    }

    .input-group-text {
        cursor: pointer;
    }
    </style>
</head>

<body>
    <!-- Success Alert -->
    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Error Alert -->
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="reset-password-container">
        <!-- Logo -->
        <img src="../../media/horizontal_logo.png" alt="Logo" class="logo" width="180">
        <h2 class="text-center">Reset Password</h2>
        <p class="text-center">Don't worry, happens to the best of us.</p>
        <form action="reset_password.php" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password" required>
                    <button type="button" id="togglePassword" class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-reset-password w-100">Reset Password</button>
        </form>
        <hr>
        <div class="text-center mt-3">
            <small>Done? <a href="../sign_in/sign_in_html.php" class="text-decoration-none">Sign in here</a>.</small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Password toggle visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        // Toggle the password visibility
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle the icon
        this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.classList.remove('show');
            alert.addEventListener('transitionend', () => alert.remove());
        });
    }, 5000);
    </script>
</body>

</html>