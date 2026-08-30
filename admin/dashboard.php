<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$adminModel = new Admin();
$kpis = $adminModel->get6KPICards();
$attentionItems = $adminModel->getNeedsAttentionQueue();
$categoryAnalytics = $adminModel->getCategoryAnalytics();

$db = Database::getInstance();
$recentLogs = $db->query("
    SELECT a.*, u.full_name AS user_name 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
")->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-3 p-md-4">
        <!-- Content Subtitle Header (No Duplicate 'Dashboard' Title) -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <p class="text-muted small mb-0">Monitor AVASTRA activity, marketplace health, and pending actions.</p>
            </div>
            <a href="verify-spaces.php" class="btn btn-avastra rounded-pill px-3 py-1 text-nowrap align-self-start align-self-sm-center">
                <i class="bi bi-shield-check me-1"></i> Verification Queue (<?= $kpis['pending_verifications']; ?>)
            </a>
        </div>

        <!-- 6 KPI CARDS (Clean Operational Cards) -->
        <div class="row g-2 g-md-3 mb-3">
            <!-- 1. Total Users -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Total Users</div>
                            <div class="kpi-value fs-4"><?= number_format($kpis['total_users']); ?></div>
                            <div class="kpi-subtext">Registered</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
                    </div>
                </div>
            </div>

            <!-- 2. Active Owners -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Active Owners</div>
                            <div class="kpi-value fs-4"><?= number_format($kpis['active_owners']); ?></div>
                            <div class="kpi-subtext">Space Owners</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-person-badge-fill"></i></div>
                    </div>
                </div>
            </div>

            <!-- 3. Active Spaces -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Active Spaces</div>
                            <div class="kpi-value fs-4"><?= number_format($kpis['active_spaces']); ?></div>
                            <div class="kpi-subtext">Verified</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-building-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- 4. Total Bookings -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Total Bookings</div>
                            <div class="kpi-value fs-4"><?= number_format($kpis['total_bookings']); ?></div>
                            <div class="kpi-subtext">Reservations</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-calendar-check-fill"></i></div>
                    </div>
                </div>
            </div>

            <!-- 5. Revenue -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Revenue</div>
                            <div class="kpi-value fs-5">₹<?= number_format($kpis['total_revenue'], 0); ?></div>
                            <div class="kpi-subtext">Gross Marketplace</div>
                        </div>
                        <div class="kpi-icon"><i class="bi bi-currency-rupee"></i></div>
                    </div>
                </div>
            </div>

            <!-- 6. Pending Verification -->
            <div class="col-xl-2 col-md-4 col-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Pending Verif.</div>
                            <div class="kpi-value fs-4 text-warning"><?= number_format($kpis['pending_verifications']); ?></div>
                            <div class="kpi-subtext text-warning">Requires Review</div>
                        </div>
                        <div class="kpi-icon warning"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Needs Your Attention -->
        <div class="avastra-card p-3 mb-3">
            <h6 class="serif-heading fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill text-warning"></i> Needs Your Attention
            </h6>

            <?php if (empty($attentionItems)): ?>
                <div class="p-2 text-center text-muted small bg-light rounded">
                    <i class="bi bi-check-circle-fill text-success me-1"></i> No urgent admin actions required. All operational queues clean!
                </div>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($attentionItems as $item): ?>
                        <div class="col-md-4">
                            <div class="attention-item py-2 px-3 mb-0">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="attention-icon <?= ($item['priority'] === 'High') ? 'bg-danger text-white' : 'bg-warning text-dark'; ?>" style="width:32px; height:32px; font-size:0.9rem;">
                                        <i class="bi <?= $item['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small mb-0" style="font-size:0.825rem;"><?= htmlspecialchars($item['title']); ?></div>
                                        <span class="badge bg-light text-dark border me-1" style="font-size:10px;"><?= $item['module']; ?></span>
                                        <span class="badge <?= ($item['priority'] === 'High') ? 'bg-danger' : 'bg-warning text-dark'; ?>" style="font-size:10px;"><?= $item['priority']; ?></span>
                                    </div>
                                </div>
                                <a href="<?= $item['action_link']; ?>" class="btn btn-sm btn-outline-avastra fw-bold ms-2 py-0 px-2 text-nowrap" style="font-size:0.75rem;">
                                    <?= $item['action_label']; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- BOOKING & REVENUE OVERVIEW CHART CARD -->
        <div class="avastra-card p-3 mb-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2">
                <div>
                    <h6 class="serif-heading fw-bold mb-0">Booking & Revenue Overview</h6>
                    <small class="text-muted" style="font-size:0.75rem;">Year-to-date marketplace performance</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-avastra active px-3 py-1" id="btnBookings">Bookings</button>
                        <button type="button" class="btn btn-outline-avastra px-3 py-1" id="btnRevenue">Revenue</button>
                    </div>
                    <select class="form-select form-select-sm" style="width:auto; font-size:0.75rem;">
                        <option>30D</option>
                        <option>7D</option>
                        <option>3M</option>
                        <option>6M</option>
                        <option>1Y</option>
                    </select>
                </div>
            </div>

            <div style="position: relative; height: 220px; width: 100%;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- SECONDARY ANALYTICS (Two-column Desktop Layout) -->
        <div class="row g-3 mb-3">
            <!-- LEFT: Bookings by Space Type -->
            <div class="col-lg-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <h6 class="serif-heading fw-bold mb-0">Bookings by Space Type</h6>
                    <small class="text-muted d-block mb-2" style="font-size:0.75rem;">Distribution across Office, Studio, Meeting, Warehouse, Event, Storage</small>
                    <div style="position: relative; height: 200px; width: 100%;">
                        <canvas id="spaceTypeBarChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- RIGHT: User & Owner Growth -->
            <div class="col-lg-6">
                <div class="avastra-card p-3 mb-0 h-100">
                    <h6 class="serif-heading fw-bold mb-0">User & Owner Growth</h6>
                    <small class="text-muted d-block mb-2" style="font-size:0.75rem;">Cumulative seeker and space owner accounts over time</small>
                    <div style="position: relative; height: 200px; width: 100%;">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT ACTIVITY TABLE -->
        <div class="avastra-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="serif-heading fw-bold mb-0">Recent Activity & Audit Trail</h6>
                    <small class="text-muted" style="font-size:0.75rem;">Real-time record of system actions and events</small>
                </div>
                <a href="audit-logs.php" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;">View Full Log</a>
            </div>

            <?php if (empty($recentLogs)): ?>
                <div class="text-center py-3 text-muted small">No audit log entries recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle mb-0" style="font-size:0.825rem;">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Activity</th>
                                <th>Type</th>
                                <th>Date / Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td class="py-2">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($log['user_name'] ?? 'System'); ?></div>
                                    </td>
                                    <td class="py-2"><?= htmlspecialchars($log['details'] ?? $log['action']); ?></td>
                                    <td class="py-2"><span class="badge bg-light text-dark border"><?= htmlspecialchars($log['entity_type'] ?? 'System'); ?></span></td>
                                    <td class="py-2"><small class="text-muted"><?= date('d M Y, h:i A', strtotime($log['created_at'])); ?></small></td>
                                    <td class="py-2"><span class="badge-status active">Logged</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- AVASTRA Chart.js Config -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'];
            const bookingsData = [5, 12, 18, 24, 30, 42, 55, 68];
            const revenueData = [12000, 18000, 25000, 32000, 28000, 45000, 52000, 68000];

            // 1. Overview Chart
            const ctxTrend = document.getElementById('trendChart').getContext('2d');
            const trendChart = new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Total Bookings',
                        data: bookingsData,
                        borderColor: '#1B5E3A',
                        backgroundColor: 'rgba(27, 94, 58, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#1B5E3A'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // Toggle Overview Datasets
            const btnBookings = document.getElementById('btnBookings');
            const btnRevenue = document.getElementById('btnRevenue');

            btnBookings.addEventListener('click', function () {
                btnBookings.classList.add('active');
                btnRevenue.classList.remove('active');
                trendChart.data.datasets[0].label = 'Total Bookings';
                trendChart.data.datasets[0].data = bookingsData;
                trendChart.data.datasets[0].borderColor = '#1B5E3A';
                trendChart.data.datasets[0].backgroundColor = 'rgba(27, 94, 58, 0.08)';
                trendChart.update();
            });

            btnRevenue.addEventListener('click', function () {
                btnRevenue.classList.add('active');
                btnBookings.classList.remove('active');
                trendChart.data.datasets[0].label = 'Platform Revenue (₹)';
                trendChart.data.datasets[0].data = revenueData;
                trendChart.data.datasets[0].borderColor = '#4CAF6D';
                trendChart.data.datasets[0].backgroundColor = 'rgba(76, 175, 109, 0.12)';
                trendChart.update();
            });

            // 2. Bookings by Space Type (Horizontal Bar)
            const ctxSpaceType = document.getElementById('spaceTypeBarChart').getContext('2d');
            new Chart(ctxSpaceType, {
                type: 'bar',
                data: {
                    labels: ['Office', 'Studio', 'Meeting', 'Warehouse', 'Event', 'Workshop', 'Storage'],
                    datasets: [{
                        label: 'Bookings',
                        data: [42, 28, 35, 19, 24, 15, 12],
                        backgroundColor: '#1B5E3A',
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

            // 3. User & Owner Growth Line Chart
            const ctxUserGrowth = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(ctxUserGrowth, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Space Seekers',
                            data: [120, 240, 410, 680, 890, 1150, 1420, 1850],
                            borderColor: '#1B5E3A',
                            fill: false,
                            tension: 0.3
                        },
                        {
                            label: 'Space Owners',
                            data: [35, 62, 98, 140, 195, 260, 310, 390],
                            borderColor: '#4CAF6D',
                            borderDash: [4, 4],
                            fill: false,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
