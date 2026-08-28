<?php
/**
 * SpaceShare Admin — Sidebar Navigation Component
 */
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside id="admin-sidebar">
    <!-- Brand Logo -->
    <div class="sidebar-brand">
        <a href="<?= APP_URL; ?>/admin/dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= APP_URL; ?>/assets/images/logo/transparent-logo.svg" alt="AVASTRA Logo" height="36">
        </a>
    </div>

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <li class="menu-header">Overview</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/dashboard.php" class="nav-link <?= ($currentScript === 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">Marketplace Management</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/verify-spaces.php" class="nav-link <?= ($currentScript === 'verify-spaces.php') ? 'active' : ''; ?>">
                <i class="bi bi-patch-check-fill"></i>
                <span>Verify Spaces</span>
                <?php
                    $adminModel = new Admin();
                    $pendingCount = $adminModel->getDashboardStats()['pending_spaces'] ?? 0;
                    if ($pendingCount > 0):
                ?>
                    <span class="badge bg-danger rounded-pill ms-auto"><?= $pendingCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/spaces.php" class="nav-link <?= ($currentScript === 'spaces.php') ? 'active' : ''; ?>">
                <i class="bi bi-building"></i>
                <span>All Spaces</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/users.php" class="nav-link <?= ($currentScript === 'users.php') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>
                <span>User Management</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/bookings.php" class="nav-link <?= ($currentScript === 'bookings.php') ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Bookings</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/payments.php" class="nav-link <?= ($currentScript === 'payments.php') ? 'active' : ''; ?>">
                <i class="bi bi-credit-card-2-front-fill"></i>
                <span>Transactions</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/complaints.php" class="nav-link <?= ($currentScript === 'complaints.php') ? 'active' : ''; ?>">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Complaints</span>
            </a>
        </li>

        <li class="menu-header">Configuration & Reports</li>
        <li>
            <a href="<?= APP_URL; ?>/admin/categories.php" class="nav-link <?= ($currentScript === 'categories.php') ? 'active' : ''; ?>">
                <i class="bi bi-tags-fill"></i>
                <span>Categories & Amenities</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/settings.php" class="nav-link <?= ($currentScript === 'settings.php') ? 'active' : ''; ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Platform Settings</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/reports.php" class="nav-link <?= ($currentScript === 'reports.php') ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Reports & Analytics</span>
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/admin/audit-logs.php" class="nav-link <?= ($currentScript === 'audit-logs.php') ? 'active' : ''; ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Audit Logs</span>
            </a>
        </li>
    </ul>

    <!-- Bottom User Section -->
    <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
        <a href="<?= APP_URL; ?>/public/logout.php" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
