<?php

/**
 * AVASTRA — My Bookings
 *
 * Reads the same `bookings` table as my-requests.php, but this page only
 * cares about bookings that were actually accepted at some point — a
 * `rejected` request never became a real booking, so it's deliberately
 * left out of every tab here (it lives on the My Requests page instead).
 */
$pageTitle = 'My Bookings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

$tab = $_GET['tab'] ?? 'upcoming';
if (!in_array($tab, ['upcoming', 'pending', 'completed', 'cancelled'], true)) {
    $tab = 'upcoming';
}

/* Counts for the tab badges — one small query per tab, all scoped to this user */
function countBookings(PDO $db, int $uid, string $extraWhere): int
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE seeker_id = :uid AND $extraWhere");
    $stmt->execute([':uid' => $uid]);
    return (int) $stmt->fetchColumn();
}
$counts = [
    'upcoming'  => countBookings($db, $userId, "status IN ('confirmed','active') AND end_date >= CURDATE()"),
    'pending'   => countBookings($db, $userId, "status = 'pending'"),
    'completed' => countBookings($db, $userId, "status = 'completed'"),
    'cancelled' => countBookings($db, $userId, "status = 'cancelled'"),
];

$whereByTab = [
    'upcoming'  => "status IN ('confirmed','active') AND end_date >= CURDATE()",
    'pending'   => "status = 'pending'",
    'completed' => "status = 'completed'",
    'cancelled' => "status = 'cancelled'",
];

$stmt = $db->prepare("
    SELECT b.*, s.title, s.city, s.address,
           (SELECT image_path FROM space_images WHERE space_id = s.id AND is_primary = 1 LIMIT 1) AS image_path
    FROM bookings b
    JOIN spaces s ON b.space_id = s.id
    WHERE b.seeker_id = :uid AND {$whereByTab[$tab]}
    ORDER BY b.start_date ASC
");
$stmt->execute([':uid' => $userId]);
$bookings = $stmt->fetchAll();

$unreadNotifCount = 0; // used by topbar.php

$tabLabels = [
    'upcoming'  => 'Upcoming',
    'pending'   => 'Pending',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <div class="greeting-row">
            <div>
                <h1>My Bookings</h1>
            </div>
            <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Find a Space</a>
        </div>

        <nav class="tab-underline">
            <?php foreach ($tabLabels as $key => $label): ?>
                <a href="?tab=<?= $key; ?>" class="<?= $tab === $key ? 'active' : ''; ?>">
                    <?= $label; ?>
                    <?php if ($counts[$key] > 0): ?><span class="tab-count"><?= $counts[$key]; ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <h3>No <?= strtolower($tabLabels[$tab]); ?> bookings</h3>
                <p>Once you have a booking in this stage, it'll show up here.</p>
                <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Find a Space</a>
            </div>
        <?php else: ?>
            <div class="booking-list">
                <?php foreach ($bookings as $b): ?>
                    <?php
                    $totalDays = (int) $b['total_days'];
                    $dateRange = $totalDays > 1
                        ? date('j M Y', strtotime($b['start_date'])) . ' – ' . date('j M Y', strtotime($b['end_date']))
                        : date('j F Y', strtotime($b['start_date']));
                    $pillClass = match ($b['status']) {
                        'confirmed', 'active' => 'upcoming',
                        default => $b['status'],
                    };
                    ?>
                    <a href="<?= APP_URL; ?>/user/booking-details.php?id=<?= (int) $b['id']; ?>" class="booking-row" style="color:inherit;">
                        <?php if ($b['image_path']): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($b['image_path']); ?>" alt="<?= htmlspecialchars($b['title']); ?>">
                        <?php else: ?>
                            <div class="img-fallback"><i class="bi bi-building" style="font-size:26px;color:rgba(23,32,27,0.25);"></i></div>
                        <?php endif; ?>
                        <div class="bk-body">
                            <div class="bk-top">
                                <div>
                                    <h3><?= htmlspecialchars($b['title']); ?></h3>
                                    <div class="bk-loc"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($b['city']); ?></div>
                                </div>
                                <span class="status-pill <?= $pillClass; ?>"><?= ucfirst($tabLabels[$tab]); ?></span>
                            </div>
                            <div class="bk-meta">
                                <span><i class="bi bi-calendar3"></i> <?= $dateRange; ?></span>
                                <span><i class="bi bi-moon"></i> <?= $totalDays; ?> day<?= $totalDays > 1 ? 's' : ''; ?></span>
                                <span><strong>₹<?= number_format((float) $b['total_amount'], 0); ?></strong></span>
                            </div>
                            <div class="bk-bottom">
                                <span class="bk-code">Booking <?= htmlspecialchars($b['booking_code']); ?></span>
                                <span class="bk-view">View Details <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>