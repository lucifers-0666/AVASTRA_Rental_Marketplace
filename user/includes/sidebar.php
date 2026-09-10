<?php

/**
 * AVASTRA User App — Left Sidebar Navigation
 * Standardized to official AVASTRA Design System and structured sections.
 */
$currentScript = basename($_SERVER['PHP_SELF']);

// Build initials for user avatar
$nameParts = preg_split('/\s+/', trim($currentUser['full_name']));
$initials  = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
if (count($nameParts) === 1) {
    $initials = strtoupper(substr($nameParts[0], 0, 2));
}

// Pending booking requests count on owned spaces
$ownerPendingStmt = Database::getInstance()->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN spaces s ON s.id = b.space_id
    WHERE s.owner_id = :uid AND b.status = 'pending'
");
$ownerPendingStmt->execute([':uid' => (int) $currentUser['id']]);
$ownerPendingCount = (int) $ownerPendingStmt->fetchColumn();
?>
<div id="sidebar-backdrop" onclick="toggleSidebar()"></div>
<aside id="user-sidebar">
    <div class="sidebar-brand">
        <a href="<?= APP_URL; ?>/user/dashboard.php" class="d-inline-block" style="text-decoration:none;">
            <img src="<?= APP_URL; ?>/assets/images/logo/transparent-logo.svg" alt="AVASTRA" style="height:36px; width:auto; display:block;">
        </a>
    </div>

    <ul class="sidebar-nav">
        <!-- EXPLORE -->
        <li class="sidebar-section-title">Explore</li>
        <li>
            <a href="<?= APP_URL; ?>/user/dashboard.php" class="<?= $currentScript === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i> Overview
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/user/find-spaces.php" class="<?= $currentScript === 'find-spaces.php' ? 'active' : ''; ?>">
                <i class="bi bi-search"></i> Find Spaces
            </a>
        </li>

        <!-- BOOKINGS -->
        <li class="sidebar-section-title">Bookings</li>
        <li>
            <a href="<?= APP_URL; ?>/user/my-bookings.php" class="<?= $currentScript === 'my-bookings.php' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check"></i> My Bookings
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/user/my-requests.php" class="<?= $currentScript === 'my-requests.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text"></i> My Requests
            </a>
        </li>

        <!-- OWNER -->
        <li class="sidebar-section-title">Owner</li>
        <li>
            <a href="<?= APP_URL; ?>/user/my-spaces.php" class="<?= $currentScript === 'my-spaces.php' ? 'active' : ''; ?>">
                <i class="bi bi-building"></i> My Spaces
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/user/owner-requests.php" class="<?= $currentScript === 'owner-requests.php' ? 'active' : ''; ?>">
                <i class="bi bi-inbox"></i> Booking Requests
                <?php if ($ownerPendingCount > 0): ?>
                    <span class="sidebar-badge"><?= $ownerPendingCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="mt-1">
            <a href="<?= APP_URL; ?>/user/list-space.php" class="<?= $currentScript === 'list-space.php' ? 'active' : ''; ?>" style="background: rgba(255,255,255,0.08); color: #ffffff;">
                <i class="bi bi-plus-circle-fill" style="color:var(--avastra-accent);"></i> + List a Space
            </a>
        </li>

        <!-- COMMUNICATION -->
        <li class="sidebar-section-title">Communication</li>
        <li>
            <a href="<?= APP_URL; ?>/user/messages.php" class="<?= $currentScript === 'messages.php' ? 'active' : ''; ?>">
                <i class="bi bi-chat-dots"></i> Messages
            </a>
        </li>

        <!-- ACCOUNT -->
        <li class="sidebar-section-title">Account</li>
        <li>
            <a href="<?= APP_URL; ?>/user/profile.php" class="<?= $currentScript === 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i> Profile
            </a>
        </li>
        <li>
            <a href="<?= APP_URL; ?>/user/settings.php" class="<?= $currentScript === 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> Settings
            </a>
        </li>
    </ul>

    <div class="sidebar-footer-links">
        <a href="<?= APP_URL; ?>/user/help.php" class="<?= $currentScript === 'help.php' ? 'active' : ''; ?>">
            <i class="bi bi-question-circle"></i> Help &amp; Support
        </a>
    </div>

    <div class="sidebar-user">
        <div class="avatar-circle"><?= htmlspecialchars($initials); ?></div>
        <div style="flex:1;min-width:0;">
            <div class="u-name"><?= htmlspecialchars($currentUser['full_name']); ?></div>
            <div class="u-email"><?= htmlspecialchars($currentUser['email']); ?></div>
        </div>
        <a href="<?= APP_URL; ?>/public/logout.php" title="Sign out" style="color:rgba(255,255,255,0.6); font-size:16px;">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</aside>