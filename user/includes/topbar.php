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

// Fetch real unread notification count for currentUser
try {
    $notifStmt = Database::getInstance()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $notifStmt->execute([':uid' => (int) $currentUser['id']]);
    $unreadNotifCount = (int) $notifStmt->fetchColumn();
} catch (Exception $e) {
    $unreadNotifCount = 0;
}
?>
<header id="user-topbar">
    <div class="topbar-left">
        <button id="mobile-sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>
        <span class="page-title"><?= htmlspecialchars($pageTitle); ?></span>
    </div>

    <div class="topbar-right">
        <?php if (empty($hideTopbarSearch)): ?>
            <form action="<?= APP_URL; ?>/user/find-spaces.php" method="GET" class="topbar-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" placeholder="Search spaces..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
            </form>
        <?php endif; ?>

        <a href="<?= APP_URL; ?>/user/notifications.php" class="topbar-icon-btn position-relative" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($unreadNotifCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                    <span class="visually-hidden">New notifications</span>
                </span>
            <?php endif; ?>
        </a>

        <a href="<?= APP_URL; ?>/user/profile.php" class="topbar-avatar" title="<?= htmlspecialchars($currentUser['full_name']); ?>">
            <?= htmlspecialchars($topInitials); ?>
        </a>
    </div>
</header>
<script>
    function toggleSidebar() {
        var sb = document.getElementById('user-sidebar');
        var bd = document.getElementById('sidebar-backdrop');
        if (sb) sb.classList.toggle('open');
        if (bd) bd.classList.toggle('open');
    }
</script>
