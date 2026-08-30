<?php
$pageTitle = 'Bookings';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$statusFilter = $_GET['status'] ?? 'all';

$whereClauses = ["1=1"];
$params = [];

if ($statusFilter !== 'all') {
    $whereClauses[] = "b.status = :status";
    $params[':status'] = $statusFilter;
}

$sql = "
    SELECT b.*, s.title AS space_title, u.full_name AS seeker_name, u.email AS seeker_email,
           o.full_name AS owner_name
    FROM bookings b
    JOIN spaces s ON b.space_id = s.id
    JOIN users u ON b.seeker_id = u.id
    JOIN users o ON s.owner_id = o.id
    WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY b.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-3 p-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <p class="text-muted small mb-0">Monitor system reservations, rental duration dates, renter & owner details, and status timelines.</p>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="bookings.php?status=all" class="btn <?= ($statusFilter === 'all') ? 'btn-avastra fw-bold' : 'btn-outline-secondary'; ?>">All</a>
                <a href="bookings.php?status=pending" class="btn <?= ($statusFilter === 'pending') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'; ?>">Pending</a>
                <a href="bookings.php?status=confirmed" class="btn <?= ($statusFilter === 'confirmed') ? 'btn-avastra fw-bold' : 'btn-outline-secondary'; ?>">Confirmed</a>
                <a href="bookings.php?status=completed" class="btn <?= ($statusFilter === 'completed') ? 'btn-info text-white fw-bold' : 'btn-outline-secondary'; ?>">Completed</a>
            </div>
        </div>

        <div class="avastra-card p-3">
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-x fs-1 text-secondary mb-2 d-block"></i>
                    No bookings found for the selected status.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle mb-0" style="font-size:0.825rem;">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash me-1 text-muted"></i> Booking Code</th>
                                <th><i class="bi bi-person me-1 text-muted"></i> Renter / Seeker</th>
                                <th><i class="bi bi-building me-1 text-muted"></i> Space Title</th>
                                <th><i class="bi bi-person-badge me-1 text-muted"></i> Space Owner</th>
                                <th><i class="bi bi-calendar-range me-1 text-muted"></i> Duration</th>
                                <th><i class="bi bi-currency-rupee me-1 text-muted"></i> Total Amount</th>
                                <th><i class="bi bi-info-circle me-1 text-muted"></i> Status</th>
                                <th><i class="bi bi-three-dots me-1 text-muted"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark font-mono"><?= htmlspecialchars($b['booking_code']); ?></div>
                                        <small class="text-muted"><?= date('d M Y', strtotime($b['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($b['seeker_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['seeker_email']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($b['space_title']); ?></div>
                                        <small class="text-muted">Purpose: <?= htmlspecialchars($b['purpose']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($b['owner_name']); ?></td>
                                    <td>
                                        <div><strong><?= date('d M Y', strtotime($b['start_date'])); ?></strong> to <strong><?= date('d M Y', strtotime($b['end_date'])); ?></strong></div>
                                        <small class="text-muted font-mono"><?= $b['total_days']; ?> Days</small>
                                    </td>
                                    <td class="fw-bold text-success">₹<?= number_format($b['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge-status <?= $b['status']; ?>"><?= ucfirst($b['status']); ?></span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#bookingModal<?= $b['id']; ?>">
                                            <i class="bi bi-eye"></i> Details
                                        </button>

                                        <!-- Booking Detail Modal -->
                                        <div class="modal fade" id="bookingModal<?= $b['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2 text-success"></i> Booking Ref #<?= htmlspecialchars($b['booking_code']); ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="fw-bold fs-6 mb-1"><?= htmlspecialchars($b['space_title']); ?></div>
                                                        <div class="row g-2 border-top pt-2 small">
                                                            <div class="col-6"><strong>Renter:</strong> <?= htmlspecialchars($b['seeker_name']); ?></div>
                                                            <div class="col-6"><strong>Owner:</strong> <?= htmlspecialchars($b['owner_name']); ?></div>
                                                            <div class="col-6"><strong>Start Date:</strong> <?= date('d M Y', strtotime($b['start_date'])); ?></div>
                                                            <div class="col-6"><strong>End Date:</strong> <?= date('d M Y', strtotime($b['end_date'])); ?></div>
                                                            <div class="col-6"><strong>Base Amount:</strong> ₹<?= number_format($b['base_amount'], 2); ?></div>
                                                            <div class="col-6"><strong>Platform Fee:</strong> ₹<?= number_format($b['platform_fee'], 2); ?></div>
                                                            <div class="col-6"><strong>Total Price:</strong> ₹<?= number_format($b['total_amount'], 2); ?></div>
                                                            <div class="col-6"><strong>Status:</strong> <?= ucfirst($b['status']); ?></div>
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
