<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Bootstrap Icons -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymou.s">
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
    --color-card-bg: #ffffff;
    --color-card-border: #d0d7de;
    --color-header-bg: #f6f8fa;
    --color-modal-bg: #ffffff;
    --color-alert-error-bg: #FFEBE9;
    --color-alert-error-border: rgba(255, 129, 130, 0.4);
    --color-alert-error-fg: #cf222e;
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
        --color-card-bg: #161b22;
        --color-card-border: #30363d;
        --color-header-bg: #161b22;
        --color-modal-bg: #161b22;
        --color-alert-error-bg: #ff000015;
        --color-alert-error-border: rgba(248, 81, 73, 0.4);
        --color-alert-error-fg: #f85149;
    }
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif;
    background-color: var(--color-canvas-default);
    color: var(--color-fg-default);
    line-height: 1.5;
    font-size: 14px;
}

/* Buttons */
.btn {
    border-radius: 6px;
    padding: 5px 16px;
    font-size: 14px;
    font-weight: 500;
    line-height: 20px;
    transition: .2s cubic-bezier(0.3, 0, 0.5, 1);
}

.btn-primary {
    color: #ffffff;
    background-color: var(--color-btn-primary-bg);
    border: 1px solid rgba(27, 31, 36, 0.15);
    box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1);
}

.btn-primary:hover {
    background-color: var(--color-btn-primary-hover-bg);
}

/* Alerts */
.alert {
    padding: 12px 16px;
    margin-bottom: 16px;
    border-radius: 6px;
    border: 1px solid transparent;
}

.alert-danger {
    background-color: var(--color-alert-error-bg);
    border-color: var(--color-alert-error-border);
    color: var(--color-alert-error-fg);
}
.navbar {
        background-color: var(--color-header-bg);
        border-bottom: 1px solid var(--color-border-muted);
        z-index: 1030; /* Ensures it stays above content */
    }
	</style>
</head>

<body>
	<nav class="navbar p-2 sticky-top" data-bs-theme="">
		<div>
			<a class="navbar-brand" href="index.php">
				<img src="https://i.ibb.co/q0Y1L5q/horizontal-logo.png" height="50" alt="">
			</a>
		</div>
		<!-- Center Navigation --> <?php
        // Get the current file name
        $current_page = basename($_SERVER['PHP_SELF']);
    ?> <ul class="nav mb-2 justify-content-center mx-4 mt-2">
			<li>
				<a href="index.php" class="nav-link px-2 <?= $current_page == 'index.php' ? 'link-secondary' : '' ?>">
					<i class="bi bi-house-fill me-2"></i>Home </a>
			</li>
			<li>
				<a href="php/marketplace.php" class="nav-link px-2 <?= $current_page == 'php/marketplace.php' ? 'link-secondary' : '' ?>">
					<i class="bi bi-shop me-2"></i>Marketplace </a>
			</li>
			<li>
				<a href="php/about.php" class="nav-link px-2 <?= $current_page == 'php/about.php' ? 'link-secondary' : '' ?>">
					<i class="bi bi-question-circle-fill me-2"></i>About </a>
			</li>
		</ul>
		<!-- Right Side: Login and Sign-up buttons or Profile Circle -->
		<div class="d-flex align-items-center ms-auto"> <?php if (isset($_SESSION['user_id'])): ?>
			<!-- Profile Dropdown -->
			<div class="btn-group dropstart">
				<button class="btn btn-secondary rounded-circle p-2 dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="bi bi-person"></i>
				</button>
				<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown" style="right: 0; top: 60px;">
					<li><a class="dropdown-item" href="php/profile.php">Profile</a></li>
					<li><a class="dropdown-item" href="php/settings.php">Settings</a></li>
					<li>
						<hr class="dropdown-divider">
					</li>
					<li><a class="dropdown-item" href="php/sign_out/sign_out.php">Sign Out</a></li>
				</ul>
			</div> <?php else: ?> <button type="button" class="btn btn-primary me-2" onclick="window.location.href='php/sign_in/sign_in_html.php'">Sign In</button>
			<button type="button" class="btn btn-secondary" onclick="window.location.href='php/sign_up/sign_up_html.php'">Sign Up</button> <?php endif; ?>
		</div>
	</nav>
	<!-- Bootstrap JS -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
	<script>
	// Ensure dropdown functionality works correctly
	document.addEventListener('DOMContentLoaded', function() {
		const dropdownTrigger = document.getElementById('profileDropdown');
		dropdownTrigger.addEventListener('click', function(event) {
			event.stopPropagation();
			const dropdownMenu = document.querySelector('.dropdown-menu');
			dropdownMenu.classList.toggle('show');
		});
		document.body.addEventListener('click', function() {
			const dropdownMenu = document.querySelector('.dropdown-menu');
			dropdownMenu.classList.remove('show');
		});
	});
	</script>
</body>
</html>