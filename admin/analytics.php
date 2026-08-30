<?php
$pageTitle = 'Analytics';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$adminModel = new Admin();
$kpis = $adminModel->get6KPICards();
$categories = $adminModel->getCategoryAnalytics();
$db = Database::getInstance();

$monthlyRevenue = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn() ?? 0.00;
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color:#0d5c46;">Marketplace Analytics & Insights</h3>
                <p class="text-muted small mb-0">In-depth performance metrics, category utilization, and platform growth analytics.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="avastra-card text-center mb-0">
                    <div class="kpi-label">30-Day Revenue</div>
                    <div class="kpi-value text-success">₹<?= number_format((float)$monthlyRevenue, 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="avastra-card text-center mb-0">
                    <div class="kpi-label">Total Listings</div>
                    <div class="kpi-value"><?= $kpis['active_spaces']; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="avastra-card text-center mb-0">
                    <div class="kpi-label">Total Reservations</div>
                    <div class="kpi-value"><?= $kpis['total_bookings']; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="avastra-card text-center mb-0">
                    <div class="kpi-label">Registered Accounts</div>
                    <div class="kpi-value"><?= $kpis['total_users']; ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="avastra-card">
                    <h5 class="fw-bold mb-3">Revenue & Booking Growth (2026)</h5>
                    <canvas id="growthChart" height="250"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="avastra-card">
                    <h5 class="fw-bold mb-3">Category Breakdown</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Listed Spaces</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['name']); ?></td>
                                        <td class="text-end font-monospace fw-bold"><?= $c['total_spaces']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('growthChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                    datasets: [
                        {
                            label: 'Revenue (₹)',
                            data: [12000, 18000, 25000, 32000, 28000, 45000, 52000, 68000],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Bookings Count',
                            data: [5, 12, 18, 24, 30, 42, 55, 68],
                            borderColor: '#0284c7',
                            borderDash: [5, 5],
                            tension: 0.3
                        }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
