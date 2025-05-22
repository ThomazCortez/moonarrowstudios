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

        $successMessage = 'Password has been reset successfully. Redirecting...';
        $disableInput = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../css/css2.css">
    <link rel="icon" href="/moonarrowstudios/media/moon.ico" type="image/x-icon" />
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
                .custom-alert-success {
                    background-color: #12281e;
                    color: #7ee2b8;
                }
                .custom-alert-danger {
                    background-color: #2e0a12;
                    color: #fda4af;
                }
                .custom-alert-warning {
                    background-color: #2e2a0e;
                    color: #fde047;
                }
                .custom-alert-info {
                    background-color: #092c42;
                    color: #7dd3fc;
                }
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

        /* Custom Alert Styles */
        .alert-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            pointer-events: none;
        }

        .custom-alert {
            position: relative;
            margin: 16px auto;
            max-width: 500px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            pointer-events: auto;
            overflow: hidden;
            transform: translateY(-100%);
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s linear;
        }

        .custom-alert.show {
            transform: translateY(0);
            opacity: 1;
        }

        .custom-alert.hiding {
            transform: translateY(-100%);
            opacity: 0;
        }

        .custom-alert .progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            border-radius: 0;
            background-color: rgba(0, 0, 0, 0.1);
            padding: 0;
            margin: 0;
        }

        .custom-alert .progress-bar {
            transition: width linear 5000ms;
            width: 100%;
            height: 100%;
        }

        .custom-alert-success .progress-bar {
            background-color: #198754;
        }

        .custom-alert-danger .progress-bar {
            background-color: #dc3545;
        }

        .custom-alert-warning .progress-bar {
            background-color: #ffc107;
        }

        .custom-alert-info .progress-bar {
            background-color: #0dcaf0;
        }

        .custom-alert-content {
            display: flex;
            align-items: center;
            padding: 12px 16px;
        }

        .custom-alert-icon {
            margin-right: 12px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-alert-message {
            flex-grow: 1;
        }

        .custom-alert-close {
            background: transparent;
            border: none;
            color: inherit;
            opacity: 0.7;
            padding: 0 4px;
            cursor: pointer;
        }

        .custom-alert-close:hover {
            opacity: 1;
        }

        .custom-alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .custom-alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .custom-alert-warning {
            background-color: #fff3cd;
            color: #664d03;
        }

        .custom-alert-info {
            background-color: #cff4fc;
            color: #055160;
        }

        /* Existing form styles */
        .logo {
            display: block;
            margin: 0 auto 20px auto;
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

        /* Add/Update these styles in your CSS */
.card {
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    transform: translateZ(0); /* Hardware acceleration */
    will-change: transform; /* Prepare browser for animation */
    border: 1px solid transparent; /* Add this line */
}

.card::before,
.card::after {
    content: '';
    position: absolute;
    left: 0;
    width: 100%;
    height: 2px;
    background: rgba(88, 166, 255, 0.3); /* Use your theme's blue color */
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
    opacity: 0;
}

.card::before {
    top: 0;
    transform: translateX(-105%);
    box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
}

.card::after {
    bottom: 0;
    transform: translateX(105%);
    box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
}

.card:hover {
    box-shadow: 0 0 25px 5px rgba(88, 166, 255, 0.2),
                0 4px 20px rgba(0, 0, 0, 0.3) !important;
    border-color: rgba(88, 166, 255, 0.3) !important;
}

.card:hover::before,
.card:hover::after {
    transform: translateX(0);
    opacity: 1;
}

.card-body {
    position: relative;
    z-index: 1; /* Ensure content stays above borders */
}

.card-body::before,
.card-body::after {
    content: '';
    position: absolute;
    top: 0;
    height: 100%;
    width: 2px;
    background: rgba(88, 166, 255, 0.3);
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
    opacity: 0;
    box-shadow: 0 0 15px rgba(88, 166, 255, 0.3);
}

.card-body::before {
    left: 0;
    transform: translateY(105%);
}

.card-body::after {
    right: 0;
    transform: translateY(-105%);
}

.card:hover .card-body::before,
.card:hover .card-body::after {
    transform: translateY(0);
    opacity: 1;
}
    </style>
</head>

<body>
    <!-- Alert Container -->
    <div class="alert-container" id="alertContainer"></div>

    <div class="reset-password-container card">
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
        <div class="text-center">
            <small>If it doesn't redirect, you can click <a href="../sign_in/sign_in_html.php" class="text-decoration-none">here</a>.</small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Alert functions
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        const alertElement = document.createElement('div');
        alertElement.className = `custom-alert custom-alert-${type}`;
        let iconClass = 'bi-info-circle';
        if (type === 'success') iconClass = 'bi-check-circle';
        if (type === 'danger')  iconClass = 'bi-exclamation-triangle';
        if (type === 'warning') iconClass = 'bi-exclamation-circle';

        alertElement.innerHTML = `
            <div class="custom-alert-content">
                <div class="custom-alert-icon"><i class="bi ${iconClass}"></i></div>
                <div class="custom-alert-message">${message}</div>
                <button type="button" class="custom-alert-close"><i class="bi bi-x"></i></button>
            </div>
            <div class="progress">
                <div class="progress-bar"></div>
            </div>
        `;

        alertContainer.appendChild(alertElement);

        requestAnimationFrame(() => alertElement.classList.add('show'));

        const progressBar = alertElement.querySelector('.progress-bar');
        progressBar.style.transition = 'width linear 5000ms';
        progressBar.style.width = '100%';
        setTimeout(() => { progressBar.style.width = '0%'; }, 50);

        const dismissTimeout = setTimeout(() => {
            dismissAlert(alertElement);
        }, 5050);

        alertElement.querySelector('.custom-alert-close').addEventListener('click', () => {
            clearTimeout(dismissTimeout);
            dismissAlert(alertElement);
        });
    }

    function dismissAlert(alertElement) {
        if (!alertElement || alertElement.classList.contains('hiding')) return;
        alertElement.classList.add('hiding');
        alertElement.classList.remove('show');
        setTimeout(() => { alertElement.remove(); }, 300);
    }

    // Password toggle visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }

    // Redirect on success
    <?php if ($successMessage): ?>
    setTimeout(() => {
        window.location.href = '../sign_in/sign_in_html.php';
    }, 5050);
    <?php endif; ?>
    </script>

    <?php if ($successMessage || $errorMessage): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($successMessage): ?>
            showAlert('<?php echo addslashes($successMessage); ?>', 'success');
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            showAlert('<?php echo addslashes($errorMessage); ?>', 'danger');
        <?php endif; ?>
    });
    </script>
    <?php endif; ?>
</body>
</html>