<?php
$pageTitle = 'Booking Management';
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

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Booking Management</h3>
                <p class="text-muted small mb-0">Monitor system bookings, date ranges, total calculated fees, and status timelines.</p>
            </div>
            <div class="btn-group">
                <a href="bookings.php?status=all" class="btn btn-sm <?= ($statusFilter === 'all') ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                <a href="bookings.php?status=pending" class="btn btn-sm <?= ($statusFilter === 'pending') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                <a href="bookings.php?status=confirmed" class="btn btn-sm <?= ($statusFilter === 'confirmed') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Confirmed</a>
                <a href="bookings.php?status=completed" class="btn btn-sm <?= ($statusFilter === 'completed') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Completed</a>
            </div>
        </div>

        <div class="admin-card">
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5 text-muted">No bookings found for the selected status.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Booking Code</th>
                                <th>Space Title</th>
                                <th>Seeker</th>
                                <th>Owner</th>
                                <th>Rental Period</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['booking_code']); ?></div>
                                        <small class="text-muted"><?= date('d M Y', strtotime($b['created_at'])); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($b['space_title']); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($b['seeker_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['seeker_email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($b['owner_name']); ?></td>
                                    <td>
                                        <div><strong><?= date('d M Y', strtotime($b['start_date'])); ?></strong> to <strong><?= date('d M Y', strtotime($b['end_date'])); ?></strong></div>
                                        <small class="text-muted"><?= $b['total_days']; ?> Days</small>
                                    </td>
                                    <td class="fw-bold text-success">₹<?= number_format($b['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge-status <?= $b['status']; ?>"><?= ucfirst($b['status']); ?></span>
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
