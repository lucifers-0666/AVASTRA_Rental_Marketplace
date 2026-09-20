<?php
/**
 * AVASTRA — Dedicated Admin Portal Login
 * Completely separated session scope from standard user/seeker accounts.
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::initSession();

$error = '';

// If already authenticated as admin, jump straight to the admin dashboard
if (Auth::isAdminLoggedIn()) {
    header("Location: " . APP_URL . "/admin/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both admin email and password.';
    } else {
        $result = Auth::loginAdmin($email, $password);
        if ($result['success']) {
            header("Location: " . APP_URL . "/admin/dashboard.php");
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
    <title>Admin Portal — AVASTRA</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL; ?>/assets/images/logo/only%20logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #073B36 0%, #0E5244 50%, #042522 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            padding: 1.5rem;
        }
        .admin-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            position: relative;
        }
        .badge-admin {
            background: #E8F4EF;
            color: #073B36;
            font-size: 11.5px;
            letter-spacing: 0.5px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            border: 1px solid rgba(7, 59, 54, 0.15);
        }
        .btn-admin {
            background: #073B36;
            color: #FFFFFF;
            font-weight: 600;
            border: none;
            padding: 10px;
            transition: all 0.2s ease;
        }
        .btn-admin:hover {
            background: #0E5244;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(7, 59, 54, 0.3);
        }
        .form-control:focus {
            border-color: #073B36;
            box-shadow: 0 0 0 0.2rem rgba(7, 59, 54, 0.15);
        }
    </style>
</head>
<body>
    <div class="admin-card">
        <div class="text-center mb-4">
            <img src="<?= APP_URL; ?>/assets/images/logo/transparent-logo.svg" alt="AVASTRA" height="48" class="mb-2">
            <div><span class="badge-admin"><i class="bi bi-shield-lock-fill me-1"></i> ADMIN CONTROL CENTER</span></div>
            <p class="text-muted small mt-2 mb-0">Authorized administration access only</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3" style="border-radius:8px;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label fw-semibold small text-dark">Admin Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" value="admin@spaceshare.com" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small text-dark">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" value="admin123" required>
                </div>
            </div>

            <button type="submit" class="btn btn-admin w-100 rounded-3">
                <i class="bi bi-shield-check me-1"></i> Access Admin Console
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center" style="font-size:12px;">
            <div class="text-muted mb-2">
                Default Credentials: <code>admin@spaceshare.com</code> / <code>admin123</code>
            </div>
            <a href="<?= APP_URL; ?>/public/login.php" class="text-decoration-none" style="color:#073B36; font-weight:600;">
                <i class="bi bi-arrow-left me-1"></i> Switch to User Portal
            </a>
        </div>
    </div>
</body>
</html>
