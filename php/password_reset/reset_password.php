<?php
require '../config.php';

$token = $_GET['token'] ?? '';
$successMessage = '';
$errorMessage = '';
$disableInput = false;

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Validate token
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$token]);
    $resetEntry = $stmt->fetch();

    if (!$resetEntry) {
        $errorMessage = 'Invalid or expired token.';
        $disableInput = true;
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
        $disableInput = true;
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
        $disableInput = true;
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
        :root {
            --color-canvas-default: #ffffff;
            --color-canvas-subtle: #f6f8fa;
            --color-border-default: #d0d7de;
            --color-border-muted: #d8dee4;
            --color-btn-primary-bg: #2da44e;
            --color-btn-primary-hover-bg: #2c974b;
            --color-fg-default: #1F2328;
            --color-fg-muted: #656d76;
            --color-accent-fg: #0969da;
            --color-input-bg: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --color-canvas-default: #0d1117;
                --color-canvas-subtle: #161b22;
                --color-border-default: #30363d;
                --color-border-muted: #21262d;
                --color-btn-primary-bg: #238636;
                --color-btn-primary-hover-bg: #2ea043;
                --color-fg-default: #c9d1d9;
                --color-fg-muted: #8b949e;
                --color-accent-fg: #58a6ff;
                --color-input-bg: #0d1117;
            }
        }
    
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
        }

        .reset-password-container {
            width: 100%;
            max-width: 400px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            padding: 30px;
            background-color: var(--color-canvas-subtle);
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

        .form-control {
            padding: 5px 12px;
            font-size: 14px;
            line-height: 20px;
            color: var(--color-fg-default);
            background-color: var(--color-input-bg);
            border: 1px solid var(--color-border-default);
            border-radius: 6px;
            box-shadow: var(--color-primer-shadow-inset);
        }

        .form-control:focus {
            border-color: #0969da;
            outline: none;
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.3);
        }

        .form-control:disabled {
            background-color: var(--color-canvas-subtle);
            cursor: not-allowed;
        }

        .form-label {
            font-weight: 400;
            font-size: 14px;
            color: var(--color-fg-default);
        }

        .btn-primary {
            color: #ffffff;
            background-color: var(--color-btn-primary-bg);
            border-color: rgba(27, 31, 36, 0.15);
            box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1);
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            padding: 5px 16px;
            border-radius: 6px;
        }

        .btn-primary:hover {
            background-color: var(--color-btn-primary-hover-bg);
        }

        .btn-primary:disabled {
            background-color: var(--color-btn-primary-bg);
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-outline-secondary {
            color: var(--color-fg-muted);
            border-color: var(--color-border-default);
            background-color: var(--color-canvas-default);
        }

        .btn-outline-secondary:hover {
            background-color: var(--color-canvas-subtle);
            border-color: var(--color-border-muted);
        }

        .btn-outline-secondary:disabled {
            background-color: var(--color-canvas-subtle);
            cursor: not-allowed;
        }

        h1 {
            font-size: 24px;
            font-weight: 300;
            letter-spacing: -0.5px;
        }

        a {
            color: var(--color-accent-fg);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .input-group {
            border-radius: 6px;
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        hr {
            border-color: var(--color-border-muted);
        }

        .centered-footer {
            text-align: center;
        }

        .centered-footer p {
            margin-bottom: 8px;
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
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-control" 
                           placeholder="Enter new password" 
                           required
                           <?php if ($disableInput): ?>disabled<?php endif; ?>>
                    <button type="button" 
                            id="togglePassword" 
                            class="btn btn-outline-secondary"
                            <?php if ($disableInput): ?>disabled<?php endif; ?>>
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" 
                    class="btn btn-primary btn-reset-password w-100"
                    <?php if ($disableInput): ?>disabled<?php endif; ?>>
                Reset Password
            </button>
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

    // If there's a success message, redirect to sign in page after 5 seconds
    <?php if ($successMessage): ?>
    setTimeout(() => {
        window.location.href = '../sign_in/sign_in_html.php';
    }, 5000);
    <?php endif; ?>
    </script>
</body>
</html>