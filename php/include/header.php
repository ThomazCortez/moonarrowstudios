<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Bootstrap Icons -->
	<link rel="stylesheet" href="css/css2.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
	<nav class="navbar p-2 sticky-top" data-bs-theme="">
		<div>
			<a class="navbar-brand" href="index.php">
				<img src="media/horizontal_logo.png" height="50" alt="">
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