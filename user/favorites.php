<?php

/**
 * AVASTRA — Saved Spaces (Favorites)
 */
$pageTitle = 'Saved Spaces';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_favorite') {
    $favSpaceId = (int)($_POST['space_id'] ?? 0);
    if ($favSpaceId > 0) {
        $delFav = $db->prepare("DELETE FROM favorites WHERE user_id = :uid AND space_id = :sid");
        $delFav->execute([':uid' => $userId, ':sid' => $favSpaceId]);
    }
    header("Location: " . APP_URL . "/user/favorites.php");
    exit;
}

$favSql = "
    SELECT s.*, c.name AS category_name, f.created_at AS saved_at,
        (SELECT image_path FROM space_images WHERE space_id = s.id AND is_primary = 1 LIMIT 1) AS image_path,
        (SELECT AVG(rating) FROM reviews WHERE space_id = s.id AND is_approved = 1) AS avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE space_id = s.id AND is_approved = 1) AS review_count
    FROM favorites f
    JOIN spaces s ON f.space_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE f.user_id = :uid
    ORDER BY f.created_at DESC
";
$stmt = $db->prepare($favSql);
$stmt->execute([':uid' => $userId]);
$savedSpaces = $stmt->fetchAll();

$totalSaved = count($savedSpaces);
$unreadNotifCount = 0;
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main id="user-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 style="font-family:'DM Serif Display',serif; font-size:28px; color:var(--avastra-dark); margin:0;">Saved Spaces</h1>
                <p class="text-secondary mb-0" style="font-size:14px;">Spaces you've bookmarked for future events, storage, or workspaces.</p>
            </div>
            <div>
                <span class="badge" style="background:var(--avastra-light); color:var(--avastra-primary); font-size:14px; font-weight:600; padding:8px 16px; border-radius:20px; border:1px solid rgba(20,92,74,0.15);">
                    <i class="bi bi-heart-fill text-danger"></i> <?= $totalSaved; ?> Saved
                </span>
            </div>
        </div>

        <?php if (empty($savedSpaces)): ?>
            <div class="empty-state">
                <i class="bi bi-heart" style="color:var(--avastra-primary);"></i>
                <h3>No saved spaces yet</h3>
                <p>Browse our marketplace and click the heart icon on any listing to save it here for later.</p>
                <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Explore Spaces</a>
            </div>
        <?php else: ?>
            <div class="find-grid">
                <?php foreach ($savedSpaces as $space): ?>
                    <?php
                    $avgRating   = $space['avg_rating'] ? round((float) $space['avg_rating'], 1) : null;
                    $reviewCount = (int) $space['review_count'];
                    $shortLoc    = htmlspecialchars($space['city']);
                    ?>
                    <div class="position-relative" style="display:flex; flex-direction:column;">
                        <form method="post" style="position:absolute; top:12px; right:12px; z-index:10;">
                            <input type="hidden" name="action" value="remove_favorite">
                            <input type="hidden" name="space_id" value="<?= (int)$space['id']; ?>">
                            <button type="submit" title="Remove from Saved" style="width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,0.92); border:1px solid rgba(0,0,0,0.06); display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(0,0,0,0.12); cursor:pointer;">
                                <i class="bi bi-heart-fill" style="color:#EF4444; font-size:15px;"></i>
                            </button>
                        </form>
                        <a href="<?= APP_URL; ?>/user/space-details.php?id=<?= (int) $space['id']; ?>" class="find-card" style="height:100%;">
                            <?php if ($space['image_path']): ?>
                                <img src="<?= APP_URL . '/' . htmlspecialchars($space['image_path']); ?>" alt="<?= htmlspecialchars($space['title']); ?>">
                            <?php else: ?>
                                <div class="img-fallback"><i class="bi bi-building" style="font-size:30px;color:rgba(23,32,27,0.25);"></i></div>
                            <?php endif; ?>
                            <div class="fc-body">
                                <div class="fc-top">
                                    <h3><?= htmlspecialchars($space['title']); ?></h3>
                                    <?php if ($avgRating): ?>
                                        <span class="fc-rating"><i class="bi bi-star-fill" style="color:var(--accent);"></i> <?= $avgRating; ?> (<?= $reviewCount; ?>)</span>
                                    <?php else: ?>
                                        <span class="fc-rating" style="color:rgba(23,32,27,0.4);font-weight:500;">New</span>
                                    <?php endif; ?>
                                </div>
                                <div class="fc-loc"><i class="bi bi-geo-alt"></i> <?= $shortLoc; ?></div>
                                <div class="fc-meta">
                                    <span class="fc-tag"><?= htmlspecialchars($space['category_name']); ?></span>
                                    <span><i class="bi bi-people"></i> Up to <?= (int) $space['max_capacity']; ?></span>
                                </div>
                                <div class="fc-bottom">
                                    <span class="fc-price">₹<?= number_format((float) $space['daily_rate'], 0); ?> <span>/ day</span></span>
                                    <span class="fc-view">View Space <i class="bi bi-arrow-right"></i></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
