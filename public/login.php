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
            // Send admins to the admin panel, everyone else to their dashboard.
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <nav class="navbar-avastra">
        <div class="wrap nav-inner">
            <a href="<?= APP_URL; ?>/index.php" class="brand-logo">
                <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2 L29 9 V23 L16 30 L3 23 V9 Z" stroke="#145C4A" stroke-width="2" fill="none" />
                    <path d="M16 2 V16 M16 16 L3 9 M16 16 L29 9" stroke="#56B978" stroke-width="2" />
                </svg>
                <span class="brand-text">
                    <span class="brand-name">AVASTRA</span>
                    <span class="brand-tag">Space for what's next</span>
                </span>
            </a>
            <div class="nav-center">
                <a href="<?= APP_URL; ?>/user/browse-spaces.php">Browse Spaces</a>
                <a href="<?= APP_URL; ?>/user/how-it-works.php">How It Works</a>
                <a href="<?= APP_URL; ?>/user/for-owners.php">For Owners</a>
            </div>
            <div class="nav-right">
                <a href="<?= APP_URL; ?>/public/login.php" class="nav-login d-none d-md-inline" style="color:var(--teal);">Log in</a>
                <a href="<?= APP_URL; ?>/public/register.php" class="btn btn-ghost-avastra d-none d-lg-inline-flex">Sign up</a>
            </div>
        </div>
    </nav>

    <div class="auth-shell">
        <div class="wrap">
            <div class="auth-logo">
                <svg viewBox="0 0 32 32" width="26" height="26" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2 L29 9 V23 L16 30 L3 23 V9 Z" stroke="#145C4A" stroke-width="2" fill="none" />
                    <path d="M16 2 V16 M16 16 L3 9 M16 16 L29 9" stroke="#56B978" stroke-width="2" />
                </svg>
                <span class="brand-name">AVASTRA</span>
            </div>

            <div class="auth-card">
                <h1 style="font-size:26px;text-align:center;margin-bottom:6px;">Welcome back</h1>
                <p style="text-align:center;color:rgba(23,32,27,0.65);font-size:14.5px;margin-bottom:26px;">Log in to manage your spaces and bookings.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3" style="font-size:13.5px;border-radius:8px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php if ($redirectTo !== ''): ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTo); ?>">
                    <?php endif; ?>

                    <div class="field mb-3">
                        <label for="loginEmail">Email address</label>
                        <input type="email" name="email" class="form-control" id="loginEmail"
                            value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                            placeholder="you@example.com" autofocus>
                    </div>
                    <div class="field mb-2">
                        <label for="loginPassword">Password</label>
                        <input type="password" name="password" class="form-control" id="loginPassword"
                            placeholder="Your password">
                    </div>
                    <div class="text-end mb-3">
                        <a href="#" style="font-size:13.5px;" onclick="alert('Password reset is not built yet.'); return false;">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary-avastra w-100">Log in</button>
                </form>
            </div>

            <p class="auth-below">Don't have an account? <a href="<?= APP_URL; ?>/public/register.php" style="font-weight:600;">Register</a></p>
        </div>
    </div>

</body>

</html>