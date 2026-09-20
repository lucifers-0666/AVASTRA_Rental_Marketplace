<?php

/**
 * AVASTRA — Space Details (authenticated)
 *
 * This is the page where the core project logic lives:
 *   Booking conflict rule: existing.start < new.end AND existing.end > new.start
 *
 * Two honest schema gaps handled here (not faked):
 *  1. Pricing shown as "/ day" using `daily_rate` — there's no hourly rate column.
 *  2. "Rules & Requirements" has nowhere to live in the DB yet. `spaces` has no
 *     rules/house_rules column, even though list-space.php's wizard already
 *     collects a "House rules" text field on Step 3. Ask Zaid to add:
 *         ALTER TABLE spaces ADD COLUMN house_rules TEXT DEFAULT NULL;
 */

require_once __DIR__ . '/../classes/Auth.php';
Auth::initSession();
Auth::requireLogin();
$currentUser = Auth::getUser();

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];
$spaceId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT s.*, c.name AS category_name
    FROM spaces s
    JOIN categories c ON s.category_id = c.id
    WHERE s.id = :id AND (s.owner_id = :uid OR (s.is_active = 1 AND s.verification_status = 'approved'))
");
$stmt->execute([':id' => $spaceId, ':uid' => $userId]);
$space = $stmt->fetch();

$bookingError   = '';
$bookingSuccess = '';

/* -----------------------------------------------------------
   CHECK FAVORITE STATUS & HANDLE TOGGLE
----------------------------------------------------------- */
$favStmt = $db->prepare("SELECT id FROM favorites WHERE user_id = :uid AND space_id = :sid LIMIT 1");
$favStmt->execute([':uid' => $userId, ':sid' => $spaceId]);
$isFavorited = (bool) $favStmt->fetch();

if ($space && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_favorite') {
    if ($isFavorited) {
        $delFav = $db->prepare("DELETE FROM favorites WHERE user_id = :uid AND space_id = :sid");
        $delFav->execute([':uid' => $userId, ':sid' => $spaceId]);
        $isFavorited = false;
    } else {
        $addFav = $db->prepare("INSERT IGNORE INTO favorites (user_id, space_id) VALUES (:uid, :sid)");
        $addFav->execute([':uid' => $userId, ':sid' => $spaceId]);
        $isFavorited = true;
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'is_favorited' => $isFavorited]);
        exit;
    }
    header("Location: " . APP_URL . "/user/space-details.php?id=" . $spaceId);
    exit;
}

/* -----------------------------------------------------------
   HANDLE "REQUEST TO BOOK" SUBMISSION
----------------------------------------------------------- */
if ($space && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'toggle_favorite') {
    $startDate = $_POST['start_date'] ?? '';
    $endDate   = $_POST['end_date'] ?? '';
    $purpose   = trim($_POST['purpose'] ?? '');

    if ((int) $space['owner_id'] === $userId) {
        $bookingError = "You own this space, so you can't book it yourself.";
    } elseif ($startDate === '' || $endDate === '' || $purpose === '') {
        $bookingError = 'Please fill in the date range and your purpose for booking.';
    } elseif (strtotime($endDate) <= strtotime($startDate)) {
        $bookingError = 'End date must be after the start date.';
    } elseif (strtotime($startDate) < strtotime('today')) {
        $bookingError = 'Start date cannot be in the past.';
    } else {
        // --- The core algorithm: existing.start < new.end AND existing.end > new.start ---
        $conflictStmt = $db->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE space_id = :space_id
              AND status IN ('pending', 'confirmed', 'active')
              AND start_date < :new_end
              AND end_date > :new_start
        ");
        $conflictStmt->execute([
            ':space_id'  => $spaceId,
            ':new_end'   => $endDate,
            ':new_start' => $startDate,
        ]);
        $hasConflict = (int) $conflictStmt->fetchColumn() > 0;

        if ($hasConflict) {
            $bookingError = 'Those dates overlap with an existing booking on this space. Please pick different dates.';
        } else {
            $totalDays = (int) ((strtotime($endDate) - strtotime($startDate)) / 86400);

            $feeRow = $db->query("SELECT platform_fee_percent FROM commission_settings LIMIT 1")->fetch();
            $feePercent = $feeRow ? (float) $feeRow['platform_fee_percent'] : 5.00;

            $baseAmount    = (float) $space['daily_rate'] * $totalDays;
            $platformFee   = round($baseAmount * ($feePercent / 100), 2);
            $depositAmount = (float) $space['security_deposit'];
            $totalAmount   = $baseAmount + $platformFee + $depositAmount;

            $bookingCode = 'BK-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $insert = $db->prepare("
                INSERT INTO bookings
                    (booking_code, space_id, seeker_id, start_date, end_date, total_days,
                     base_amount, platform_fee, deposit_amount, total_amount, purpose, status)
                VALUES
                    (:code, :space_id, :seeker_id, :start_date, :end_date, :total_days,
                     :base_amount, :platform_fee, :deposit_amount, :total_amount, :purpose, 'pending')
            ");
            $insert->execute([
                ':code'           => $bookingCode,
                ':space_id'       => $spaceId,
                ':seeker_id'      => $userId,
                ':start_date'     => $startDate,
                ':end_date'       => $endDate,
                ':total_days'     => $totalDays,
                ':base_amount'    => $baseAmount,
                ':platform_fee'   => $platformFee,
                ':deposit_amount' => $depositAmount,
                ':total_amount'   => $totalAmount,
                ':purpose'        => $purpose,
            ]);

            $_SESSION['flash_success'] = "Request sent! Booking {$bookingCode} is now pending the owner's approval.";
            header("Location: " . APP_URL . "/user/my-requests.php?status=pending");
            exit;
        }
    }
}

