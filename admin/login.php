<?php
/**
 * AVASTRA — Admin Login Portal
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::initSession();

$message = '';
$error = '';

if (Auth::isAdmin()) {
    header("Location: " . APP_URL . "/admin/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = Auth::login($email, $password);
    if ($result['success']) {
        if (Auth::isAdmin()) {
            header("Location: " . APP_URL . "/admin/dashboard.php");
            exit;
        } else {
            $error = "Access denied. Your account does not have Administrator privileges.";
            Auth::logout();
        }
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — AVASTRA</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL; ?>/assets/images/logo/only%20logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #0B2A18;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
        .login-card {
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            padding: 2.25rem;
        }
        .btn-avastra {
            background: #145C4A;
            color: #FFFFFF;
        }
        .btn-avastra:hover {
            background: #0B2A18;
            color: #FFFFFF;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <img src="<?= APP_URL; ?>/assets/images/logo/colored-logo.svg" alt="AVASTRA Logo" height="42" class="mb-2">
            <h5 class="fw-bold mb-1" style="color:#0B2A18;">AVASTRA Admin</h5>
            <p class="text-muted small mb-0">SPACE FOR WHAT'S NEXT.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold small">Admin Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" value="admin@spaceshare.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" value="admin123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-avastra w-100 py-2 fw-bold rounded-2">
                <i class="bi bi-shield-lock-fill me-1"></i> Log In to Admin Panel
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-muted small" style="font-size:11px;">
            Default Credentials: <code>admin@spaceshare.com</code> / <code>admin123</code>
        </div>
    </div>
</body>
</html>
