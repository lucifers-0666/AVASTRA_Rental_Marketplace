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
$topCities = $db->query("SELECT city, COUNT(id) AS space_count FROM spaces WHERE verification_status = 'approved' GROUP BY city ORDER BY space_count DESC LIMIT 5")->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-3 p-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <h4 class="fw-bold mb-1" style="color:#0B2A18;">Marketplace Analytics & Insights</h4>
                <p class="text-muted small mb-0">In-depth performance metrics, category utilization, and platform growth analytics.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-avastra btn-sm rounded-pill px-3 align-self-start align-self-sm-center">
                <i class="bi bi-printer me-1"></i> Print Report
            </button>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-sm-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">30-Day Gross Revenue</div>
                            <div class="kpi-value text-success fs-4">₹<?= number_format((float)$monthlyRevenue, 2); ?></div>
                            <div class="kpi-subtext">Completed Payments</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-currency-rupee"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Active Listed Spaces</div>
                            <div class="kpi-value fs-4"><?= number_format($kpis['active_spaces']); ?></div>
                            <div class="kpi-subtext">Verified Marketplace Inventory</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-building-check"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Total Reservations</div>
                            <div class="kpi-value fs-4"><?= number_format($kpis['total_bookings']); ?></div>
                            <div class="kpi-subtext">Bookings Executed</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-calendar-check-fill"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Registered Accounts</div>
                            <div class="kpi-value fs-4"><?= number_format($kpis['total_users']); ?></div>
                            <div class="kpi-subtext">Seekers & Owners</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="avastra-card p-3 mb-0 h-100">
                    <h6 class="fw-bold mb-2" style="color:#0B2A18;">Platform Growth & Revenue Trends (2026)</h6>
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="dualGrowthChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="avastra-card p-3 mb-0 h-100">
                    <h6 class="fw-bold mb-2" style="color:#0B2A18;">Category Utilization</h6>
                    <div style="position: relative; height: 240px; width: 100%;">
                        <canvas id="categoryBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="avastra-card p-3 mb-0">
                    <h6 class="fw-bold mb-2" style="color:#0B2A18;"><i class="bi bi-tags me-1 text-success"></i> Category Inventory Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-avastra align-middle mb-0" style="font-size:0.825rem;">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Listed Spaces</th>
                                    <th>Market Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $totalSpacesCount = max(1, array_sum(array_column($categories, 'total_spaces')));
                                    foreach ($categories as $c): 
                                        $percent = round(($c['total_spaces'] / $totalSpacesCount) * 100, 1);
                                ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($c['name']); ?></td>
                                        <td><span class="badge bg-light text-dark border font-monospace"><?= $c['total_spaces']; ?> spaces</span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar" style="width: <?= $percent; ?>%; background:#145C4A;"></div>
                                                </div>
                                                <span class="small font-monospace text-muted"><?= $percent; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="avastra-card p-3 mb-0">
                    <h6 class="fw-bold mb-2" style="color:#0B2A18;"><i class="bi bi-geo-alt me-1 text-success"></i> Top Active Cities</h6>
                    <?php if (empty($topCities)): ?>
                        <div class="text-center py-4 text-muted small">No verified city data available yet.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topCities as $city): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-building text-success"></i>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($city['city']); ?></span>
                                    </div>
                                    <span class="badge bg-light text-dark border fw-bold font-monospace"><?= $city['space_count']; ?> spaces</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'];
            const revenueData = [12000, 18000, 25000, 32000, 28000, 45000, 52000, 68000];
            const bookingsData = [5, 12, 18, 24, 30, 42, 55, 68];

            const ctxGrowth = document.getElementById('dualGrowthChart').getContext('2d');
            new Chart(ctxGrowth, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Revenue (₹)',
                            data: revenueData,
                            backgroundColor: 'rgba(20, 92, 74, 0.75)',
                            borderColor: '#145C4A',
                            borderWidth: 1,
                            borderRadius: 4,
                            yAxisID: 'yRevenue'
                        },
                        {
                            type: 'line',
                            label: 'Bookings Volume',
                            data: bookingsData,
                            borderColor: '#0B2A18',
                            backgroundColor: '#0B2A18',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.3,
                            pointRadius: 4,
                            yAxisID: 'yBookings'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: { grid: { display: false } },
                        yRevenue: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Revenue (₹)' },
                            grid: { color: 'rgba(0,0,0,0.04)' }
                        },
                        yBookings: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Bookings Count' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });

            const ctxCatBar = document.getElementById('categoryBarChart').getContext('2d');
            new Chart(ctxCatBar, {
                type: 'bar',
                data: {
                    labels: [
                        <?php foreach ($categories as $cat) { echo "'" . addslashes($cat['name']) . "',"; } ?>
                    ],
                    datasets: [{
                        label: 'Listed Spaces',
                        data: [
                            <?php foreach ($categories as $cat) { echo $cat['total_spaces'] . ","; } ?>
                        ],
                        backgroundColor: '#145C4A',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
