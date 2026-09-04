<?php

/** AVASTRA — Booking Details */
$pageTitle = 'Booking Details';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db        = Database::getInstance();
$userId    = (int) $currentUser['id'];
$bookingId = (int) ($_GET['id'] ?? 0);
$notice    = '';
$error     = '';

$bookingStmt = $db->prepare("
    SELECT b.*, s.title, s.address, s.city, s.state, s.owner_id,
           u.full_name AS owner_name, u.email_verified AS owner_verified,
           (SELECT image_path FROM space_images WHERE space_id = s.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS image_path
    FROM bookings b
    JOIN spaces s ON s.id = b.space_id
    JOIN users u ON u.id = s.owner_id
    WHERE b.id = :id AND b.seeker_id = :user_id
    LIMIT 1
");
$bookingStmt->execute([':id' => $bookingId, ':user_id' => $userId]);
$booking = $bookingStmt->fetch();

if ($booking && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $canCancel = in_array($booking['status'], ['pending', 'confirmed'], true)
        && strtotime($booking['start_date']) > strtotime('today');
    if (!$canCancel) {
        $error = 'This booking can no longer be cancelled online.';
    } else {
        $cancel = $db->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = :reason WHERE id = :id AND seeker_id = :user_id AND status IN ('pending', 'confirmed')");
        $cancel->execute([':reason' => 'Cancelled by the seeker.', ':id' => $bookingId, ':user_id' => $userId]);
        $booking['status'] = 'cancelled';
        $notice = 'Your booking has been cancelled.';
    }
}

$unreadNotifCount = 0;

if (!$booking):
?>
    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <main id="user-content">
            <div class="empty-state"><i class="bi bi-calendar-x"></i>
                <h3>Booking not found</h3>
                <p>This booking does not exist or is not available in your account.</p><a class="btn btn-primary-avastra" href="<?= APP_URL; ?>/user/my-bookings.php">Back to My Bookings</a>
            </div>
        </main>
        <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php exit;
endif;

$ownerParts = preg_split('/\s+/', trim($booking['owner_name']));
$ownerInitials = strtoupper(substr($ownerParts[0], 0, 1) . substr(end($ownerParts), 0, 1));
$statusLabel = match ($booking['status']) {
    'confirmed' => strtotime($booking['start_date']) <= strtotime('today') ? 'In progress' : 'Upcoming',
    'pending' => 'Pending approval',
    'active' => 'In progress',
    default => ucfirst($booking['status']),
};
$statusClass = match ($booking['status']) {
    'confirmed', 'active' => 'upcoming',
    default => $booking['status']
};
$canCancel = in_array($booking['status'], ['pending', 'confirmed'], true) && strtotime($booking['start_date']) > strtotime('today');
    ?>

    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <main id="user-content" class="booking-details-page">
            <a href="<?= APP_URL; ?>/user/my-bookings.php" class="bd-back"><i class="bi bi-arrow-left"></i> Back to bookings</a>
            <?php if ($notice): ?><div class="settings-alert success"><i class="bi bi-check-circle"></i><?= htmlspecialchars($notice); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="settings-alert error"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error); ?></div><?php endif; ?>
            <div class="bd-title-row">
                <div>
                    <h1><?= htmlspecialchars($booking['title']); ?></h1>
                    <p>Booking <?= htmlspecialchars($booking['booking_code']); ?></p>
                </div><span class="status-pill <?= $statusClass; ?>"><?= htmlspecialchars($statusLabel); ?></span>
            </div>
            <div class="bd-layout">
                <div class="bd-main">
                    <?php if ($booking['image_path']): ?><img class="bd-space-image" src="<?= APP_URL . '/' . htmlspecialchars($booking['image_path']); ?>" alt="<?= htmlspecialchars($booking['title']); ?>"><?php else: ?><div class="bd-image-fallback"><i class="bi bi-building"></i></div><?php endif; ?>
                    <section class="bd-card">
                        <h2>Booking Information</h2>
                        <div class="bd-info-grid">
                            <div><span>Space</span><strong><i class="bi bi-geo-alt"></i><?= htmlspecialchars($booking['address'] . ', ' . $booking['city']); ?></strong></div>
                            <div><span>Date</span><strong><i class="bi bi-calendar3"></i><?= date('j F Y', strtotime($booking['start_date'])); ?><?= $booking['total_days'] > 1 ? ' – ' . date('j F Y', strtotime($booking['end_date'])) : ''; ?></strong></div>
                            <div><span>Duration</span><strong><i class="bi bi-clock"></i><?= (int) $booking['total_days']; ?> day<?= (int) $booking['total_days'] === 1 ? '' : 's'; ?></strong></div>
                            <div><span>Booking status</span><strong><?= htmlspecialchars($statusLabel); ?></strong></div>
                        </div>
                        <div class="bd-copy-row"><span>Purpose</span>
                            <p><?= htmlspecialchars($booking['purpose']); ?></p>
                        </div><?php if (!empty($booking['cancellation_reason'])): ?><div class="bd-copy-row"><span>Cancellation reason</span>
                                <p><?= htmlspecialchars($booking['cancellation_reason']); ?></p>
                            </div><?php endif; ?>
                    </section>
                    <section class="bd-card bd-owner-card">
                        <h2>Space Owner</h2>
                        <div class="bd-owner-row">
                            <div class="avatar-circle-xl"><?= htmlspecialchars($ownerInitials); ?></div>
                            <div><strong><?= htmlspecialchars($booking['owner_name']); ?><?php if ($booking['owner_verified']): ?> <i class="bi bi-patch-check-fill bd-verified"></i><?php endif; ?></strong><span>Space owner</span></div><a class="btn btn-ghost-avastra btn-sm" href="<?= APP_URL; ?>/user/messages.php?with=<?= (int) $booking['owner_id']; ?>&space_id=<?= (int) $booking['space_id']; ?>"><i class="bi bi-chat"></i> Message</a>
                        </div>
                    </section>
                </div>
                <aside class="bd-sidebar">
                    <section class="bd-card bd-price-card">
                        <h2>Price Breakdown</h2>
                        <div><span>Space rate</span><strong>₹<?= number_format((float) $booking['base_amount'], 0); ?></strong></div>
                        <div><span>Platform fee</span><strong>₹<?= number_format((float) $booking['platform_fee'], 0); ?></strong></div><?php if ((float) $booking['deposit_amount'] > 0): ?><div><span>Security deposit</span><strong>₹<?= number_format((float) $booking['deposit_amount'], 0); ?></strong></div><?php endif; ?><div class="bd-total"><span>Total</span><strong>₹<?= number_format((float) $booking['total_amount'], 0); ?></strong></div>
                    </section><a class="btn btn-ghost-avastra bd-view-space" href="<?= APP_URL; ?>/user/space-details.php?id=<?= (int) $booking['space_id']; ?>">View Space</a><?php if ($canCancel): ?><form method="post" onsubmit="return confirm('Cancel this booking? This action cannot be undone.');"><input type="hidden" name="action" value="cancel"><button class="bd-cancel-btn" type="submit"><i class="bi bi-x-lg"></i> Cancel Booking</button></form><?php endif; ?>
                </aside>
            </div>
        </main>
        <?php require_once __DIR__ . '/includes/footer.php'; ?>