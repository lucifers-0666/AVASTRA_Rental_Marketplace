<?php

/**
 * AVASTRA — My Requests
 *
 * NOTE: there's no separate "requests" table — a request and a confirmed
 * booking are the same row in `bookings`, just at different points in its
 * `status` lifecycle. This page and my-bookings.php both read that same
 * table with different filters, which is expected.
 *
 * NOTE: `bookings.status` only has: pending, confirmed, active, completed,
 * cancelled, rejected — there is no "expired" value in the schema. This
 * page computes "Expired" itself: a request that's still `pending` after
 * its start_date has already passed.
 */
$pageTitle = 'My Requests';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

$statusFilter = $_GET['status'] ?? 'all';
$validFilters = ['all', 'pending', 'accepted', 'rejected', 'expired', 'cancelled'];
if (!in_array($statusFilter, $validFilters, true)) {
    $statusFilter = 'all';
}

$sql = "
    SELECT b.*, s.title, s.city, s.address,
           (SELECT image_path FROM space_images WHERE space_id = s.id AND is_primary = 1 LIMIT 1) AS image_path
    FROM bookings b
    JOIN spaces s ON b.space_id = s.id
    WHERE b.seeker_id = :uid
";
$params = [':uid' => $userId];

switch ($statusFilter) {
    case 'pending':
        $sql .= " AND b.status = 'pending' AND b.start_date >= CURDATE()";
        break;
    case 'expired':
        $sql .= " AND b.status = 'pending' AND b.start_date < CURDATE()";
        break;
    case 'accepted':
        $sql .= " AND b.status IN ('confirmed', 'active', 'completed')";
        break;
    case 'rejected':
        $sql .= " AND b.status = 'rejected'";
        break;
    case 'cancelled':
        $sql .= " AND b.status = 'cancelled'";
        break;
        // 'all' — no extra condition
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

/**
 * Turn a DB row into a display label + CSS class for the status pill.
 * This is where the computed "Expired" state actually gets decided.
 */
function requestStatusLabel(array $row): array
{
    if ($row['status'] === 'pending') {
        return (strtotime($row['start_date']) < strtotime('today'))
            ? ['Expired', 'expired']
            : ['Pending', 'pending'];
    }
    return match ($row['status']) {
        'confirmed', 'active', 'completed' => ['Accepted', 'accepted'],
        'rejected'                          => ['Rejected', 'rejected'],
        'cancelled'                         => ['Cancelled', 'cancelled'],
        default                             => [ucfirst($row['status']), 'pending'],
    };
}

$unreadNotifCount = 0; // used by topbar.php

$tabs = [
    'all'       => 'All',
    'pending'   => 'Pending',
    'accepted'  => 'Accepted',
    'rejected'  => 'Rejected',
    'expired'   => 'Expired',
    'cancelled' => 'Cancelled',
];
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <div class="greeting-row">
            <div>
                <h1>My Requests</h1>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="bp-alert success" style="margin-bottom:20px;">
                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_SESSION['flash_success']); ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <div class="status-tabs">
            <?php foreach ($tabs as $key => $label): ?>
                <a href="?status=<?= $key; ?>" class="status-tab <?= $statusFilter === $key ? 'active' : ''; ?>"><?= $label; ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <h3>No requests here</h3>
                <p>Once you request a space, it'll show up in this list.</p>
                <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Find a Space</a>
            </div>
        <?php else: ?>
            <div class="request-list">
                <?php foreach ($requests as $r): ?>
                    <?php
                    [$statusText, $statusClass] = requestStatusLabel($r);
                    $totalDays = (int) $r['total_days'];
                    $dateRange = $totalDays > 1
                        ? date('j M Y', strtotime($r['start_date'])) . ' – ' . date('j M Y', strtotime($r['end_date']))
                        : date('j F Y', strtotime($r['start_date']));
                    ?>
                    <a href="<?= APP_URL; ?>/user/request-details.php?id=<?= (int) $r['id']; ?>" class="request-row">
                        <?php if ($r['image_path']): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($r['image_path']); ?>" alt="<?= htmlspecialchars($r['title']); ?>">
                        <?php else: ?>
                            <div class="img-fallback"><i class="bi bi-building" style="font-size:26px;color:rgba(23,32,27,0.25);"></i></div>
                        <?php endif; ?>
                        <div class="r-body">
                            <div class="r-top">
                                <div>
                                    <h3><?= htmlspecialchars($r['title']); ?></h3>
                                    <div class="r-loc"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($r['city']); ?></div>
                                </div>
                                <span class="status-pill <?= $statusClass; ?>"><?= $statusText; ?></span>
                            </div>
                            <div class="r-meta">
                                <span><i class="bi bi-calendar3"></i> <?= $dateRange; ?></span>
                                <span><i class="bi bi-moon"></i> <?= $totalDays; ?> day<?= $totalDays > 1 ? 's' : ''; ?></span>
                                <span><strong>₹<?= number_format((float) $r['total_amount'], 0); ?></strong></span>
                            </div>
                            <div class="r-bottom">
                                <span class="r-timestamps">
                                    Submitted <?= date('j F Y', strtotime($r['created_at'])); ?> · Updated <?= date('j F Y', strtotime($r['updated_at'])); ?>
                                </span>
                                <span class="r-view">View Request <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>