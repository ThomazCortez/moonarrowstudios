<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>MoonArrow Studios - Forgot Password</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Custom Alert Animation Styles */
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
	</style>
</head>

<body class="">
	<div class="container-fluid vh-100">
		<div class="row h-100">
			<!-- Forgot Password Form Section -->
			<div class="col-md-6 d-flex flex-column p-3">
				<!-- Logo Section -->
				<div class="mb-4">
					<img src="../../media/horizontal_logo.png" alt="Logo" class="img-fluid logo" style="max-width: 200px;">
				</div>
				<!-- Alert Section -->
				<div class="alert-container" id="alertContainer"></div>
				<!-- Form Section -->
				<div class="flex-grow-1 d-flex align-items-center">
					<div class="form-container w-100 px-3 px-md-5">
						<h1 class="mb-4">Forgot Password</h1>
						<p>Enter your email and we'll send you a link to reset your password.</p>
						<form id="forgotPasswordForm" action="send_reset.php" method="post">
							<div class="mb-3">
								<label for="email" class="form-label">Email address</label>
								<input type="email" class="form-control" id="email" placeholder="yourname@example.com" name="email" required>
							</div>
							<button id="submitButton" type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                            <button id="spinnerButton" class="btn btn-primary w-100" type="button" disabled style="display: none;">
                                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                <span role="status">Sending link...</span>
                            </button>
						</form>
						<hr class="my-4">
						<div class="centered-footer">
							<a href="../sign_in/sign_in_html.php" class="text-decoration-none">Back to Sign In</a>
						</div>
					</div>
				</div>
			</div>
			<!-- Art/Image Section -->
			<div class="col-md-6 art-section p-0 d-none d-md-block">
				<img src="../../media/output-onlinepngtools.png" alt="Background Image" class="w-100 h-100">
			</div>
		</div>
	</div>
	<!-- Bootstrap JS -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
	<script>

        document.getElementById('forgotPasswordForm').addEventListener('submit', function(event) {
            document.getElementById('submitButton').style.display = 'none';
            document.getElementById('spinnerButton').style.display = 'block';
        });

        document.addEventListener('DOMContentLoaded', () => {
    const artSection = document.querySelector('.art-section img');
    if(artSection) {
        artSection.style.opacity = '0';
        artSection.style.transition = 'opacity 1s ease-in-out';
        setTimeout(() => { artSection.style.opacity = '1'; }, 1000);
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('alert')) {
        showAlert(urlParams.get('alert'), urlParams.get('type') || 'info');
    }
});

// Add alert functions
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

// Remove the old alert timeout
document.getElementById('forgotPasswordForm').addEventListener('submit', function(event) {
    document.getElementById('submitButton').style.display = 'none';
    document.getElementById('spinnerButton').style.display = 'block';
});
	</script>
</body>

</html>