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

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Reports & Analytics</h3>
                <p class="text-muted small mb-0">Generate financial summaries, space utilization reports, and user activity metrics.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i> Print Summary Report
            </button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="admin-card text-center mb-0">
                    <i class="bi bi-calendar-check fs-1 text-primary mb-2"></i>
                    <div class="stat-label">30-Day Platform Revenue</div>
                    <div class="stat-value text-primary mt-1">₹<?= number_format((float)$monthlyRevenue, 2); ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="admin-card text-center mb-0">
                    <i class="bi bi-trophy fs-1 text-warning mb-2"></i>
                    <div class="stat-label">Top Demand Category</div>
                    <div class="stat-value text-dark mt-1"><?= htmlspecialchars($topCategory['name'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="admin-card text-center mb-0">
                    <i class="bi bi-activity fs-1 text-success mb-2"></i>
                    <div class="stat-label">Active Ongoing Rentals</div>
                    <div class="stat-value text-success mt-1"><?= number_format((int)$activeBookingsCount); ?></div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <h5 class="fw-bold mb-3">System Performance Summary</h5>
            <p class="text-muted">SpaceShare marketplace operation overview report generated for academic demonstration and audit.</p>
            
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Metric Name</th>
                            <th>Current Value</th>
                            <th>Status / Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Registered Accounts</td>
                            <td><strong><?= (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(); ?></strong></td>
                            <td><span class="badge bg-success">Healthy Growth</span></td>
                        </tr>
                        <tr>
                            <td>Total Active Spaces</td>
                            <td><strong><?= (int)$db->query("SELECT COUNT(*) FROM spaces WHERE verification_status = 'approved'")->fetchColumn(); ?></strong></td>
                            <td><span class="badge bg-info text-dark">Verified & Searchable</span></td>
                        </tr>
                        <tr>
                            <td>Completed Rental Transactions</td>
                            <td><strong><?= (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn(); ?></strong></td>
                            <td><span class="badge bg-success">100% Fulfilled</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
