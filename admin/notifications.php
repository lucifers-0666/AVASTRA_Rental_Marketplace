<?php
$pageTitle = 'Notifications';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$notifications = $db->query("
    SELECT n.*, u.full_name 
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT 50
")->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color:#0d5c46;">Admin Notifications Center</h3>
                <p class="text-muted small mb-0">System alerts, automated notifications, and marketplace status messages.</p>
            </div>
        </div>

        <div class="avastra-card">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 text-secondary mb-2 d-block"></i>
                    No unread system notifications right now.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 bg-light text-success rounded-circle">
                                    <i class="bi bi-bell-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($n['title']); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($n['message']); ?></small>
                                </div>
                            </div>
                            <small class="text-muted"><?= date('d M Y, h:i A', strtotime($n['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
