<?php

/**
 * AVASTRA — Public Registration (Seeker / Owner Account Creation)
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::initSession();

// Already logged in? Skip to dashboard.
if (Auth::isLoggedIn()) {
    if (Auth::isAdmin()) {
        header("Location: " . APP_URL . "/admin/dashboard.php");
    } else {
        header("Location: " . APP_URL . "/user/dashboard.php");
    }
    exit;
}

$error = '';
$fullName = '';
$email    = '';
$phone    = '';
$city     = '';
$state    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $state    = trim($_POST['state'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $agree    = isset($_POST['agree_terms']);

    if (!$agree) {
        $error = 'You must agree to the Terms of Service to create an account.';
    } else {
        $result = Auth::register([
            'full_name'        => $fullName,
            'email'            => $email,
            'phone'            => $phone,
            'city'             => $city,
            'state'            => $state,
            'password'         => $password,
            'confirm_password' => $confirm,
        ]);

        if ($result['success']) {
            header("Location: " . APP_URL . "/user/dashboard.php?welcome=1");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account — AVASTRA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:ital,wght@0,400;0,500;1,400&family=DM+Serif+Display:ital@0;1&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL; ?>/assets/images/logo/only%20logo.svg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_URL; ?>/assets/css/style.css">
</head>

<body>

    <nav class="navbar-avastra">
        <div class="wrap nav-inner">
            <a href="<?= APP_URL; ?>/user/dashboard.php" class="brand-logo d-inline-block">
                <img src="<?= APP_URL; ?>/assets/images/logo/transparent-logo.svg" alt="AVASTRA" style="height:38px; width:auto; display:block;">
            </a>
            <div class="nav-center d-none d-md-flex gap-4">
                <a href="<?= APP_URL; ?>/user/find-spaces.php">Browse Spaces</a>
                <a href="<?= APP_URL; ?>/user/help.php">How It Works</a>
                <a href="<?= APP_URL; ?>/user/list-space.php">For Owners</a>
            </div>
            <div class="nav-right">
                <a href="<?= APP_URL; ?>/public/login.php" class="nav-login" style="color:var(--avastra-primary, #073B36); font-weight:600;">Log in</a>
            </div>
        </div>
    </nav>

    <div class="auth-shell py-5">
        <div class="wrap" style="max-width: 520px; margin: 0 auto;">
            <div class="auth-logo text-center mb-3">
                <img src="<?= APP_URL; ?>/assets/images/logo/transparent-logo.svg" alt="AVASTRA" style="height:50px; width:auto; display:inline-block;">
            </div>

            <div class="auth-card p-4 p-md-5 bg-white rounded-3 shadow-sm border border-light">
                <h1 style="font-family:'DM Serif Display', serif; font-size:26px; text-align:center; margin-bottom:6px; color:#073B36;">Create Your Account</h1>
                <p style="text-align:center; color:#5C6B62; font-size:14px; margin-bottom:24px;">Join AVASTRA to rent verified spaces or list your properties.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13.5px; border-radius:8px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="regName" class="form-label text-dark fw-medium small mb-1">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" id="regName"
                            value="<?= htmlspecialchars($fullName); ?>"
                            placeholder="e.g. Priya Sharma" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="regEmail" class="form-label text-dark fw-medium small mb-1">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" id="regEmail"
                            value="<?= htmlspecialchars($email); ?>"
                            placeholder="name@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="regPhone" class="form-label text-dark fw-medium small mb-1">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" id="regPhone"
                            value="<?= htmlspecialchars($phone); ?>"
                            placeholder="+91 98765 43210">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="regCity" class="form-label text-dark fw-medium small mb-1">City</label>
                            <input type="text" name="city" class="form-control" id="regCity"
                                value="<?= htmlspecialchars($city); ?>"
                                placeholder="e.g. Mumbai">
                        </div>
                        <div class="col-md-6">
                            <label for="regState" class="form-label text-dark fw-medium small mb-1">State</label>
                            <input type="text" name="state" class="form-control" id="regState"
                                value="<?= htmlspecialchars($state); ?>"
                                placeholder="e.g. Maharashtra">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="regPassword" class="form-label text-dark fw-medium small mb-1">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" id="regPassword"
                                placeholder="Min 6 characters" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label for="regConfirm" class="form-label text-dark fw-medium small mb-1">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" id="regConfirm"
                                placeholder="Repeat password" required minlength="6">
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="agree_terms" id="agreeTerms" required>
                        <label class="form-check-label small text-muted" for="agreeTerms">
                            I agree to AVASTRA's <a href="<?= APP_URL; ?>/user/help.php" target="_blank" style="color:#073B36;">Terms of Service</a> and Privacy Policy.
                        </label>
                    </div>

                    <button type="submit" class="btn w-100 py-2 fw-semibold text-white" style="background:#073B36; border-radius:8px;">Create Account</button>
                </form>

                <div class="text-center mt-3 pt-3 border-top">
                    <p class="small text-muted mb-0">Already have an account? <a href="<?= APP_URL; ?>/public/login.php" style="color:#073B36; font-weight:600;">Log in</a></p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
