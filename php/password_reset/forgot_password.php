<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Forgot Password</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://bootswatch.com/5/darkly/bootstrap.min.css">
	<link rel="stylesheet" href="css.css">
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

	/* Initially hide the spinner button */
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
			<!-- Forgot Password Form Section -->
			<div class="col-md-6 d-flex flex-column p-3">
				<!-- Logo Section -->
				<div class="mb-4">
					<img src="../../media/horizontal_logo.png" alt="Logo" class="img-fluid logo" style="max-width: 200px;">
				</div>
				<!-- Alert Section -->
				<div class="alert-container"> <?php if (isset($_GET['alert'])): ?> <div class="alert alert-<?= htmlspecialchars($_GET['type']) ?> alert-dismissible fade show" role="alert"> <?= htmlspecialchars($_GET['alert']) ?> <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div> <?php endif; ?> </div>
				<!-- Form Section -->
				<div class="form-container d-flex flex-column justify-content-center align-items-start">
					<div class="w-100 px-3 px-md-5">
						<h1 class="mb-4">Forgot Password</h1>
						<p>Enter your email and we'll send you a link to reset your password.</p>
						<form id="forgotPasswordForm" action="send_reset.php" method="post">
							<div class="mb-3">
								<label for="email" class="form-label">Email address</label>
								<input type="email" class="form-control" id="email" placeholder="yourname@example.com" name="email" required>
							</div>
							<button id="submitButton" type="submit" class="btn btn-primary w-100">Send Reset Link</button>
							<!-- Spinner Button (initially hidden) -->
							<button id="spinnerButton" class="btn btn-primary w-100" type="button" disabled>
								<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
								<span role="status">Sending Reset Link...</span>
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
	// Automatically remove alerts after 5 seconds
	setTimeout(() => {
		const alerts = document.querySelectorAll('.alert');
		alerts.forEach(alert => {
			alert.classList.remove('show'); // Bootstrap's fade-out transition
			alert.addEventListener('transitionend', () => alert.remove());
		});
	}, 5000); // 5000ms = 5 seconds
	// Handle form submission
	document.getElementById('forgotPasswordForm').addEventListener('submit', function(event) {
		// Hide the submit button and show the spinner
		document.getElementById('submitButton').style.display = 'none';
		document.getElementById('spinnerButton').style.display = 'block';
	});
	</script>
</body>

</html>