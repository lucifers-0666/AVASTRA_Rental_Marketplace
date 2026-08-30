<?php
$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();

// Summary metrics
$monthlyRevenue = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?? 0.00;
$topCategory = $db->query("SELECT c.name, COUNT(s.id) as cnt FROM spaces s JOIN categories c ON s.category_id = c.id GROUP BY c.id ORDER BY cnt DESC LIMIT 1")->fetch();
$activeBookingsCount = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'")->fetchColumn() ?? 0;
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1" style="color:#0B2A18;">Reports & Analytics Summary</h4>
                <p class="text-muted small mb-0">Generate financial summaries, space utilization reports, and user activity metrics.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-avastra btn-sm rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Print Summary Report
            </button>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="avastra-card text-center mb-0">
                    <i class="bi bi-calendar-check fs-2 text-success mb-2"></i>
                    <div class="kpi-label">30-Day Platform Revenue</div>
                    <div class="kpi-value text-success mt-1">₹<?= number_format((float)$monthlyRevenue, 2); ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="avastra-card text-center mb-0">
                    <i class="bi bi-trophy fs-2 text-warning mb-2"></i>
                    <div class="kpi-label">Top Demand Category</div>
                    <div class="kpi-value text-dark mt-1" style="font-size:1.25rem;"><?= htmlspecialchars($topCategory['name'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="avastra-card text-center mb-0">
                    <i class="bi bi-activity fs-2 text-primary mb-2"></i>
                    <div class="kpi-label">Active Ongoing Rentals</div>
                    <div class="kpi-value text-dark mt-1"><?= number_format((int)$activeBookingsCount); ?></div>
                </div>
            </div>
        </div>

        <div class="avastra-card">
            <h6 class="fw-bold mb-3" style="color:#0B2A18;">System Performance Summary</h6>
            <p class="text-muted small">AVASTRA marketplace operation overview report generated for system administrative audit.</p>
            
            <div class="table-responsive">
                <table class="table table-avastra align-middle">
                    <thead>
                        <tr>
                            <th>Metric Name</th>
                            <th>Current Value</th>
                            <th>Status / Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold text-dark">Total Registered Accounts</td>
                            <td class="font-monospace fw-bold"><?= (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></td>
                            <td><span class="badge-status active">Healthy Growth</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-dark">Total Active Spaces</td>
                            <td class="font-monospace fw-bold"><?= (int)$db->query("SELECT COUNT(*) FROM spaces WHERE verification_status = 'approved'")->fetchColumn(); ?></td>
                            <td><span class="badge-status active">Verified & Searchable</span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-dark">Completed Rental Transactions</td>
                            <td class="font-monospace fw-bold"><?= (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn(); ?></td>
                            <td><span class="badge-status active">100% Fulfilled</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
