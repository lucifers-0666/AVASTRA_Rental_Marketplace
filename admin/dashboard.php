<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$adminModel = new Admin();
$kpis = $adminModel->get6KPICards();
$attentionItems = $adminModel->getNeedsAttentionQueue();
$categoryAnalytics = $adminModel->getCategoryAnalytics();
$pendingSpaces = $adminModel->getPendingSpaces(5);

$db = Database::getInstance();
$recentLogs = $db->query("
    SELECT a.*, u.full_name AS user_name 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 6
")->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color:#0d5c46;">Dashboard</h3>
                <p class="text-muted small mb-0">Monitor AVASTRA activity, marketplace health, and pending actions.</p>
            </div>
            <a href="verify-spaces.php" class="btn btn-avastra rounded-pill px-4">
                <i class="bi bi-shield-check me-1"></i> Verification Queue (<?= $kpis['pending_verifications']; ?>)
            </a>
        </div>

        <!-- 6 KPI CARDS (Real Database Values) -->
        <div class="row g-3 mb-4">
            <!-- 1. Total Users -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="avastra-card mb-0">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Total Users</div>
                            <div class="kpi-value"><?= number_format($kpis['total_users']); ?></div>
                            <div class="kpi-subtext">Registered</div>
                        </div>
                        <div class="kpi-icon blue"><i class="bi bi-people-fill"></i></div>
                    </div>
                </div>
            </div>

            <!-- 2. Active Owners -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="avastra-card mb-0">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Active Owners</div>
                            <div class="kpi-value"><?= number_format($kpis['active_owners']); ?></div>
                            <div class="kpi-subtext">Space Owners</div>
                        </div>
                        <div class="kpi-icon emerald"><i class="bi bi-person-badge-fill"></i></div>
                    </div>
                </div>
            </div>

            <!-- 3. Active Spaces -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="avastra-card mb-0">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Active Spaces</div>
                            <div class="kpi-value"><?= number_format($kpis['active_spaces']); ?></div>
                            <div class="kpi-subtext">Verified</div>
                        </div>
                        <div class="kpi-icon emerald"><i class="bi bi-building-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- 4. Total Bookings -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="avastra-card mb-0">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Total Bookings</div>
                            <div class="kpi-value"><?= number_format($kpis['total_bookings']); ?></div>
                            <div class="kpi-subtext">Reservations</div>
                        </div>
                        <div class="kpi-icon purple"><i class="bi bi-calendar-check-fill"></i></div>
                    </div>
                </div>
            </div>

            <!-- 5. Revenue -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="avastra-card mb-0">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Revenue</div>
                            <div class="kpi-value" style="font-size:1.4rem;">₹<?= number_format($kpis['total_revenue'], 0); ?></div>
                            <div class="kpi-subtext">Gross Marketplace</div>
                        </div>
                        <div class="kpi-icon rose"><i class="bi bi-currency-rupee"></i></div>
                    </div>
                </div>
            </div>

            <!-- 6. Pending Verification -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="avastra-card mb-0">
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-label">Pending Verif.</div>
                            <div class="kpi-value text-amber"><?= number_format($kpis['pending_verifications']); ?></div>
                            <div class="kpi-subtext text-warning">Requires Review</div>
                        </div>
                        <div class="kpi-icon amber"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Needs Your Attention -->
        <div class="avastra-card mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill text-warning"></i> Needs Your Attention
            </h5>

            <?php if (empty($attentionItems)): ?>
                <div class="p-3 text-center text-muted small bg-light rounded">
                    <i class="bi bi-check-circle-fill text-success me-1"></i> No urgent admin actions required right now. All queues clean!
                </div>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($attentionItems as $item): ?>
                        <div class="col-md-4">
                            <div class="attention-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="attention-icon <?= ($item['priority'] === 'High') ? 'bg-danger text-white' : 'bg-warning text-dark'; ?>">
                                        <i class="bi <?= $item['icon']; ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small mb-1"><?= htmlspecialchars($item['title']); ?></div>
                                        <span class="badge bg-light text-dark border me-1"><?= $item['module']; ?></span>
                                        <span class="badge <?= ($item['priority'] === 'High') ? 'bg-danger' : 'bg-warning text-dark'; ?>"><?= $item['priority']; ?></span>
                                    </div>
                                </div>
                                <a href="<?= $item['action_link']; ?>" class="btn btn-sm btn-outline-success fw-bold ms-2" style="white-space:nowrap;">
                                    <?= $item['action_label']; ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Bookings / Revenue Trend Chart -->
            <div class="col-lg-8">
                <div class="avastra-card mb-0 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-0">Marketplace Activity & Trends</h5>
                            <small class="text-muted">Visual breakdown of booking activity and platform revenue</small>
                        </div>
                        <ul class="nav nav-pills nav-pills-sm" id="chartTabs">
                            <li class="nav-item">
                                <button class="nav-link active small py-1 px-3" data-bs-toggle="tab" data-bs-target="#bookingsTab">Bookings</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link small py-1 px-3" data-bs-toggle="tab" data-bs-target="#revenueTab">Revenue</button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="bookingsTab">
                            <canvas id="bookingsTrendChart" height="230"></canvas>
                        </div>
                        <div class="tab-pane fade" id="revenueTab">
                            <canvas id="revenueTrendChart" height="230"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Space Categories Analytics -->
            <div class="col-lg-4">
                <div class="avastra-card mb-0 h-100">
                    <h5 class="fw-bold mb-1">Space Categories</h5>
                    <small class="text-muted d-block mb-3">Distribution of spaces listed by category</small>
                    <canvas id="categoriesDoughnutChart" height="230"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Audit Log Activity Table -->
        <div class="avastra-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">Recent Activity & Audit Trail</h5>
                    <small class="text-muted">Real-time record of system actions and administrator events</small>
                </div>
                <a href="audit-logs.php" class="btn btn-sm btn-outline-secondary">View Full Log</a>
            </div>

            <?php if (empty($recentLogs)): ?>
                <div class="text-center py-4 text-muted small">No audit log entries recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action Event</th>
                                <th>Entity</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($log['user_name'] ?? 'System'); ?></div>
                                    </td>
                                    <td><span class="badge bg-dark text-white"><?= htmlspecialchars($log['action']); ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($log['entity_type'] ?? 'N/A'); ?> <?= $log['entity_id'] ? '#' . $log['entity_id'] : ''; ?></span></td>
                                    <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></code></td>
                                    <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($log['created_at'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Chart.js Config -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Bookings Trend Chart
            const ctx1 = document.getElementById('bookingsTrendChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                    datasets: [{
                        label: 'Total Bookings',
                        data: [5, 12, 18, 24, 30, 42, 55, 68],
                        borderColor: '#0d5c46',
                        backgroundColor: 'rgba(13, 92, 70, 0.08)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Revenue Trend Chart
            const ctx2 = document.getElementById('revenueTrendChart').getContext('2d');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                    datasets: [{
                        label: 'Platform Revenue (₹)',
                        data: [12000, 18000, 25000, 32000, 28000, 45000, 52000, 68000],
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Category Doughnut Chart
            const ctx3 = document.getElementById('categoriesDoughnutChart').getContext('2d');
            new Chart(ctx3, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php foreach ($categoryAnalytics as $cat) { echo "'" . addslashes($cat['name']) . "',"; } ?>
                    ],
                    datasets: [{
                        data: [
                            <?php foreach ($categoryAnalytics as $cat) { echo $cat['total_spaces'] . ","; } ?>
                        ],
                        backgroundColor: ['#0d5c46', '#10b981', '#0284c7', '#f59e0b', '#8b5cf6', '#ec4899']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
