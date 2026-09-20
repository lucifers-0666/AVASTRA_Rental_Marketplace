<?php

/**
 * AVASTRA — Edit Space Listing
 */
$pageTitle = 'Edit Space';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];
$spaceId = (int) ($_GET['id'] ?? 0);
$errors = [];
$notice = '';

// Check space ownership
$stmt = $db->prepare("SELECT * FROM spaces WHERE id = :id AND owner_id = :uid LIMIT 1");
$stmt->execute([':id' => $spaceId, ':uid' => $userId]);
$space = $stmt->fetch();

if (!$space) {
    $unreadNotifCount = 0;
    ?>
    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <main id="user-content">
            <div class="empty-state">
                <i class="bi bi-building-exclamation"></i>
                <h3>Space not found</h3>
                <p>This space listing does not exist or you do not have permission to edit it.</p>
                <a href="<?= APP_URL; ?>/user/my-spaces.php" class="btn btn-primary-avastra">Back to My Spaces</a>
            </div>
        </main>
        <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php
    exit;
}

$categories = $db->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetchAll();
$amenities  = $db->query('SELECT id, name FROM amenities ORDER BY name')->fetchAll();

// Handle Delete Photo Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_image') {
    $imageId = (int)($_POST['image_id'] ?? 0);
    $imgStmt = $db->prepare("SELECT image_path FROM space_images WHERE id = :id AND space_id = :sid");
    $imgStmt->execute([':id' => $imageId, ':sid' => $spaceId]);
    $img = $imgStmt->fetch();
    if ($img) {
        $fullPath = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        $db->prepare("DELETE FROM space_images WHERE id = :id")->execute([':id' => $imageId]);
        $notice = 'Photo removed successfully.';
    }
}

