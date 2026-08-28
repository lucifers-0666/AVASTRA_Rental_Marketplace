<?php
$pageTitle = 'Complaints & Disputes';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$adminModel = new Admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['status'])) {
    $cId = (int) $_POST['complaint_id'];
    $status = $_POST['status'];
    $notes = trim($_POST['resolution_notes'] ?? '');

    $stmt = $db->prepare("UPDATE complaints SET status = :status, resolution_notes = :notes WHERE id = :id");
    if ($stmt->execute([':status' => $status, ':notes' => $notes, ':id' => $cId])) {
        $adminModel->logAction($currentUser['id'], 'RESOLVE_COMPLAINT', 'COMPLAINT', $cId, "Status updated to {$status}");
        $message = "Complaint #{$cId} updated to " . strtoupper($status);
    }
}

$sql = "
    SELECT c.*, u.full_name AS user_name, u.email AS user_email, b.booking_code
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    JOIN bookings b ON c.booking_id = b.id
    ORDER BY c.created_at DESC
";
$complaints = $db->query($sql)->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Disputes & Complaints</h3>
                <p class="text-muted small mb-0">Handle customer and space owner disputes, investigate tickets, and record resolutions.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <?php if (empty($complaints)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
                    No active complaint tickets. All marketplace transactions running smoothly!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>User</th>
                                <th>Booking Ref</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Date Filed</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $comp): ?>
                                <tr>
                                    <td><strong>#CMP-<?= $comp['id']; ?></strong></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($comp['user_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($comp['user_email']); ?></small>
                                    </td>
                                    <td><code class="text-primary"><?= htmlspecialchars($comp['booking_code']); ?></code></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($comp['subject']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars(substr($comp['description'], 0, 60)); ?>...</small>
                                    </td>
                                    <td><span class="badge-status <?= $comp['status']; ?>"><?= ucfirst(str_replace('_', ' ', $comp['status'])); ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($comp['created_at'])); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resolveModal<?= $comp['id']; ?>">
                                            Manage Ticket
                                        </button>

                                        <!-- Resolve Modal -->
                                        <div class="modal fade" id="resolveModal<?= $comp['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Manage Dispute Ticket #CMP-<?= $comp['id']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="complaint_id" value="<?= $comp['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Ticket Description</label>
                                                                <div class="p-3 bg-light rounded border small"><?= nl2br(htmlspecialchars($comp['description'])); ?></div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Update Status</label>
                                                                <select name="status" class="form-select">
                                                                    <option value="open" <?= ($comp['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                                                                    <option value="in_progress" <?= ($comp['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                                                    <option value="resolved" <?= ($comp['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                                                                    <option value="closed" <?= ($comp['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Resolution Notes / Action Taken</label>
                                                                <textarea name="resolution_notes" class="form-control" rows="3" placeholder="Enter findings, refund details, or resolution summary..."><?= htmlspecialchars($comp['resolution_notes'] ?? ''); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Ticket Changes</button>
                                                        </div>
                                                    </form>
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
