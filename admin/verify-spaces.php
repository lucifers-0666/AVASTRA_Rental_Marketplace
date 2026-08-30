<?php
$pageTitle = 'Verification';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$adminModel = new Admin();
$db = Database::getInstance();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $spaceId = (int) ($_POST['space_id'] ?? 0);
    if ($_POST['action'] === 'approve') {
        if ($adminModel->approveSpace($spaceId, $currentUser['id'])) {
            $message = "Space #{$spaceId} has been successfully approved and published!";
        } else {
            $error = "Failed to approve space.";
        }
    } elseif ($_POST['action'] === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? 'Space specifications do not comply with marketplace standards.');
        if ($adminModel->rejectSpace($spaceId, $reason, $currentUser['id'])) {
            $message = "Space #{$spaceId} has been rejected.";
        } else {
            $error = "Failed to reject space.";
        }
    } elseif ($_POST['action'] === 'request_info') {
        $infoDetails = trim($_POST['info_details'] ?? '');
        $adminModel->logAction($currentUser['id'], 'REQUEST_INFO', 'SPACE', $spaceId, "Information requested: {$infoDetails}");
        $message = "Requested additional information from space owner for Space #{$spaceId}.";
    }
}

// Fetch all spaces by verification status
$statusFilter = $_GET['status'] ?? 'pending';
$sql = "
    SELECT s.*, u.full_name AS owner_name, u.email AS owner_email, u.phone AS owner_phone, c.name AS category_name
    FROM spaces s
    JOIN users u ON s.owner_id = u.id
    JOIN categories c ON s.category_id = c.id
    WHERE s.verification_status = :status
    ORDER BY s.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute([':status' => $statusFilter]);
$spaces = $stmt->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color:#0d5c46;">Verification Queue</h3>
                <p class="text-muted small mb-0">Review pending space listings, inspect specifications, approve, or request further information.</p>
            </div>

            <div class="btn-group" role="group">
                <a href="verify-spaces.php?status=pending" class="btn btn-sm <?= ($statusFilter === 'pending') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'; ?>">
                    Pending Review (<?= $adminModel->get6KPICards()['pending_verifications']; ?>)
                </a>
                <a href="verify-spaces.php?status=approved" class="btn btn-sm <?= ($statusFilter === 'approved') ? 'btn-success fw-bold' : 'btn-outline-secondary'; ?>">
                    Approved
                </a>
                <a href="verify-spaces.php?status=rejected" class="btn btn-sm <?= ($statusFilter === 'rejected') ? 'btn-danger fw-bold' : 'btn-outline-secondary'; ?>">
                    Rejected
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="avastra-card">
            <?php if (empty($spaces)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                    No space listings currently under <strong><?= htmlspecialchars(strtoupper($statusFilter)); ?></strong> queue.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle">
                        <thead>
                            <tr>
                                <th>Applicant & Space</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Size & Rates</th>
                                <th>Submitted Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spaces as $s): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($s['title']); ?></div>
                                        <small class="text-muted">Owner: <strong><?= htmlspecialchars($s['owner_name']); ?></strong> (<?= htmlspecialchars($s['owner_email']); ?>)</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['category_name']); ?></span></td>
                                    <td><?= htmlspecialchars($s['city']); ?>, <?= htmlspecialchars($s['state']); ?></td>
                                    <td>
                                        <div class="fw-bold"><?= $s['total_sqft']; ?> sq.ft</div>
                                        <small class="text-success fw-bold">₹<?= number_format($s['daily_rate'], 2); ?> / day</small>
                                    </td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($s['created_at'])); ?></small></td>
                                    <td>
                                        <?php if ($s['verification_status'] === 'approved'): ?>
                                            <span class="badge-status approved"><i class="bi bi-check-circle"></i> Approved</span>
                                        <?php elseif ($s['verification_status'] === 'pending'): ?>
                                            <span class="badge-status pending"><i class="bi bi-clock"></i> Pending Review</span>
                                        <?php else: ?>
                                            <span class="badge-status rejected"><i class="bi bi-x-circle"></i> Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($s['verification_status'] === 'pending'): ?>
                                            <div class="d-flex gap-1">
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="space_id" value="<?= $s['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-avastra">Approve</button>
                                                </form>

                                                <button type="button" class="btn btn-sm btn-outline-warning text-dark" data-bs-toggle="modal" data-bs-target="#reqInfoModal<?= $s['id']; ?>">
                                                    Req Info
                                                </button>

                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $s['id']; ?>">
                                                    Reject
                                                </button>
                                            </div>

                                            <!-- Request Info Modal -->
                                            <div class="modal fade" id="reqInfoModal<?= $s['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Request Information for #<?= $s['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="space_id" value="<?= $s['id']; ?>">
                                                                <input type="hidden" name="action" value="request_info">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Details Requested</label>
                                                                    <textarea name="info_details" class="form-control" rows="3" required placeholder="Describe additional photos, certificates, or documents needed..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-warning">Send Request</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Reject Modal -->
                                            <div class="modal fade" id="rejectModal<?= $s['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Reject Listing #<?= $s['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="space_id" value="<?= $s['id']; ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Reason for Rejection</label>
                                                                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Specify guidelines violation or missing criteria..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">No actions</span>
                                        <?php endif; ?>
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
