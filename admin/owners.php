<?php
$pageTitle = 'Owners';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$adminModel = new Admin();
$owners = $adminModel->getOwnersList();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color:#0d5c46;">Space Owners Management</h3>
                <p class="text-muted small mb-0">Overview of space partners, performance metrics, listed inventory, and gross earnings.</p>
            </div>
        </div>

        <div class="avastra-card">
            <?php if (empty($owners)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-person-badge fs-1 text-secondary mb-2 d-block"></i>
                    No space owner accounts listed yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle">
                        <thead>
                            <tr>
                                <th>Owner Name & Email</th>
                                <th>Contact Phone</th>
                                <th>Listed Spaces</th>
                                <th>Total Bookings</th>
                                <th>Earned Revenue</th>
                                <th>Joined Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($owners as $o): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($o['full_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($o['email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($o['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?= $o['total_spaces']; ?> spaces</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace"><?= $o['total_bookings']; ?> bookings</span>
                                    </td>
                                    <td class="fw-bold text-success">
                                        ₹<?= number_format((float)$o['total_revenue'], 2); ?>
                                    </td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($o['created_at'])); ?></small></td>
                                    <td>
                                        <?php if ($o['status'] === 'active'): ?>
                                            <span class="badge-status active"><i class="bi bi-check-circle"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge-status blocked"><i class="bi bi-slash-circle"></i> <?= ucfirst($o['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="spaces.php?owner_id=<?= $o['id']; ?>" class="btn btn-sm btn-outline-success">
                                            View Spaces
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
