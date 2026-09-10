<?php

/**
 * AVASTRA — User Notifications Center
 */
$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'read_all') {
    $markStmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
    $markStmt->execute([':uid' => $userId]);
    header("Location: " . APP_URL . "/user/notifications.php");
    exit;
}

// Fetch user notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50");
$stmt->execute([':uid' => $userId]);
$notifications = $stmt->fetchAll();
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content" class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1" style="font-family:'DM Serif Display', serif; color:var(--avastra-primary);">Notifications</h1>
                <p class="text-muted small mb-0">Stay updated on your booking requests, approvals, and marketplace activity.</p>
            </div>
            <?php if (!empty($notifications)): ?>
                <a href="<?= APP_URL; ?>/user/notifications.php?action=read_all" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-check-all me-1"></i> Mark all as read
                </a>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="background:#ffffff;">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5 px-3">
                    <div class="mb-3" style="width: 64px; height: 64px; background: var(--avastra-light); color: var(--avastra-primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 28px;">
                        <i class="bi bi-bell-slash"></i>
                    </div>
                    <h3 class="h5 mb-2" style="font-family:'DM Serif Display', serif;">No notifications yet</h3>
                    <p class="text-muted small mb-4" style="max-width: 380px; margin: 0 auto;">You're all caught up! Updates regarding your bookings and listings will appear here.</p>
                    <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn text-white px-4 py-2" style="background:var(--avastra-primary); border-radius:8px;">Explore Spaces</a>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): ?>
                        <div class="list-group-item p-3 d-flex align-items-start gap-3 <?= $n['is_read'] ? 'bg-white' : 'bg-light'; ?>">
                            <div class="p-2 rounded-circle text-white flex-shrink-0" style="background: var(--avastra-primary);">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h4 class="h6 mb-0 text-dark <?= $n['is_read'] ? 'fw-normal' : 'fw-bold'; ?>">
                                        <?= htmlspecialchars($n['title']); ?>
                                    </h4>
                                    <small class="text-muted font-mono" style="font-size:12px;"><?= date('d M, h:i A', strtotime($n['created_at'])); ?></small>
                                </div>
                                <p class="text-secondary small mb-0"><?= htmlspecialchars($n['message']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
