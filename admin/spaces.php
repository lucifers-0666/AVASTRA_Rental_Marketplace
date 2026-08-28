<?php
$pageTitle = 'Master Space Directory';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$categoryFilter = (int) ($_GET['category'] ?? 0);

$whereClauses = ["1=1"];
$params = [];

if ($categoryFilter > 0) {
    $whereClauses[] = "s.category_id = :cat_id";
    $params[':cat_id'] = $categoryFilter;
}

$sql = "
    SELECT s.*, u.full_name AS owner_name, u.email AS owner_email, c.name AS category_name
    FROM spaces s
    JOIN users u ON s.owner_id = u.id
    JOIN categories c ON s.category_id = c.id
    WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY s.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$spaces = $stmt->fetchAll();

// Fetch categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Master Spaces Directory</h3>
                <p class="text-muted small mb-0">Browse all space listings across categories, verification statuses, and geographic locations.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="admin-card mb-4">
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

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Space Name</th>
                            <th>Category</th>
                            <th>Owner</th>
                            <th>Location</th>
                            <th>Size</th>
                            <th>Daily Rate</th>
                            <th>Status</th>
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
                                <td><strong><?= $s['total_sqft']; ?></strong> sq.ft</td>
                                <td class="text-success fw-bold">₹<?= number_format($s['daily_rate'], 2); ?></td>
                                <td>
                                    <?php if ($s['verification_status'] === 'approved'): ?>
                                        <span class="badge-status approved">Approved</span>
                                    <?php elseif ($s['verification_status'] === 'pending'): ?>
                                        <span class="badge-status pending">Pending</span>
                                    <?php else: ?>
                                        <span class="badge-status rejected">Rejected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
