<?php
/**
 * SpaceShare Admin — Top Navbar Component
 */
$currentUser = Auth::getUser();
?>
<header class="admin-navbar">
    <div class="d-flex align-items-center gap-3">
        <button id="sidebar-toggle" class="btn btn-sm btn-light d-md-none">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="navbar-search d-none d-sm-block">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search spaces, users, bookings...">
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <!-- System Quick View -->
        <a href="<?= APP_URL; ?>/public/index.php" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="bi bi-globe me-1"></i> Public Site
        </a>

        <!-- Notifications Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light rounded-circle position-relative p-2" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="width: 300px;">
                <li class="dropdown-header fw-bold">System Alerts</li>
                <li><hr class="dropdown-divider"></li>
                <li class="px-3 py-2 text-muted small">No unread admin notifications.</li>
            </ul>
        </div>

        <!-- Profile Dropdown -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark gap-2" data-bs-toggle="dropdown">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:38px; height:38px;">
                    A
                </div>
                <div class="d-none d-md-block text-start" style="line-height:1.2;">
                    <div class="fw-bold small"><?= htmlspecialchars($currentUser['full_name'] ?? 'Admin'); ?></div>
                    <small class="text-muted" style="font-size:11px;">Administrator</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item" href="<?= APP_URL; ?>/admin/settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= APP_URL; ?>/public/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</header>
