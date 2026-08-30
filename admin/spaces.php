<?php
$pageTitle = 'Spaces';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$categoryFilter = (int) ($_GET['category'] ?? 0);
$ownerFilter = (int) ($_GET['owner_id'] ?? 0);

$whereClauses = ["1=1"];
$params = [];

if ($categoryFilter > 0) {
    $whereClauses[] = "s.category_id = :cat_id";
    $params[':cat_id'] = $categoryFilter;
}

if ($ownerFilter > 0) {
    $whereClauses[] = "s.owner_id = :owner_id";
    $params[':owner_id'] = $ownerFilter;
}

$sql = "
    SELECT s.*, u.full_name AS owner_name, u.email AS owner_email, c.name AS category_name,
           (SELECT COUNT(*) FROM bookings WHERE space_id = s.id) AS bookings_count
    FROM spaces s
    JOIN users u ON s.owner_id = u.id
    JOIN categories c ON s.category_id = c.id
    WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY s.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$spaces = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color:#0d5c46;">Spaces Management</h3>
                <p class="text-muted small mb-0">Browse all space listings across categories, pricing tiers, and approval statuses.</p>
            </div>
            <a href="verify-spaces.php" class="btn btn-avastra rounded-pill">
                <i class="bi bi-shield-check me-1"></i> Verification Queue
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="avastra-card mb-4">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Filter by Category</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="avastra-card">
            <?php if (empty($spaces)): ?>
                <div class="text-center py-5 text-muted">No spaces found matching criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle">
                        <thead>
                            <tr>
                                <th>Space Title</th>
                                <th>Category</th>
                                <th>Owner</th>
                                <th>Location</th>
                                <th>Size & Rate</th>
                                <th>Bookings</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spaces as $s): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($s['title']); ?></div>
                                        <small class="text-muted">ID: #<?= $s['id']; ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['category_name']); ?></span></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($s['owner_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($s['owner_email']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($s['city']); ?>, <?= htmlspecialchars($s['state']); ?></td>
                                    <td>
                                        <div><strong><?= $s['total_sqft']; ?></strong> sq.ft</div>
                                        <small class="text-success fw-bold">₹<?= number_format($s['daily_rate'], 2); ?> / day</small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $s['bookings_count']; ?> bookings</span></td>
                                    <td>
                                        <?php if ($s['verification_status'] === 'approved'): ?>
                                            <span class="badge-status published"><i class="bi bi-check-circle"></i> Published</span>
                                        <?php elseif ($s['verification_status'] === 'pending'): ?>
                                            <span class="badge-status pending"><i class="bi bi-clock"></i> Pending Review</span>
                                        <?php else: ?>
                                            <span class="badge-status rejected"><i class="bi bi-x-circle"></i> Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="verify-spaces.php" class="btn btn-sm btn-outline-success">Manage</a>
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
