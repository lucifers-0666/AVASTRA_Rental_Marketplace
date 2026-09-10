<?php

/**
 * AVASTRA — Public Login (Seeker / Owner)
 * Uses the shared Auth class so this stays consistent with the admin panel.
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::initSession();

// Already logged in? Skip the form.
if (Auth::isLoggedIn()) {
    if (Auth::isAdmin()) {
        header("Location: " . APP_URL . "/admin/dashboard.php");
    } else {
        header("Location: " . APP_URL . "/user/dashboard.php");
    }
    exit;
}

$error = '';
$redirectTo = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        $result = Auth::login($email, $password);

        if ($result['success']) {
            if (Auth::isAdmin()) {
                header("Location: " . APP_URL . "/admin/dashboard.php");
            } elseif ($redirectTo !== '') {
                header("Location: " . $redirectTo);
            } else {
                header("Location: " . APP_URL . "/user/dashboard.php");
            }
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
    <title>Log in — AVASTRA</title>
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
                <a href="<?= APP_URL; ?>/public/login.php" class="nav-login d-none d-md-inline" style="color:var(--avastra-primary, #1B5E3A); font-weight:600;">Log in</a>
            </div>
        </div>
    </nav>

    <div class="auth-shell py-5">
        <div class="wrap" style="max-width: 440px; margin: 0 auto;">
            <div class="auth-logo text-center mb-4">
                <img src="<?= APP_URL; ?>/assets/images/logo/transparent-logo.svg" alt="AVASTRA" style="height:54px; width:auto; display:inline-block;">
            </div>

            <div class="auth-card p-4 bg-white rounded-3 shadow-sm border border-light">
                <h1 style="font-family:'DM Serif Display', serif; font-size:24px; text-align:center; margin-bottom:6px; color:#1B5E3A;">Welcome back</h1>
                <p style="text-align:center; color:#5C6B62; font-size:14px; margin-bottom:24px;">Log in to manage your spaces and bookings.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:13.5px; border-radius:8px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php if ($redirectTo !== ''): ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTo); ?>">
                    <?php endif; ?>

                    <div class="field mb-3">
                        <label for="loginEmail" class="form-label text-dark fw-medium small mb-1">Email address</label>
                        <input type="email" name="email" class="form-control" id="loginEmail"
                            value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                            placeholder="you@example.com" required autofocus>
                    </div>
                    <div class="field mb-2">
                        <label for="loginPassword" class="form-label text-dark fw-medium small mb-1">Password</label>
                        <input type="password" name="password" class="form-control" id="loginPassword"
                            placeholder="Your password" required>
                    </div>
                    <div class="text-end mb-3">
                        <a href="#" style="font-size:13px; color:#1B5E3A;" onclick="alert('Demo account passwords are: admin123'); return false;">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn w-100 py-2 fw-semibold text-white" style="background:#1B5E3A; border-radius:8px;">Log in</button>
                </form>
            </div>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">Testing Seeker / Owner account?</p>
                <code class="small text-dark">jay@example.com / admin123</code>
            </div>
        </div>
    </div>

</body>

</html>