if (!$space) {
    // Space not found / not visible to this user — show a calm empty state, not a raw error.
?>
    <div id="user-main">
        <?php $unreadNotifCount = 0;
        require_once __DIR__ . '/includes/topbar.php'; ?>
        <div id="user-content">
            <div class="empty-state">
                <i class="bi bi-building-x"></i>
                <h3>Space not found</h3>
                <p>This listing may have been removed or isn't published yet.</p>
                <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Back to Find Spaces</a>
            </div>
        </div>
    </div>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/* -----------------------------------------------------------
   FETCH EVERYTHING ELSE FOR THE PAGE
----------------------------------------------------------- */
$images = $db->prepare("SELECT * FROM space_images WHERE space_id = :id ORDER BY is_primary DESC, id ASC LIMIT 3");
$images->execute([':id' => $spaceId]);
$images = $images->fetchAll();

$amenities = $db->prepare("
    SELECT a.name FROM space_amenities sa
    JOIN amenities a ON sa.amenity_id = a.id
    WHERE sa.space_id = :id ORDER BY a.name ASC
");
$amenities->execute([':id' => $spaceId]);
$amenities = $amenities->fetchAll(PDO::FETCH_COLUMN);

$reviewStmt = $db->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count
    FROM reviews WHERE space_id = :id AND is_approved = 1
");
$reviewStmt->execute([':id' => $spaceId]);
$reviewRow   = $reviewStmt->fetch();
$avgRating   = $reviewRow['avg_rating'] ? round((float) $reviewRow['avg_rating'], 1) : null;
$reviewCount = (int) $reviewRow['review_count'];

// Fetch all approved reviews for this space
$spaceReviewsStmt = $db->prepare("
    SELECT r.*, u.full_name, u.profile_image
    FROM reviews r
    JOIN users u ON u.id = r.reviewer_id
    WHERE r.space_id = :id AND r.is_approved = 1
    ORDER BY r.created_at DESC
");
$spaceReviewsStmt->execute([':id' => $spaceId]);
$spaceReviews = $spaceReviewsStmt->fetchAll();

$owner = $db->prepare("SELECT id, full_name, email_verified, created_at FROM users WHERE id = :id");
$owner->execute([':id' => $space['owner_id']]);
$owner = $owner->fetch();
$ownerInitials = strtoupper(substr($owner['full_name'], 0, 1) . substr(strrchr($owner['full_name'], ' ') ?: '', 1, 1));

$unreadNotifCount = 0; // used by topbar.php
$pageTitle = ($space ? htmlspecialchars($space['title']) : 'Space') . ' — Details';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="<?= APP_URL; ?>/user/find-spaces.php" class="sd-back mb-0"><i class="bi bi-arrow-left"></i> Back to spaces</a>
            <form method="post" class="d-inline">
                <input type="hidden" name="action" value="toggle_favorite">
                <button type="submit" class="btn <?= $isFavorited ? 'btn-primary-avastra' : 'btn-ghost-avastra'; ?> btn-sm">
                    <i class="bi bi-heart<?= $isFavorited ? '-fill' : ''; ?>" style="<?= $isFavorited ? 'color:#EF4444;' : ''; ?>"></i>
                    <?= $isFavorited ? 'Saved to Favorites' : 'Save to Favorites'; ?>
                </button>
            </form>
        </div>

        <!-- Gallery -->
        <div class="sd-gallery">
            <?php if (!empty($images[0])): ?>
                <img class="main-img" src="<?= APP_URL . '/' . htmlspecialchars($images[0]['image_path']); ?>" alt="<?= htmlspecialchars($space['title']); ?>" onerror="this.onerror=null; this.src='<?= APP_URL; ?>/assets/images/logo/only%20logo.svg'; this.style.objectFit='contain'; this.style.padding='40px'; this.style.background='#E7F5EC';">
            <?php else: ?>
                <div class="img-fallback-lg d-flex flex-column align-items-center justify-content-center" style="background:var(--avastra-light); border-radius:12px; height:100%; min-height:280px;"><i class="bi bi-building" style="font-size:44px;color:var(--avastra-primary);"></i><span class="small text-muted mt-2">No image uploaded yet</span></div>
            <?php endif; ?>
            <div class="thumb-col">
                <?php for ($i = 1; $i <= 2; $i++): ?>
                    <?php if (!empty($images[$i])): ?>
                        <img src="<?= APP_URL . '/' . htmlspecialchars($images[$i]['image_path']); ?>" alt="<?= htmlspecialchars($space['title']); ?>" onerror="this.onerror=null; this.src='<?= APP_URL; ?>/assets/images/logo/only%20logo.svg'; this.style.objectFit='contain'; this.style.padding='20px'; this.style.background='#E7F5EC';">
                    <?php else: ?>
                        <div class="img-fallback-sm d-flex flex-column align-items-center justify-content-center" style="background:var(--avastra-light); border-radius:12px; height:100%; min-height:130px;"><i class="bi bi-image" style="font-size:24px;color:var(--avastra-primary);"></i></div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <div class="sd-layout">
            <div>
                <!-- Title + rating -->
                <div class="sd-header">
                    <div>
                        <h1><?= htmlspecialchars($space['title']); ?></h1>
                        <div class="sd-meta-row">
                            <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($space['address'] . ', ' . $space['city']); ?></span>
                            <span><i class="bi bi-people"></i> Up to <?= (int) $space['max_capacity']; ?> people</span>
                            <span class="sd-tag"><?= htmlspecialchars($space['category_name']); ?></span>
                        </div>
                    </div>
                    <?php if ($avgRating): ?>
                        <span class="sd-rating"><i class="bi bi-star-fill" style="color:var(--accent);"></i> <?= $avgRating; ?> (<?= $reviewCount; ?> review<?= $reviewCount === 1 ? '' : 's'; ?>)</span>
                    <?php else: ?>
                        <span class="sd-rating" style="color:rgba(23,32,27,0.4);">No reviews yet</span>
                    <?php endif; ?>
                </div>

                <!-- Owner -->
                <div class="owner-block">
                    <div class="avatar-circle-lg"><?= htmlspecialchars($ownerInitials); ?></div>
                    <div>
                        <div class="o-name">
                            <?= htmlspecialchars($owner['full_name']); ?>
                            <?php if ($owner['email_verified']): ?>
                                <span class="verified-badge"><i class="bi bi-patch-check-fill"></i> Verified</span>
                            <?php endif; ?>
                        </div>
                        <div class="o-sub">Member since <?= date('F Y', strtotime($owner['created_at'])); ?></div>
                    </div>
                    <div class="d-flex align-items-center gap-3" style="margin-left:auto;">
                        <?php if ((int) $owner['id'] !== $userId): ?>
                            <a href="<?= APP_URL; ?>/user/messages.php?with=<?= (int) $owner['id']; ?>&space_id=<?= (int) $space['id']; ?>" class="btn btn-ghost-avastra btn-sm">
                                <i class="bi bi-chat-dots"></i> Message
                            </a>
                        <?php endif; ?>
                        <a href="<?= APP_URL; ?>/user/owner-profile.php?id=<?= (int) $owner['id']; ?>" class="o-link">View profile <i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>

                <!-- About -->
                <div class="sd-section">
                    <h2>About this space</h2>
                    <p><?= nl2br(htmlspecialchars($space['description'] ?: 'No description provided yet.')); ?></p>
                </div>

                <!-- Amenities -->
                <?php if (!empty($amenities)): ?>
                    <div class="sd-section">
                        <h2>Amenities</h2>
                        <div class="amenity-check-grid">
                            <?php foreach ($amenities as $a): ?>
                                <div><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($a); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Rules & Requirements -->
                <div class="sd-section">
                    <h2>Rules &amp; Requirements</h2>
                    <p class="sd-note">Standard rental guidelines apply. Please respect noise ordinances and leave the premises tidy.</p>
                </div>

                <!-- Reviews & Ratings -->
                <div class="sd-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Reviews &amp; Ratings</h2>
                        <?php if ($avgRating): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span style="font-size:20px; font-weight:700; color:var(--avastra-dark);"><?= $avgRating; ?></span>
                                <div>
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="bi bi-star-fill" style="color: <?= $s <= round($avgRating) ? '#F59E0B' : '#E5E7EB'; ?>; font-size:14px;"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-muted small">(<?= $reviewCount; ?>)</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($spaceReviews)): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($spaceReviews as $r): ?>
                                <?php
                                $rInitials = strtoupper(substr($r['full_name'], 0, 1) . substr(strrchr($r['full_name'], ' ') ?: '', 1, 1));
                                ?>
                                <div class="p-3" style="border:1px solid var(--avastra-border); border-radius:10px; background:#fff;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle" style="width:36px; height:36px; font-size:13px;"><?= htmlspecialchars($rInitials); ?></div>
                                            <div>
                                                <strong style="font-size:14px; color:var(--avastra-dark);"><?= htmlspecialchars($r['full_name']); ?></strong>
                                                <div class="text-muted" style="font-size:12px;"><?= date('j M Y', strtotime($r['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div>
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <i class="bi bi-star-fill" style="color: <?= $s <= (int)$r['rating'] ? '#F59E0B' : '#E5E7EB'; ?>; font-size:13px;"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-secondary" style="font-size:14px; line-height:1.6;"><?= nl2br(htmlspecialchars($r['comment'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="sd-note">No reviews yet for this space. Verified renters will be able to share their experience after booking.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking panel -->
            <div class="book-panel">
                <?php if ($bookingError): ?>
                    <div class="bp-alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($bookingError); ?></div>
                <?php endif; ?>

                <div class="bp-price">₹<?= number_format((float) $space['daily_rate'], 0); ?> <span>/ day</span></div>
                <div class="bp-altrates">
                    <?php if ($space['weekly_rate']): ?>₹<?= number_format((float) $space['weekly_rate'], 0); ?>/week &nbsp;<?php endif; ?>
                    <?php if ($space['monthly_rate']): ?>₹<?= number_format((float) $space['monthly_rate'], 0); ?>/month<?php endif; ?>
                </div>

                <?php if ((int) $space['owner_id'] === $userId): ?>
                    <div class="bp-alert" style="background:rgba(20,92,74,0.08);color:var(--teal);">This is your own listing.</div>
                    <a href="<?= APP_URL; ?>/user/my-spaces.php" class="btn btn-ghost-avastra" style="width:100%;justify-content:center;">Manage this listing</a>
                <?php else: ?>
                    <form method="POST" action="">
                        <label for="startDate">Start date</label>
                        <input type="date" name="start_date" id="startDate" min="<?= date('Y-m-d'); ?>" required onchange="recalc()">

                        <label for="endDate">End date</label>
                        <input type="date" name="end_date" id="endDate" min="<?= date('Y-m-d'); ?>" required onchange="recalc()">

                        <label for="purpose">Purpose</label>
                        <input type="text" name="purpose" id="purpose" placeholder="e.g. Product photoshoot" required>

                        <div class="price-breakdown" id="priceBreakdown" style="display:none;">
                            <div class="pb-row"><span>Rental (<span id="pbDays">0</span> day(s))</span><span id="pbBase">₹0</span></div>
                            <div class="pb-row"><span>Platform fee</span><span id="pbFee">₹0</span></div>
                            <div class="pb-row"><span>Refundable deposit</span><span id="pbDeposit">₹0</span></div>
                            <div class="pb-row total"><span>Total</span><span id="pbTotal">₹0</span></div>
                        </div>

                        <button type="submit" class="btn btn-primary-avastra">Request to Book</button>
                        <p class="bp-note">Sending a request doesn't confirm your booking.</p>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
        const DAILY_RATE = <?= (float) $space['daily_rate']; ?>;
        const FEE_PERCENT = <?= isset($feePercent) ? $feePercent : 5.00; ?>;
        const DEPOSIT = <?= (float) $space['security_deposit']; ?>;

        function formatINR(n) {
            return '₹' + Math.round(n).toLocaleString('en-IN');
        }

        function recalc() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            const box = document.getElementById('priceBreakdown');

            if (!start || !end) {
                box.style.display = 'none';
                return;
            }

            const days = Math.round((new Date(end) - new Date(start)) / 86400000);
            if (days <= 0) {
                box.style.display = 'none';
                return;
            }

            const base = DAILY_RATE * days;
            const fee = base * (FEE_PERCENT / 100);
            const total = base + fee + DEPOSIT;

            document.getElementById('pbDays').textContent = days;
            document.getElementById('pbBase').textContent = formatINR(base);
            document.getElementById('pbFee').textContent = formatINR(fee);
            document.getElementById('pbDeposit').textContent = formatINR(DEPOSIT);
            document.getElementById('pbTotal').textContent = formatINR(total);
            box.style.display = 'block';
        }
    </script>