// Handle Update Listing Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_listing'])) {
    $title       = trim($_POST['title'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $state       = trim($_POST['state'] ?? '');
    $zipCode     = trim($_POST['zip_code'] ?? '');
    $totalSqft   = (int)($_POST['total_sqft'] ?? 0);
    $maxCapacity = (int)($_POST['max_capacity'] ?? 0);
    $dailyRate   = $_POST['daily_rate'] ?? '';
    $weeklyRate  = !empty($_POST['weekly_rate']) ? (float)$_POST['weekly_rate'] : null;
    $monthlyRate = !empty($_POST['monthly_rate']) ? (float)$_POST['monthly_rate'] : null;
    $deposit     = !empty($_POST['security_deposit']) ? (float)$_POST['security_deposit'] : 0.00;
    $isActive    = isset($_POST['is_active']) ? 1 : 0;
    $selectedAmenities = array_unique(array_map('intval', $_POST['amenities'] ?? []));

    if (empty($title) || !$categoryId || empty($description) || empty($address) || empty($city) || empty($state) || empty($zipCode)) {
        $errors[] = 'Please complete all required fields (title, type, description, location details).';
    }
    if ($totalSqft < 1 || $maxCapacity < 1) {
        $errors[] = 'Enter a valid square footage and person capacity.';
    }
    if (!is_numeric($dailyRate) || (float)$dailyRate <= 0) {
        $errors[] = 'Enter a valid daily rental rate.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $upd = $db->prepare("
                UPDATE spaces
                SET category_id = :c,
                    title = :t,
                    description = :d,
                    address = :a,
                    city = :city,
                    state = :st,
                    zip_code = :z,
                    total_sqft = :sq,
                    max_capacity = :cap,
                    daily_rate = :daily,
                    weekly_rate = :week,
                    monthly_rate = :month,
                    security_deposit = :deposit,
                    is_active = :active
                WHERE id = :id AND owner_id = :uid
            ");
            $upd->execute([
                ':c'       => $categoryId,
                ':t'       => $title,
                ':d'       => $description,
                ':a'       => $address,
                ':city'    => $city,
                ':st'      => $state,
                ':z'       => $zipCode,
                ':sq'      => $totalSqft,
                ':cap'     => $maxCapacity,
                ':daily'   => (float)$dailyRate,
                ':week'    => $weeklyRate,
                ':month'   => $monthlyRate,
                ':deposit' => $deposit,
                ':active'  => $isActive,
                ':id'      => $spaceId,
                ':uid'     => $userId
            ]);

            // Update Amenities
            $db->prepare("DELETE FROM space_amenities WHERE space_id = :sid")->execute([':sid' => $spaceId]);
            $insAmenity = $db->prepare("INSERT INTO space_amenities (space_id, amenity_id) VALUES (:sid, :aid)");
            foreach ($selectedAmenities as $aid) {
                if ($aid > 0) {
                    $insAmenity->execute([':sid' => $spaceId, ':aid' => $aid]);
                }
            }

            // Handle New Photo Uploads
            if (!empty($_FILES['photos']['name'][0])) {
                $uploadDir = __DIR__ . '/../assets/uploads/spaces/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $imageInsert = $db->prepare('INSERT INTO space_images (space_id, image_path, is_primary) VALUES (:space, :path, :primary)');

                // Check existing image count to decide is_primary
                $existingCount = (int)$db->query("SELECT COUNT(*) FROM space_images WHERE space_id = {$spaceId}")->fetchColumn();

                foreach ($_FILES['photos']['tmp_name'] as $idx => $tmp) {
                    if (($_FILES['photos']['error'][$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if (($_FILES['photos']['error'][$idx] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $ext = strtolower(pathinfo($_FILES['photos']['name'][$idx], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed, true)) {
                        continue;
                    }
                    $filename = 'space-' . bin2hex(random_bytes(12)) . '.' . $ext;
                    if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                        $isPrimary = ($existingCount === 0 && $idx === 0) ? 1 : 0;
                        $imageInsert->execute([
                            ':space'   => $spaceId,
                            ':path'    => 'assets/uploads/spaces/' . $filename,
                            ':primary' => $isPrimary
                        ]);
                        $existingCount++;
                    }
                }
            }

            $db->commit();
            $notice = 'Space listing updated successfully!';

            // Refresh space data
            $stmt->execute([':id' => $spaceId, ':uid' => $userId]);
            $space = $stmt->fetch();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors[] = 'Failed to update listing: ' . $e->getMessage();
        }
    }
}

// Fetch current amenities and images
$currentAmenityIds = $db->prepare("SELECT amenity_id FROM space_amenities WHERE space_id = :id");
$currentAmenityIds->execute([':id' => $spaceId]);
$currentAmenityIds = $currentAmenityIds->fetchAll(PDO::FETCH_COLUMN);

$spaceImages = $db->prepare("SELECT * FROM space_images WHERE space_id = :id ORDER BY is_primary DESC, id ASC");
$spaceImages->execute([':id' => $spaceId]);
$spaceImages = $spaceImages->fetchAll();

$unreadNotifCount = 0;
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main id="user-content" class="p-4" style="max-width:960px; margin:0 auto;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="<?= APP_URL; ?>/user/my-spaces.php" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left"></i> Back to My Spaces
                </a>
                <h1 style="font-family:'DM Serif Display',serif; font-size:28px; color:var(--avastra-dark); margin:8px 0 0 0;">
                    Edit Listing: <?= htmlspecialchars($space['title']); ?>
                </h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL; ?>/user/space-details.php?id=<?= (int)$space['id']; ?>" class="btn btn-ghost-avastra btn-sm" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> View Public Listing
                </a>
                <a href="<?= APP_URL; ?>/user/space-availability.php?id=<?= (int)$space['id']; ?>" class="btn btn-ghost-avastra btn-sm">
                    <i class="bi bi-calendar-week"></i> Availability
                </a>
            </div>
        </div>

        <?php if ($notice): ?>
            <div class="settings-alert success mb-4"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="settings-alert error mb-4"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars(implode(' ', $errors)); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="update_listing" value="1">

            <!-- 1. GENERAL INFORMATION -->
            <div class="p-4 mb-4 bg-white border rounded-3 shadow-sm">
                <h2 style="font-size:18px; font-weight:600; color:var(--avastra-dark); margin-bottom:16px;">
                    <i class="bi bi-info-circle text-primary me-2"></i> Basic Details
                </h2>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Space Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($space['title']); ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Space Category *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>" <?= (int)$space['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Listing Status</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" <?= $space['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="isActiveSwitch">Active (Listed for search and bookings)</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Description *</label>
                    <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($space['description']); ?></textarea>
                </div>
            </div>

            <!-- 2. LOCATION -->
            <div class="p-4 mb-4 bg-white border rounded-3 shadow-sm">
                <h2 style="font-size:18px; font-weight:600; color:var(--avastra-dark); margin-bottom:16px;">
                    <i class="bi bi-geo-alt text-primary me-2"></i> Location
                </h2>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Street Address *</label>
                    <input type="text" name="address" class="form-control" required value="<?= htmlspecialchars($space['address']); ?>">
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">City *</label>
                        <input type="text" name="city" class="form-control" required value="<?= htmlspecialchars($space['city']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">State *</label>
                        <input type="text" name="state" class="form-control" required value="<?= htmlspecialchars($space['state']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">PIN Code *</label>
                        <input type="text" name="zip_code" class="form-control" required value="<?= htmlspecialchars($space['zip_code']); ?>">
                    </div>
                </div>
            </div>

            <!-- 3. CAPACITY & PRICING -->
            <div class="p-4 mb-4 bg-white border rounded-3 shadow-sm">
                <h2 style="font-size:18px; font-weight:600; color:var(--avastra-dark); margin-bottom:16px;">
                    <i class="bi bi-currency-rupee text-primary me-2"></i> Capacity &amp; Pricing
                </h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Max Capacity (Persons) *</label>
                        <input type="number" name="max_capacity" min="1" class="form-control" required value="<?= (int)$space['max_capacity']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Total Size (Sq. Ft.) *</label>
                        <input type="number" name="total_sqft" min="1" class="form-control" required value="<?= (int)$space['total_sqft']; ?>">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Daily Rental Rate (₹) *</label>
                        <input type="number" name="daily_rate" min="1" step="0.01" class="form-control" required value="<?= (float)$space['daily_rate']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Security Deposit (₹)</label>
                        <input type="number" name="security_deposit" min="0" step="0.01" class="form-control" value="<?= (float)$space['security_deposit']; ?>">
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Weekly Rate (₹) <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="number" name="weekly_rate" min="0" step="0.01" class="form-control" value="<?= $space['weekly_rate'] !== null ? (float)$space['weekly_rate'] : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Monthly Rate (₹) <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="number" name="monthly_rate" min="0" step="0.01" class="form-control" value="<?= $space['monthly_rate'] !== null ? (float)$space['monthly_rate'] : ''; ?>">
                    </div>
                </div>
            </div>

            <!-- 4. AMENITIES -->
            <div class="p-4 mb-4 bg-white border rounded-3 shadow-sm">
                <h2 style="font-size:18px; font-weight:600; color:var(--avastra-dark); margin-bottom:16px;">
                    <i class="bi bi-check2-circle text-primary me-2"></i> Amenities
                </h2>
                <div class="row g-2">
                    <?php foreach ($amenities as $a): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="amenities[]" value="<?= $a['id']; ?>" id="amenity_<?= $a['id']; ?>" <?= in_array((int)$a['id'], $currentAmenityIds, true) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="amenity_<?= $a['id']; ?>">
                                    <?= htmlspecialchars($a['name']); ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 5. PHOTOS -->
            <div class="p-4 mb-4 bg-white border rounded-3 shadow-sm">
                <h2 style="font-size:18px; font-weight:600; color:var(--avastra-dark); margin-bottom:16px;">
                    <i class="bi bi-images text-primary me-2"></i> Listing Photos
                </h2>

                <?php if (!empty($spaceImages)): ?>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <?php foreach ($spaceImages as $img): ?>
                            <div class="position-relative border rounded p-1" style="width:140px; height:110px; background:#fafafa;">
                                <img src="<?= APP_URL . '/' . htmlspecialchars($img['image_path']); ?>" alt="Photo" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">
                                <?php if ($img['is_primary']): ?>
                                    <span class="badge bg-primary position-absolute bottom-0 start-0 m-1" style="font-size:10px;">Primary</span>
                                <?php endif; ?>
                                <button type="submit" form="delForm_<?= $img['id']; ?>" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 rounded-circle" style="width:24px; height:24px; display:flex; align-items:center; justify-content:center;" onclick="return confirm('Remove this photo?');">
                                    <i class="bi bi-x" style="font-size:16px;"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="form-label fw-bold small">Upload Additional Photos</label>
                    <input type="file" name="photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
                    <span class="form-text small">Accepted formats: JPG, PNG, WebP (up to 35 MB each).</span>
                </div>
            </div>

            <!-- SUBMIT BUTTONS -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <a href="<?= APP_URL; ?>/user/my-spaces.php" class="btn btn-ghost-avastra">
                    <i class="bi bi-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary-avastra px-4 py-2">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </form>

        <!-- Hidden photo delete forms -->
        <?php foreach ($spaceImages as $img): ?>
            <form id="delForm_<?= $img['id']; ?>" method="post" class="d-none">
                <input type="hidden" name="action" value="delete_image">
                <input type="hidden" name="image_id" value="<?= (int)$img['id']; ?>">
            </form>
        <?php endforeach; ?>

    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
