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

    <main class="p-3 p-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <p class="text-muted small mb-0">Manage space owners, monitor space listings performance, gross revenue, and verification status.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-avastra btn-sm rounded-pill px-3 align-self-start align-self-sm-center">
                <i class="bi bi-download me-1"></i> Export Owners
            </button>
        </div>

        <div class="avastra-card p-3">
            <?php if (empty($owners)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-person-badge fs-1 text-secondary mb-2 d-block"></i>
                    No space owner accounts registered in the marketplace yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle mb-0" style="font-size:0.825rem;">
                        <thead>
                            <tr>
                                <th><i class="bi bi-person me-1 text-muted"></i> Owner Name / Contact</th>
                                <th><i class="bi bi-envelope me-1 text-muted"></i> Email & Phone</th>
                                <th><i class="bi bi-building me-1 text-muted"></i> Listed Spaces</th>
                                <th><i class="bi bi-calendar-check me-1 text-muted"></i> Bookings</th>
                                <th><i class="bi bi-currency-rupee me-1 text-muted"></i> Gross Revenue</th>
                                <th><i class="bi bi-clock me-1 text-muted"></i> Joined Date</th>
                                <th><i class="bi bi-info-circle me-1 text-muted"></i> Status</th>
                                <th><i class="bi bi-three-dots me-1 text-muted"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($owners as $o): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($o['full_name']); ?></div>
                                        <small class="text-muted font-mono">ID: #<?= $o['id']; ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($o['email']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($o['phone'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border font-mono"><?= $o['total_spaces']; ?> spaces</span></td>
                                    <td><span class="badge bg-light text-dark border font-mono"><?= $o['total_bookings']; ?> bookings</span></td>
                                    <td class="fw-bold text-success">₹<?= number_format((float)$o['total_revenue'], 2); ?></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($o['created_at'])); ?></small></td>
                                    <td>
                                        <?php if ($o['status'] === 'active'): ?>
                                            <span class="badge-status active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge-status blocked"><i class="bi bi-slash-circle-fill"></i> Suspended</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#ownerModal<?= $o['id']; ?>">
                                            <i class="bi bi-eye"></i> Details
                                        </button>

                                        <!-- Owner Detail Modal -->
                                        <div class="modal fade" id="ownerModal<?= $o['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold"><i class="bi bi-person-badge me-2 text-success"></i> Owner Profile #<?= $o['id']; ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="fw-bold fs-6 mb-1"><?= htmlspecialchars($o['full_name']); ?></div>
                                                        <p class="text-muted small mb-3"><?= htmlspecialchars($o['email']); ?> &bull; <?= htmlspecialchars($o['phone'] ?? 'N/A'); ?></p>
                                                        <div class="row g-2 border-top pt-2 small">
                                                            <div class="col-6"><strong>Listed Spaces:</strong> <?= $o['total_spaces']; ?></div>
                                                            <div class="col-6"><strong>Total Reservations:</strong> <?= $o['total_bookings']; ?></div>
                                                            <div class="col-6"><strong>Gross Revenue:</strong> ₹<?= number_format((float)$o['total_revenue'], 2); ?></div>
                                                            <div class="col-6"><strong>Member Since:</strong> <?= date('d M Y', strtotime($o['created_at'])); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
