<?php
$pageTitle = 'Categories & Amenities';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$adminModel = new Admin();

$message = '';
$error = '';

// Add New Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-building');
    $description = trim($_POST['description'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (!empty($name)) {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (:name, :slug, :desc, :icon)");
            $stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':icon' => $icon]);
            $adminModel->logAction($currentUser['id'], 'ADD_CATEGORY', 'CATEGORY', (int)$db->lastInsertId(), "Added category {$name}");
            $message = "Category '{$name}' created successfully!";
        } catch (Exception $e) {
            $error = "Category creation failed: " . $e->getMessage();
        }
    }
}

// Add New Amenity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_amenity'])) {
    $name = trim($_POST['amenity_name'] ?? '');
    $icon = trim($_POST['amenity_icon'] ?? 'bi-check-circle');

    if (!empty($name)) {
        try {
            $stmt = $db->prepare("INSERT INTO amenities (name, icon) VALUES (:name, :icon)");
            $stmt->execute([':name' => $name, ':icon' => $icon]);
            $adminModel->logAction($currentUser['id'], 'ADD_AMENITY', 'AMENITY', (int)$db->lastInsertId(), "Added amenity {$name}");
            $message = "Amenity '{$name}' created successfully!";
        } catch (Exception $e) {
            $error = "Amenity creation failed: " . $e->getMessage();
        }
    }
}

$categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM spaces WHERE category_id = c.id) AS spaces_count FROM categories c ORDER BY name ASC")->fetchAll();
$amenities = $db->query("SELECT * FROM amenities ORDER BY name ASC")->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Categories & Amenities Management</h3>
                <p class="text-muted small mb-0">Configure space types and searchable property amenities across SpaceShare.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Category
                </button>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAmenityModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Amenity
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Categories Column -->
            <div class="col-lg-7">
                <div class="admin-card">
                    <h5 class="fw-bold mb-3"><i class="bi bi-tags me-2 text-primary"></i> Space Categories</h5>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Category Name</th>
                                    <th>Slug</th>
                                    <th>Listed Spaces</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><i class="bi <?= htmlspecialchars($cat['icon']); ?> fs-4 text-primary"></i></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($cat['name']); ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($cat['description']); ?></small>
                                        </td>
                                        <td><code><?= htmlspecialchars($cat['slug']); ?></code></td>
                                        <td><span class="badge bg-light text-dark border"><?= $cat['spaces_count']; ?> spaces</span></td>
                                        <td>
                                            <span class="badge-status <?= $cat['is_active'] ? 'active' : 'blocked'; ?>">
                                                <?= $cat['is_active'] ? 'Active' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Amenities Column -->
            <div class="col-lg-5">
                <div class="admin-card">
                    <h5 class="fw-bold mb-3"><i class="bi bi-check2-square me-2 text-success"></i> Master Amenities</h5>
                    <div class="list-group list-group-flush">
                        <?php foreach ($amenities as $am): ?>
                            <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= htmlspecialchars($am['icon']); ?> text-success fs-5"></i>
                                    <span class="fw-semibold"><?= htmlspecialchars($am['name']); ?></span>
                                </div>
                                <small class="text-muted">ID: #<?= $am['id']; ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Add Category -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Add Space Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="add_category" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Recording Studio" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Bootstrap Icon Class</label>
                            <input type="text" name="icon" class="form-control" value="bi-building" placeholder="e.g. bi-mic">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Short Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief summary..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Add Amenity -->
    <div class="modal fade" id="addAmenityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Add Amenity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="add_amenity" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amenity Name</label>
                            <input type="text" name="amenity_name" class="form-control" placeholder="e.g. EV Charging Station" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Bootstrap Icon Class</label>
                            <input type="text" name="amenity_icon" class="form-control" value="bi-ev-station" placeholder="e.g. bi-ev-station">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Amenity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
