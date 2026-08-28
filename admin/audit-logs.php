<?php
$pageTitle = 'Security Audit Logs';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();

$sql = "
    SELECT a.*, u.full_name, u.email
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 100
";
$logs = $db->query($sql)->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Security Audit Logs</h3>
                <p class="text-muted small mb-0">Immutable record of administrative actions, verification approvals, and system state modifications.</p>
            </div>
        </div>

        <div class="admin-card">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5 text-muted">No audit log entries recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User / Performer</th>
                                <th>Action Event</th>
                                <th>Entity</th>
                                <th>IP Address</th>
                                <th>Event Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td><small class="text-muted fw-bold"><?= date('d M Y, h:i:s A', strtotime($l['created_at'])); ?></small></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($l['full_name'] ?? 'System / Automated'); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($l['email'] ?? ''); ?></small>
                                    </td>
                                    <td><span class="badge bg-dark text-white"><?= htmlspecialchars($l['action']); ?></span></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($l['entity_type'] ?? 'N/A'); ?> <?= $l['entity_id'] ? '#' . $l['entity_id'] : ''; ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1'); ?></code></td>
                                    <td><small class="text-secondary"><?= htmlspecialchars($l['details'] ?? '-'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
