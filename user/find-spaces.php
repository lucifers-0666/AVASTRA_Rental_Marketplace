<?php

/**
 * AVASTRA — Find Spaces (renter marketplace, authenticated version)
 */
require_once __DIR__ . '/../classes/Auth.php';
Auth::initSession();
Auth::requireLogin();

$currentUser = Auth::getUser();
$db = Database::getInstance();

/* -----------------------------------------------------------
   READ FILTERS FROM THE URL (GET, so the search is shareable/bookmarkable)
----------------------------------------------------------- */
$search         = trim($_GET['q'] ?? '');
$cityFilter     = trim($_GET['city'] ?? '');
$categoryFilter = (int) ($_GET['category'] ?? 0);
$minBudget      = $_GET['min'] ?? '';
$maxBudget      = $_GET['max'] ?? '';
$sort           = $_GET['sort'] ?? 'best';

/* -----------------------------------------------------------
   BUILD THE QUERY
----------------------------------------------------------- */
/* -----------------------------------------------------------
   HANDLE FAVORITE TOGGLE (AJAX or POST)
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_favorite') {
    $targetSpaceId = (int)($_POST['space_id'] ?? 0);
    $isFav = false;
    if ($targetSpaceId > 0) {
        $chk = $db->prepare("SELECT id FROM favorites WHERE user_id = :uid AND space_id = :sid");
        $chk->execute([':uid' => (int)$currentUser['id'], ':sid' => $targetSpaceId]);
        $exists = $chk->fetch();
        if ($exists) {
            $db->prepare("DELETE FROM favorites WHERE id = :id")->execute([':id' => $exists['id']]);
            $isFav = false;
        } else {
            $db->prepare("INSERT IGNORE INTO favorites (user_id, space_id) VALUES (:uid, :sid)")->execute([':uid' => (int)$currentUser['id'], ':sid' => $targetSpaceId]);
            $isFav = true;
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'is_favorited' => $isFav]);
            exit;
        }
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$where  = ["s.is_active = 1", "s.verification_status = 'approved'", "s.owner_id != :current_user_id"];
$params = [':current_user_id' => (int) $currentUser['id']];

if ($search !== '') {
    $where[]            = "(s.title LIKE :search1 OR s.description LIKE :search2 OR s.city LIKE :search3)";
    $params[':search1']  = "%$search%";
    $params[':search2']  = "%$search%";
    $params[':search3']  = "%$search%";
}
if ($cityFilter !== '') {
    $where[]          = "s.city = :city";
    $params[':city']  = $cityFilter;
}
if ($categoryFilter > 0) {
    $where[]              = "s.category_id = :category";
    $params[':category']  = $categoryFilter;
}
if ($minBudget !== '' && is_numeric($minBudget)) {
    $where[]         = "s.daily_rate >= :minb";
    $params[':minb'] = $minBudget;
}
if ($maxBudget !== '' && is_numeric($maxBudget)) {
    $where[]         = "s.daily_rate <= :maxb";
    $params[':maxb'] = $maxBudget;
}

$orderBy = match ($sort) {
    'price_low'  => 's.daily_rate ASC',
    'price_high' => 's.daily_rate DESC',
    'newest'     => 's.created_at DESC',
    'rating'     => 'avg_rating DESC',
    default      => 's.created_at DESC', // "Best match" needs a submitted requirement to score against — falls back to newest for now
};

$sql = "
    SELECT s.*, c.name AS category_name,
        (SELECT image_path FROM space_images WHERE space_id = s.id AND is_primary = 1 LIMIT 1) AS image_path,
        (SELECT AVG(rating) FROM reviews WHERE space_id = s.id AND is_approved = 1) AS avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE space_id = s.id AND is_approved = 1) AS review_count,
        EXISTS(SELECT 1 FROM favorites WHERE user_id = :fav_uid AND space_id = s.id) AS is_favorited
    FROM spaces s
    JOIN categories c ON s.category_id = c.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $orderBy
";
$params[':fav_uid'] = (int) $currentUser['id'];
$stmt = $db->prepare($sql);
$stmt->execute($params);
$spaces = $stmt->fetchAll();

// Dropdown source data
$cities     = $db->query("SELECT DISTINCT city FROM spaces WHERE is_active = 1 AND verification_status = 'approved' ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);
$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

$unreadNotifCount = 0; // used by topbar.php
$pageTitle = 'Find Spaces';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <!-- Search hero -->
        <div class="find-hero">
            <h1>Find a space</h1>
            <form method="GET" action="">
                <div class="find-search-row">
                    <input type="text" name="q" placeholder="What do you need? (name, type…)" value="<?= htmlspecialchars($search); ?>">

                    <select name="city">
                        <option value="">All cities</option>
                        <?php foreach ($cities as $c): ?>
                            <option value="<?= htmlspecialchars($c); ?>" <?= $cityFilter === $c ? 'selected' : ''; ?>><?= htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="category">
                        <option value="0">All types</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= $categoryFilter === (int) $cat['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort); ?>">
                    <button type="submit">Search</button>
                </div>

                <!-- Budget filter, revealed by the "Filters" button below -->
                <div class="filters-panel" id="filtersPanel" style="display:none;">
                    <div>
                        <label>Min ₹/day</label>
                        <input type="number" name="min" value="<?= htmlspecialchars($minBudget); ?>" placeholder="0">
                    </div>
                    <div>
                        <label>Max ₹/day</label>
                        <input type="number" name="max" value="<?= htmlspecialchars($maxBudget); ?>" placeholder="10000">
                    </div>
                    <button type="submit" class="btn btn-primary-avastra" style="padding:9px 18px;">Apply</button>
                </div>
            </form>
        </div>

        <!-- Results bar -->
        <div class="results-bar">
            <span class="results-count"><?= count($spaces); ?> space<?= count($spaces) === 1 ? '' : 's'; ?> found</span>
            <div class="d-flex align-items-center gap-2">
                <label for="sortSelect" style="font-size:13.5px;color:rgba(23,32,27,0.6);">Sort:</label>
                <select id="sortSelect" class="sort-select" onchange="updateSort(this.value)">
                    <option value="best" <?= $sort === 'best' ? 'selected' : ''; ?>>Recommended</option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                </select>
                <button type="button" class="filters-toggle-btn" onclick="toggleFilters()">
                    <i class="bi bi-sliders"></i> Filters
                </button>
            </div>
        </div>

        <!-- Results grid -->
        <?php if (empty($spaces)): ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h3>No spaces match this search</h3>
                <p>Try a different city, category, or budget range.</p>
                <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Clear filters</a>
            </div>
        <?php else: ?>
            <div class="find-grid">
                <?php foreach ($spaces as $space): ?>
                    <?php
                    $avgRating   = $space['avg_rating'] ? round((float) $space['avg_rating'], 1) : null;
                    $reviewCount = (int) $space['review_count'];
                    $shortLoc    = htmlspecialchars($space['city']);
                    ?>
                    <div class="position-relative" style="display:flex; flex-direction:column;">
                        <button type="button" class="btn-fav-toggle" onclick="toggleFav(event, <?= (int)$space['id']; ?>, this)" title="<?= !empty($space['is_favorited']) ? 'Remove from Saved' : 'Save to Favorites'; ?>" style="position:absolute; top:12px; right:12px; z-index:10; width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,0.92); border:1px solid rgba(0,0,0,0.06); display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(0,0,0,0.12); cursor:pointer;">
                            <i class="bi bi-heart<?= !empty($space['is_favorited']) ? '-fill' : ''; ?>" style="color:<?= !empty($space['is_favorited']) ? '#EF4444' : '#6B7280'; ?>; font-size:15px;"></i>
                        </button>
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

    </div><!-- /#user-content -->

    <script>
        function toggleFav(e, spaceId, btn) {
            e.preventDefault();
            e.stopPropagation();
            const formData = new FormData();
            formData.append('action', 'toggle_favorite');
            formData.append('space_id', spaceId);
            fetch('<?= APP_URL; ?>/user/find-spaces.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const icon = btn.querySelector('i');
                    if (data.is_favorited) {
                        icon.className = 'bi bi-heart-fill';
                        icon.style.color = '#EF4444';
                        btn.title = 'Remove from Saved';
                    } else {
                        icon.className = 'bi bi-heart';
                        icon.style.color = '#6B7280';
                        btn.title = 'Save to Favorites';
                    }
                }
            })
            .catch(err => console.error(err));
        }

        function updateSort(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', value);
            window.location.href = url.toString();
        }

        function toggleFilters() {
            const panel = document.getElementById('filtersPanel');
            panel.style.display = (panel.style.display === 'flex') ? 'none' : 'flex';
        }
        // Auto-open the filter panel if a min/max value is already in the URL
        const params = new URLSearchParams(window.location.search);
        if (params.get('min') || params.get('max')) {
            document.getElementById('filtersPanel').style.display = 'flex';
        }
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
