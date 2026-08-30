<?php
/**
 * AVASTRA Admin — White Sidebar Navigation Component (Figma Design System)
 */
$currentScript = basename($_SERVER['PHP_SELF']);
$adminModel = new Admin();
$pendingVerificationCount = $adminModel->getPendingVerificationCount();
$currentUser = Auth::getUser();
?>
<aside id="admin-sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <a href="<?= APP_URL; ?>/admin/dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= APP_URL; ?>/assets/images/PHP%20LOGO/transparent-logo.svg" alt="AVASTRA Logo" style="height:44px; width:auto; max-width:145px; object-fit:contain;">
            <span class="brand-tag">ADMIN</span>
        </a>
    </div>

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <li class="menu-header">OVERVIEW</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/dashboard.php" class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">MANAGEMENT</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/users.php" class="nav-link <?= ($currentScript === 'users.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>
                <span>Users</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/owners.php" class="nav-link <?= ($currentScript === 'owners.php') ? 'active' : ''; ?>">
                <i class="bi bi-person-badge-fill"></i>
                <span>Owners</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/spaces.php" class="nav-link <?= ($currentScript === 'spaces.php') ? 'active' : ''; ?>">
                <i class="bi bi-building-fill"></i>
                <span>Spaces</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/bookings.php" class="nav-link <?= ($currentScript === 'bookings.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Bookings</span>
            </a>
        </li>

        <li class="menu-header">OPERATIONS</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/verify-spaces.php" class="nav-link <?= ($currentScript === 'verify-spaces.php') ? 'active' : ''; ?>">
                <i class="bi bi-shield-check-fill"></i>
                <span>Verification</span>
                <?php if ($pendingVerificationCount > 0): ?>
                    <span class="badge bg-warning text-dark rounded-pill ms-auto" style="font-size:10px; font-weight:800;"><?= $pendingVerificationCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/complaints.php" class="nav-link <?= ($currentScript === 'complaints.php') ? 'active' : ''; ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Reports & Issues</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/reviews.php" class="nav-link <?= ($currentScript === 'reviews.php') ? 'active' : ''; ?>">
                <i class="bi bi-star-fill"></i>
                <span>Reviews</span>
            </a>
        </li>

        <li class="menu-header">FINANCE</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/payments.php" class="nav-link <?= ($currentScript === 'payments.php') ? 'active' : ''; ?>">
                <i class="bi bi-credit-card-2-front-fill"></i>
                <span>Payments / Transactions</span>
            </a>
        </li>

        <li class="menu-header">INSIGHTS</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/analytics.php" class="nav-link <?= ($currentScript === 'analytics.php') ? 'active' : ''; ?>">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Analytics</span>
            </a>
        </li>

        <li class="menu-header">SYSTEM</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/notifications.php" class="nav-link <?= ($currentScript === 'notifications.php') ? 'active' : ''; ?>">
                <i class="bi bi-bell-fill"></i>
                <span>Notifications</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/settings.php" class="nav-link <?= ($currentScript === 'settings.php') ? 'active' : ''; ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/audit-logs.php" class="nav-link <?= ($currentScript === 'audit-logs.php') ? 'active' : ''; ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Audit Logs</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar Bottom User Card -->
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="d-flex align-items-center gap-2">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['full_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? 'Administrator'); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="<?= APP_URL; ?>/public/logout.php" class="btn btn-sm text-secondary p-1" title="Logout">
                <i class="bi bi-box-arrow-right fs-5 text-danger"></i>
            </a>
        </div>
    </div>
</aside>
