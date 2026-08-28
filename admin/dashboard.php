<?php
$pageTitle = 'Admin Overview & Analytics';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$adminModel = new Admin();
$stats = $adminModel->getDashboardStats();
$pendingSpaces = $adminModel->getPendingSpaces(5);

// Handle Quick Space Actions (Approve / Reject)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $spaceId = (int) ($_POST['space_id'] ?? 0);
    if ($_POST['action'] === 'approve') {
        if ($adminModel->approveSpace($spaceId, $currentUser['id'])) {
            $message = "Space #{$spaceId} approved successfully!";
        } else {
            $error = "Failed to approve space.";
        }
    } elseif ($_POST['action'] === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? 'Listing specifications incomplete.');
        if ($adminModel->rejectSpace($spaceId, $reason, $currentUser['id'])) {
            $message = "Space #{$spaceId} rejected.";
        } else {
            $error = "Failed to reject space.";
        }
    }
    // Refresh stats
    $stats = $adminModel->getDashboardStats();
    $pendingSpaces = $adminModel->getPendingSpaces(5);
}
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <!-- Page Title & Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">System Overview & Analytics</h3>
                <p class="text-muted small mb-0">Welcome back, <?= htmlspecialchars($currentUser['full_name']); ?>! Here is what is happening across SpaceShare today.</p>
            </div>
            <a href="verify-spaces.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-shield-check me-2"></i> Review Pending Spaces (<?= $stats['pending_spaces']; ?>)
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stat Widgets Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="admin-card mb-0">
                    <div class="stat-widget">
                        <div>
                            <div class="stat-label">Registered Users</div>
                            <div class="stat-value mt-1"><?= number_format($stats['total_users']); ?></div>
                        </div>
                        <div class="stat-icon primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="admin-card mb-0">
                    <div class="stat-widget">
                        <div>
                            <div class="stat-label">Total Spaces Listed</div>
                            <div class="stat-value mt-1"><?= number_format($stats['total_spaces']); ?></div>
                        </div>
                        <div class="stat-icon success">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="admin-card mb-0">
                    <div class="stat-widget">
                        <div>
                            <div class="stat-label">Pending Verification</div>
                            <div class="stat-value mt-1 text-warning"><?= number_format($stats['pending_spaces']); ?></div>
                        </div>
                        <div class="stat-icon warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="admin-card mb-0">
                    <div class="stat-widget">
                        <div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value mt-1 text-purple">₹<?= number_format($stats['total_revenue'], 2); ?></div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts & Activity Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="admin-card mb-0 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Booking & Revenue Trends</h5>
                        <span class="badge bg-light text-dark border">Year 2026</span>
                    </div>
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="admin-card mb-0 h-100">
                    <h5 class="fw-bold mb-3">Space Categories</h5>
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Pending Verifications Table -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-0">Spaces Awaiting Verification</h5>
                    <small class="text-muted">Listings submitted by space owners requiring admin review before public publishing.</small>
                </div>
                <a href="verify-spaces.php" class="btn btn-sm btn-outline-secondary">View All Queue</a>
            </div>

            <?php if (empty($pendingSpaces)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                    No pending space verification requests right now. All caught up!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Space Title</th>
                                <th>Category</th>
                                <th>Owner</th>
                                <th>Location</th>
                                <th>Size & Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingSpaces as $space): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($space['title']); ?></div>
                                        <small class="text-muted">ID: #<?= $space['id']; ?> | Created: <?= date('d M Y', strtotime($space['created_at'])); ?></small>
                                    </td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($space['category_name']); ?></span></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($space['owner_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($space['owner_email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($space['city']); ?>, <?= htmlspecialchars($space['state']); ?></td>
                                    <td>
                                        <div class="fw-bold"><?= $space['total_sqft']; ?> sq.ft</div>
                                        <small class="text-success fw-semibold">₹<?= number_format($space['daily_rate'], 2); ?> / day</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="space_id" value="<?= $space['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success px-3">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="space_id" value="<?= $space['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            </form>
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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Revenue Chart
            const ctx1 = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: [12000, 19000, 25000, 32000, 28000, 45000, 52000, 68000],
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // Category Distribution Chart
            const ctx2 = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Warehouse', 'Office', 'Event Space', 'Workshop', 'Pop-up Shop'],
                    datasets: [{
                        data: [40, 25, 15, 12, 8],
                        backgroundColor: ['#0284c7', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
