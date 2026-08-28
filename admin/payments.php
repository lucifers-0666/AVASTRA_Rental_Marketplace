<?php
$pageTitle = 'Transactions & Payment Logs';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();

$sql = "
    SELECT p.*, b.booking_code, u.full_name AS seeker_name, s.title AS space_title
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.seeker_id = u.id
    JOIN spaces s ON b.space_id = s.id
    ORDER BY p.created_at DESC
";
$payments = $db->query($sql)->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Transaction & Payment Logs</h3>
                <p class="text-muted small mb-0">Review all online and offline payment records across the marketplace.</p>
            </div>
        </div>

        <div class="admin-card">
            <?php if (empty($payments)): ?>
                <div class="text-center py-5 text-muted">No transactions recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Booking Code</th>
                                <th>Space & Seeker</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Paid Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><code class="fw-bold text-primary"><?= htmlspecialchars($p['transaction_id'] ?? 'N/A'); ?></code></td>
                                    <td><strong><?= htmlspecialchars($p['booking_code']); ?></strong></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($p['space_title']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($p['seeker_name']); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= strtoupper($p['payment_method']); ?></span></td>
                                    <td class="fw-bold text-success">₹<?= number_format($p['amount'], 2); ?></td>
                                    <td><span class="badge-status <?= $p['status']; ?>"><?= ucfirst($p['status']); ?></span></td>
                                    <td><small class="text-muted"><?= $p['paid_at'] ? date('d M Y, h:i A', strtotime($p['paid_at'])) : 'Pending'; ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
