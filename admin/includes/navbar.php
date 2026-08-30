<?php
/**
 * AVASTRA Admin — Top Navbar Component (Approved Figma Alignment)
 */
$currentUser = Auth::getUser();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<header class="admin-navbar">
    <div class="d-flex align-items-center gap-3">
        <button id="sidebar-toggle" class="btn btn-sm btn-light d-lg-none">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="page-title-header">
            <h4><?= htmlspecialchars($pageTitle); ?></h4>
            <div class="page-breadcrumb">AVASTRA Admin Portal &bull; <?= htmlspecialchars($pageTitle); ?></div>
        </div>
    </div>

    <!-- Global Search -->
    <div class="navbar-search d-none d-md-block">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Global search (spaces, users, bookings)...">
    </div>

    <div class="d-flex align-items-center gap-3">
        <!-- Public Site Button -->
        <a href="<?= APP_URL; ?>/public/index.php" target="_blank" class="btn btn-sm btn-outline-avastra rounded-pill px-3">
            <i class="bi bi-globe me-1"></i> Public Site
        </a>

        <!-- Notifications Icon -->
        <a href="<?= APP_URL; ?>/admin/notifications.php" class="btn btn-light rounded-circle position-relative p-2" title="Notifications">
            <i class="bi bi-bell fs-5 text-dark"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        </a>

        <!-- Profile Avatar & Dropdown -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark gap-2" data-bs-toggle="dropdown">
                <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px; height:36px; background:#145C4A !important;">
                    <?= strtoupper(substr($currentUser['full_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="d-none d-md-block text-start" style="line-height:1.2;">
                    <div class="fw-bold small"><?= htmlspecialchars($currentUser['full_name'] ?? 'Admin'); ?></div>
                    <small class="text-muted" style="font-size:11px;">Administrator</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                <li><a class="dropdown-item" href="<?= APP_URL; ?>/admin/settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL; ?>/admin/audit-logs.php"><i class="bi bi-shield-lock me-2"></i> Audit Logs</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger fw-semibold" href="<?= APP_URL; ?>/public/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</header>
