<?php

/**
 * AVASTRA User App — Top Bar
 * $pageTitle and $currentUser come from header.php (included before this file).
 */
$nameParts   = preg_split('/\s+/', trim($currentUser['full_name']));
$topInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
if (count($nameParts) === 1) {
    $topInitials = strtoupper(substr($nameParts[0], 0, 2));
}
$unreadNotifCount = $unreadNotifCount ?? 0;
?>
<header id="user-topbar">
    <div class="topbar-left">
        <button id="mobile-sidebar-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <span class="page-title"><?= htmlspecialchars($pageTitle); ?></span>
    </div>

    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search spaces...">
        </div>

        <a href="<?= APP_URL; ?>/user/notifications.php" class="topbar-icon-btn" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($unreadNotifCount > 0): ?><span class="dot"></span><?php endif; ?>
        </a>

        <a href="<?= APP_URL; ?>/user/profile.php" class="topbar-avatar" title="<?= htmlspecialchars($currentUser['full_name']); ?>">
            <?= htmlspecialchars($topInitials); ?>
        </a>
    </div>
</header>
<script>
    function toggleSidebar() {
        document.getElementById('user-sidebar').classList.toggle('open');
        document.getElementById('sidebar-backdrop').classList.toggle('open');
    }
</script>