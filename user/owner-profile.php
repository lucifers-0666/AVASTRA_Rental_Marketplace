<?php

/**
 * AVASTRA — Space Owner Public Profile
 */
$pageTitle = 'Owner Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db      = Database::getInstance();
$ownerId = (int) ($_GET['id'] ?? 0);

// Fetch owner details
$ownerStmt = $db->prepare("SELECT id, full_name, email_verified, city, state, created_at FROM users WHERE id = :id");
$ownerStmt->execute([':id' => $ownerId]);
$owner = $ownerStmt->fetch();

if (!$owner) {
?>
    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <div id="user-content" class="p-4">
            <div class="text-center py-5">
                <i class="bi bi-person-x fs-1 text-muted"></i>
                <h2 class="h4 mt-3" style="font-family:'DM Serif Display', serif;">Owner Not Found</h2>
                <p class="text-muted">The requested profile does not exist or has been removed.</p>
                <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn text-white px-4 py-2" style="background:var(--avastra-primary); border-radius:8px;">Back to Spaces</a>
            </div>
        </div>
    </div>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch owner initials
$nameParts = preg_split('/\s+/', trim($owner['full_name']));
$ownerInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
if (count($nameParts) === 1) {
    $ownerInitials = strtoupper(substr($nameParts[0], 0, 2));
}

// Fetch active approved spaces listed by this owner
$spacesStmt = $db->prepare("
    SELECT s.*, c.name AS category_name,
           (SELECT image_path FROM space_images WHERE space_id = s.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image,
           (SELECT AVG(rating) FROM reviews WHERE space_id = s.id AND is_approved = 1) AS avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE space_id = s.id AND is_approved = 1) AS review_count
    FROM spaces s
    JOIN categories c ON s.category_id = c.id
    WHERE s.owner_id = :owner_id AND s.is_active = 1 AND s.verification_status = 'approved'
    ORDER BY s.created_at DESC
");
$spacesStmt->execute([':owner_id' => $ownerId]);
$ownerSpaces = $spacesStmt->fetchAll();
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content" class="p-4">
        <a href="javascript:history.back()" class="text-decoration-none text-muted mb-3 d-inline-flex align-items-center gap-1 small fw-medium">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <!-- Owner Header Card -->
        <div class="card border-0 shadow-sm p-4 rounded-3 mb-4" style="background:#ffffff;">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="d-flex align-items-center justify-content-center text-white font-serif fw-bold rounded-circle" style="width:72px; height:72px; background:var(--avastra-primary); font-size:26px;">
                    <?= htmlspecialchars($ownerInitials); ?>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h1 class="h3 mb-0" style="font-family:'DM Serif Display', serif; color:var(--avastra-primary);">
                            <?= htmlspecialchars($owner['full_name']); ?>
                        </h1>
                        <?php if ($owner['email_verified']): ?>
                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill small">
                                <i class="bi bi-patch-check-fill me-1"></i> Verified Owner
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small d-flex align-items-center gap-3 flex-wrap">
                        <?php if ($owner['city']): ?>
                            <span><i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($owner['city'] . ($owner['state'] ? ', ' . $owner['state'] : '')); ?></span>
                        <?php endif; ?>
                        <span><i class="bi bi-calendar3 me-1"></i> Member since <?= date('F Y', strtotime($owner['created_at'])); ?></span>
                        <span><i class="bi bi-building me-1"></i> <?= count($ownerSpaces); ?> Space Listing<?= count($ownerSpaces) === 1 ? '' : 's'; ?></span>
                    </div>
                </div>
                <?php if ((int) $owner['id'] !== (int) $currentUser['id']): ?>
                    <div>
                        <a href="<?= APP_URL; ?>/user/messages.php?with=<?= (int) $owner['id']; ?>" class="btn btn-outline-success px-3 py-2 text-decoration-none rounded-2">
                            <i class="bi bi-chat-dots me-1"></i> Contact Owner
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Listed Spaces Section -->
        <h2 class="h5 mb-3" style="font-family:'DM Serif Display', serif; color:var(--avastra-primary);">Listed Spaces</h2>

        <?php if (empty($ownerSpaces)): ?>
            <div class="card border-0 shadow-sm p-5 text-center rounded-3 bg-white">
                <i class="bi bi-building-dash fs-1 text-muted mb-2"></i>
                <p class="text-muted mb-0">This owner currently has no active space listings available.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($ownerSpaces as $space): ?>
                    <?php
                    $img = $space['primary_image'] ? APP_URL . '/' . htmlspecialchars($space['primary_image']) : null;
                    $avgRating = $space['avg_rating'] ? round((float) $space['avg_rating'], 1) : null;
                    $reviewCount = (int) $space['review_count'];
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden d-flex flex-column bg-white">
                            <div class="position-relative" style="height: 180px; background: #e9ecef;">
                                <?php if ($img): ?>
                                    <img src="<?= $img; ?>" alt="<?= htmlspecialchars($space['title']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-secondary">
                                        <i class="bi bi-building fs-1"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="badge bg-dark bg-opacity-75 position-absolute top-0 end-0 m-2 px-2 py-1 small">
                                    <?= htmlspecialchars($space['category_name']); ?>
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column p-3">
                                <h3 class="h6 card-title mb-1 text-truncate" style="font-family:'DM Serif Display', serif; color:var(--avastra-primary);">
                                    <a href="<?= APP_URL; ?>/user/space-details.php?id=<?= $space['id']; ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($space['title']); ?>
                                    </a>
                                </h3>
                                <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($space['city']); ?></p>

                                <div class="mt-auto d-flex align-items-center justify-content-between pt-2 border-top">
                                    <div>
                                        <span class="fw-bold text-dark font-mono">₹<?= number_format((float)$space['daily_rate'], 0); ?></span>
                                        <span class="text-muted small">/ day</span>
                                    </div>
                                    <div>
                                        <?php if ($avgRating): ?>
                                            <span class="small text-warning fw-semibold"><i class="bi bi-star-fill"></i> <?= $avgRating; ?></span>
                                        <?php endif; ?>
                                        <a href="<?= APP_URL; ?>/user/space-details.php?id=<?= $space['id']; ?>" class="btn btn-sm text-white ms-2" style="background:var(--avastra-primary); border-radius:6px;">View</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
