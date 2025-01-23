<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sign Up</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://bootswatch.com/5/darkly/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
	<link rel="stylesheet" href="css.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
	<style>
	html,
	body {
		height: 100%;
		margin: 0;
	}

	.container-fluid {
		height: 100%;
	}

	.alert-container {
		position: absolute;
		top: 20px;
		left: 50%;
		transform: translateX(-50%);
		width: 90%;
		max-width: 500px;
		z-index: 10;
	}

	.form-container {
		padding-top: 80px;
		flex-grow: 1;
	}

	#spinnerButton {
		display: none;
	}

	.art-section img {
		object-fit: cover;
	}

	.centered-footer {
		text-align: center;
	}

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

<body class="">
	<div class="container-fluid vh-100">
		<div class="row h-100">
			<!-- Sign Up Form Section -->
			<div class="col-md-6 d-flex flex-column p-3">
				<!-- Logo Section -->
				<div class="mb-4">
					<img src="horizontal_logo.png" alt="Logo" class="img-fluid logo" style="max-width: 200px;">
				</div>
				<!-- Alert Section -->
				<div class="alert-container"> <?php if (isset($_GET['alert'])): ?> <div class="alert alert-<?= htmlspecialchars($_GET['type']) ?> alert-dismissible fade show" role="alert"> <?= htmlspecialchars($_GET['alert']) ?> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div> <?php endif; ?> </div>
				<!-- Form Section -->
				<div class="form-container d-flex flex-column justify-content-center align-items-start">
					<div class="w-100 px-3 px-md-5">
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
							<button id="spinnerButton" class="btn btn-primary w-100" type="button" disabled>
								<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
								<span role="status">Signing Up...</span>
							</button>
						</form>
						<hr class="my-4">
						<div class="centered-footer">
							<p>Already have an account? <a href="sign_in_html.php" class="text-decoration-none">Sign in</a></p>
							<p>Click <a href="index.php" class="text-decoration-none">here</a> to go back to the main page.</p>
						</div>
					</div>
				</div>
			</div>
			<!-- Art/Image Section -->
			<div class="col-md-6 art-section p-0 d-none d-md-block">
				<img src="loginnn.png" alt="Background Image" class="w-100 h-100">
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