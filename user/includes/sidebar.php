<?php

/**
 * AVASTRA User App — Left Sidebar Navigation
 * Active state is based on the current file name (same pattern as admin/includes/sidebar.php).
 */
$currentScript = basename($_SERVER['PHP_SELF']);

// Build "PS"-style initials from the user's full name for the avatar circle.
$nameParts = preg_split('/\s+/', trim($currentUser['full_name']));
$initials  = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
if (count($nameParts) === 1) {
    $initials = strtoupper(substr($nameParts[0], 0, 2));
}

// How many booking requests on MY spaces are waiting for MY response (owner side).
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
        <a href="<?= APP_URL; ?>/user/dashboard.php" style="text-decoration:none;">
            <span class="brand-name">AVASTRA</span>
            <span class="brand-tag">SPACE FOR WHAT'S NEXT</span>
        </a>
    </div>

    <ul class="sidebar-nav">
        <li><a href="<?= APP_URL; ?>/user/dashboard.php" class="<?= $currentScript === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i> Overview
            </a></li>
        <li><a href="<?= APP_URL; ?>/user/find-spaces.php" class="<?= $currentScript === 'find-spaces.php' ? 'active' : ''; ?>">
                <i class="bi bi-search"></i> Find Spaces
            </a></li>
        <li><a href="<?= APP_URL; ?>/user/my-bookings.php" class="<?= $currentScript === 'my-bookings.php' ? 'active' : ''; ?>">
                <i class="bi bi-calendar-check"></i> My Bookings
            </a></li>
        <li><a href="<?= APP_URL; ?>/user/my-requests.php" class="<?= $currentScript === 'my-requests.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text"></i> My Requests
            </a></li>
        <li><a href="<?= APP_URL; ?>/user/owner-requests.php" class="<?= $currentScript === 'owner-requests.php' ? 'active' : ''; ?>">
                <i class="bi bi-inbox"></i> Booking Requests
                <?php if ($ownerPendingCount > 0): ?><span class="sidebar-badge"><?= $ownerPendingCount; ?></span><?php endif; ?>
            </a></li>
        <li><a href="<?= APP_URL; ?>/user/my-spaces.php" class="<?= $currentScript === 'my-spaces.php' ? 'active' : ''; ?>">
                <i class="bi bi-building"></i> My Spaces
            </a></li>
        <li><a href="<?= APP_URL; ?>/user/messages.php" class="<?= $currentScript === 'messages.php' ? 'active' : ''; ?>">
                <i class="bi bi-chat-dots"></i> Messages
            </a></li>
        <li><a href="<?= APP_URL; ?>/user/profile.php" class="<?= $currentScript === 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i> Profile
            </a></li>
    </ul>

    <div class="sidebar-divider"></div>
    <div class="sidebar-cta">
        <a href="<?= APP_URL; ?>/user/list-space.php"><i class="bi bi-plus-lg"></i> List a Space</a>
    </div>

    <ul class="sidebar-footer-links">
        <li><a href="<?= APP_URL; ?>/user/help.php" class="<?= $currentScript === 'help.php' ? 'active' : ''; ?>"><i class="bi bi-question-circle"></i> Help &amp; Support</a></li>
        <li><a href="<?= APP_URL; ?>/user/settings.php" class="<?= $currentScript === 'settings.php' ? 'active' : ''; ?>"><i class="bi bi-gear"></i> Account Settings</a></li>
    </ul>

    <div class="sidebar-user">
        <div class="avatar-circle"><?= htmlspecialchars($initials); ?></div>
        <div style="flex:1;min-width:0;">
            <div class="u-name"><?= htmlspecialchars($currentUser['full_name']); ?></div>
            <div class="u-email"><?= htmlspecialchars($currentUser['email']); ?></div>
        </div>
        <i class="bi bi-chevron-down" style="color:rgba(247,247,247,0.4);font-size:12px;"></i>
    </div>
    <div class="sidebar-signout">
        <a href="<?= APP_URL; ?>/public/logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a>
    </div>
</aside>