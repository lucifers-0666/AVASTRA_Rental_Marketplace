<?php
$pageTitle = 'Spaces';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$categoryFilter = $_GET['category'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

$whereClauses = ["1=1"];
$params = [];

if ($categoryFilter !== 'all') {
    $whereClauses[] = "s.category_id = :category_id";
    $params[':category_id'] = (int) $categoryFilter;
}

if ($statusFilter !== 'all') {
    $whereClauses[] = "s.verification_status = :status";
    $params[':status'] = $statusFilter;
}

$sql = "
    SELECT s.*, c.name AS category_name, u.full_name AS owner_name, u.email AS owner_email,
           (SELECT image_url FROM space_images WHERE space_id = s.id LIMIT 1) AS primary_image
    FROM spaces s
    JOIN categories c ON s.category_id = c.id
    JOIN users u ON s.owner_id = u.id
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

    <main class="p-3 p-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <p class="text-muted small mb-0">Master inventory of listed space properties, pricing rules, categories, and publication statuses.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-avastra btn-sm rounded-pill px-3 align-self-start align-self-sm-center">
                <i class="bi bi-download me-1"></i> Export Directory
            </button>
        </div>

        <!-- Filter Card -->
        <div class="avastra-card p-3 mb-3">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-5">
                    <select name="category" class="form-select form-select-sm">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= ($categoryFilter == $cat['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : ''; ?>>All Verification Statuses</option>
                        <option value="approved" <?= ($statusFilter === 'approved') ? 'selected' : ''; ?>>Approved / Published</option>
                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-avastra btn-sm"><i class="bi bi-funnel me-1"></i> Filter Spaces</button>
                </div>
            </form>
        </div>

        <div class="avastra-card p-3">
            <?php if (empty($spaces)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-building-slash fs-1 text-secondary mb-2 d-block"></i>
                    No space listings found for the selected criteria.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-avastra align-middle mb-0" style="font-size:0.825rem;">
                        <thead>
                            <tr>
                                <th><i class="bi bi-building me-1 text-muted"></i> Space Title</th>
                                <th><i class="bi bi-tags me-1 text-muted"></i> Category</th>
                                <th><i class="bi bi-person me-1 text-muted"></i> Owner</th>
                                <th><i class="bi bi-geo-alt me-1 text-muted"></i> Location</th>
                                <th><i class="bi bi-currency-rupee me-1 text-muted"></i> Hourly Rate</th>
                                <th><i class="bi bi-info-circle me-1 text-muted"></i> Status</th>
                                <th><i class="bi bi-clock me-1 text-muted"></i> Created</th>
                                <th><i class="bi bi-three-dots me-1 text-muted"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spaces as $s): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center text-secondary border" style="width:36px; height:36px; overflow:hidden; flex-shrink:0;">
                                                <?php if ($s['primary_image']): ?>
                                                    <img src="<?= APP_URL; ?>/<?= htmlspecialchars($s['primary_image']); ?>" style="width:100%; height:100%; object-fit:cover;" alt="Thumbnail">
                                                <?php else: ?>
                                                    <i class="bi bi-building"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['title']); ?></div>
                                                <small class="text-muted font-mono">ID: #<?= $s['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['category_name']); ?></span></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($s['owner_name']); ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($s['owner_email']); ?></small>
                                    </td>
                                    <td><small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['city']); ?></small></td>
                                    <td class="fw-bold text-success">₹<?= number_format($s['price_per_hour'], 2); ?>/hr</td>
                                    <td>
                                        <?php if ($s['verification_status'] === 'approved'): ?>
                                            <span class="badge-status approved"><i class="bi bi-check-circle-fill"></i> Published</span>
                                        <?php elseif ($s['verification_status'] === 'pending'): ?>
                                            <span class="badge-status pending"><i class="bi bi-clock"></i> Pending Review</span>
                                        <?php else: ?>
                                            <span class="badge-status rejected"><i class="bi bi-x-circle-fill"></i> Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($s['created_at'])); ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#spaceModal<?= $s['id']; ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>

                                        <!-- Space Detail Modal -->
                                        <div class="modal fade" id="spaceModal<?= $s['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold"><i class="bi bi-building me-2 text-success"></i> Space Details #<?= $s['id']; ?></h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="fw-bold fs-6 mb-1"><?= htmlspecialchars($s['title']); ?></div>
                                                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['address']); ?>, <?= htmlspecialchars($s['city']); ?></p>
                                                        <div class="p-2 bg-light rounded border small mb-3"><?= nl2br(htmlspecialchars($s['description'])); ?></div>
                                                        <div class="row g-2 border-top pt-2 small">
                                                            <div class="col-6"><strong>Category:</strong> <?= htmlspecialchars($s['category_name']); ?></div>
                                                            <div class="col-6"><strong>Hourly Rate:</strong> ₹<?= number_format($s['price_per_hour'], 2); ?></div>
                                                            <div class="col-6"><strong>Daily Rate:</strong> ₹<?= number_format($s['price_per_day'], 2); ?></div>
                                                            <div class="col-6"><strong>Owner:</strong> <?= htmlspecialchars($s['owner_name']); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
