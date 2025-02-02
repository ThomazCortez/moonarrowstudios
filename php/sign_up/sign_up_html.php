<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoonArrow Studios - Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
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
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
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

        .btn-outline-secondary {
            color: var(--color-fg-muted);
            border-color: var(--color-border-default);
            background-color: var(--color-canvas-default);
        }

        .btn-outline-secondary:hover {
            background-color: var(--color-canvas-subtle);
            border-color: var(--color-border-muted);
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

        .art-section {
            border-left: 1px solid var(--color-border-muted);
        }

        .centered-footer {
            text-align: center;
        }

        .centered-footer p {
            margin-bottom: 8px;
        }

        /* Maintain your original animations */
        body {
            opacity: 0;
            animation: fadeIn 0.3s ease-in forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .logo {
            transform: translateY(-50px);
            opacity: 0;
            animation: slideDown 0.3s ease-out forwards;
            animation-delay: 0.2s;
        }

        @keyframes slideDown {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .form-container {
            transform: translateY(20px);
            opacity: 0;
            animation: fadeUp 0.3s ease-out forwards;
            animation-delay: 0.5s;
        }

        @keyframes fadeUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid vh-100">
        <div class="row h-100">
            <!-- Sign Up Form Section -->
            <div class="col-md-6 d-flex flex-column">
                <!-- Logo Section -->
                <div class="p-3">
                    <img src="../../media/horizontal_logo.png" alt="Logo" class="img-fluid logo" style="max-width: 200px;">
                </div>
                <!-- Alert Section -->
                <div class="alert-container">
                    <?php if (isset($_GET['alert'])): ?>
                    <div class="alert alert-<?= htmlspecialchars($_GET['type']) ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_GET['alert']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Form Section -->
                <div class="flex-grow-1 d-flex align-items-center">
                    <div class="form-container w-100 px-3 px-md-5">
                        <h1 class="mb-4">Sign Up</h1>
                        <form id="signUpForm" action="sign_up.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="example@email.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <button type="button" id="togglePassword" class="btn btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button id="submitButton" type="submit" class="btn btn-primary w-100">Sign Up</button>
                            <button id="spinnerButton" class="btn btn-primary w-100" type="button" disabled style="display: none;">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                <span role="status">Signing Up...</span>
                            </button>
                        </form>
                        <hr class="my-4">
                        <div class="centered-footer">
                            <p>Already have an account? <a href="../sign_in/sign_in_html.php" class="text-decoration-none">Sign in</a></p>
                            <p>Click <a href="../forum.php" class="text-decoration-none">here</a> to go back to the main page.</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Art/Image Section -->
            <div class="col-md-6 art-section p-0 d-none d-md-block">
                <img src="../../media/loginnn.png" alt="Background Image" class="w-100 h-100">
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.classList.remove('show');
                alert.addEventListener('transitionend', () => alert.remove());
            });
        }, 5000);

        document.getElementById('signUpForm').addEventListener('submit', function(event) {
            document.getElementById('submitButton').style.display = 'none';
            document.getElementById('spinnerButton').style.display = 'block';
        });

        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });

        document.addEventListener('DOMContentLoaded', () => {
            const artSection = document.querySelector('.art-section img');
            if(artSection) {
                artSection.style.opacity = '0';
                artSection.style.transition = 'opacity 1s ease-in-out';
                setTimeout(() => {
                    artSection.style.opacity = '1';
                }, 1000);
            }
        });
    </script>
</body>
</html>