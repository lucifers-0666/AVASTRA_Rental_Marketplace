<?php

/** AVASTRA — Request Details */
$pageTitle = 'Request Details';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$db = Database::getInstance();
$userId = (int) $currentUser['id'];
$requestId = (int) ($_GET['id'] ?? 0);
$requestStmt = $db->prepare("SELECT b.*, s.title, s.address, s.city, s.owner_id, u.full_name AS owner_name, (SELECT image_path FROM space_images WHERE space_id = s.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS image_path FROM bookings b JOIN spaces s ON s.id = b.space_id JOIN users u ON u.id = s.owner_id WHERE b.id = :id AND b.seeker_id = :user_id LIMIT 1");
$requestStmt->execute([':id' => $requestId, ':user_id' => $userId]);
$request = $requestStmt->fetch();
$unreadNotifCount = 0;
if (!$request):
?>
    <div id="user-main"><?php require_once __DIR__ . '/includes/topbar.php'; ?><main id="user-content">
            <div class="empty-state"><i class="bi bi-file-earmark-x"></i>
                <h3>Request not found</h3>
                <p>This request does not exist or is not available in your account.</p><a class="btn btn-primary-avastra" href="<?= APP_URL; ?>/user/my-requests.php">Back to My Requests</a>
            </div>
        </main><?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php exit;
endif;
$isExpired = $request['status'] === 'pending' && strtotime($request['start_date']) < strtotime('today');
[$statusLabel, $statusClass] = $isExpired ? ['Expired', 'expired'] : match ($request['status']) {
    'confirmed', 'active', 'completed' => ['Accepted', 'accepted'],
    'rejected' => ['Rejected', 'rejected'],
    'cancelled' => ['Cancelled', 'cancelled'],
    default => ['Pending', 'pending']
};
$isAccepted = in_array($request['status'], ['confirmed', 'active', 'completed'], true);
$ownerWords = preg_split('/\s+/', trim($request['owner_name']));
$ownerInitials = strtoupper(substr($ownerWords[0], 0, 1) . substr(end($ownerWords), 0, 1));
$decisionText = match ($statusClass) {
    'accepted' => 'Request accepted',
    'rejected' => 'Request declined',
    'cancelled' => 'Request cancelled',
    'expired' => 'Request expired',
    default => 'Awaiting owner response'
};
    ?>
    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <main id="user-content" class="request-details-page">
            <a href="<?= APP_URL; ?>/user/my-requests.php" class="bd-back"><i class="bi bi-arrow-left"></i> Back to requests</a>
            <div class="bd-title-row">
                <div>
                    <h1><?= htmlspecialchars($request['title']); ?></h1>
                    <p>Request <?= htmlspecialchars($request['booking_code']); ?> · <?= htmlspecialchars($request['city']); ?></p>
                </div><span class="status-pill <?= $statusClass; ?>"><?= $statusLabel; ?></span>
            </div>
            <div class="rd-layout">
                <div class="rd-main">
                    <?php if ($request['image_path']): ?><img class="rd-space-image" src="<?= APP_URL . '/' . htmlspecialchars($request['image_path']); ?>" alt="<?= htmlspecialchars($request['title']); ?>"><?php else: ?><div class="bd-image-fallback"><i class="bi bi-building"></i></div><?php endif; ?>
                    <section class="bd-card">
                        <h2>Request Details</h2>
                        <div class="bd-info-grid">
                            <div><span>Requested date</span><strong><?= date('j F Y', strtotime($request['start_date'])); ?><?= $request['total_days'] > 1 ? ' – ' . date('j F Y', strtotime($request['end_date'])) : ''; ?></strong></div>
                            <div><span>Duration</span><strong><?= (int) $request['total_days']; ?> day<?= (int) $request['total_days'] === 1 ? '' : 's'; ?></strong></div>
                            <div><span>Amount</span><strong>₹<?= number_format((float) $request['total_amount'], 0); ?></strong></div>
                            <div><span>Space</span><strong><i class="bi bi-geo-alt"></i><?= htmlspecialchars($request['address'] . ', ' . $request['city']); ?></strong></div>
                        </div>
                        <div class="rd-message"><span>Your request to owner</span>
                            <p><?= htmlspecialchars($request['purpose']); ?></p>
                        </div>
                    </section>
                    <section class="bd-card rd-owner-response">
                        <div class="rd-owner-heading">
                            <div class="avatar-circle-xl"><?= htmlspecialchars($ownerInitials); ?></div>
                            <div><strong><?= htmlspecialchars($request['owner_name']); ?></strong><span>Space owner</span></div><a class="btn btn-ghost-avastra btn-sm" href="<?= APP_URL; ?>/user/messages.php?with=<?= (int) $request['owner_id']; ?>&space_id=<?= (int) $request['space_id']; ?>"><i class="bi bi-chat"></i> Message</a>
                        </div>
                        <p>No separate owner response has been added for this request.</p>
                    </section>
                    <?php if ($isAccepted): ?><section class="rd-accepted">
                            <h2><i class="bi bi-check-circle"></i> Request accepted</h2>
                            <p>Your booking request has been accepted. View the booking to see its full details.</p><a class="btn btn-primary-avastra" href="<?= APP_URL; ?>/user/booking-details.php?id=<?= (int) $request['id']; ?>">Continue to Booking <i class="bi bi-arrow-right"></i></a>
                        </section><?php endif; ?>
                </div>
                <aside class="rd-timeline bd-card">
                    <h2>Timeline</h2>
                    <div class="rd-timeline-item"><i class="bi bi-clock"></i>
                        <div><strong>Request submitted</strong><span><?= date('j M Y, g:i A', strtotime($request['created_at'])); ?></span></div>
                    </div>
                    <div class="rd-timeline-item <?= $statusClass !== 'pending' ? 'current' : ''; ?>"><i class="bi <?= $statusClass === 'accepted' ? 'bi-check-lg' : 'bi-hourglass-split'; ?>"></i>
                        <div><strong><?= htmlspecialchars($decisionText); ?></strong><span><?= $statusClass === 'pending' ? 'We will notify you when the owner responds.' : date('j M Y, g:i A', strtotime($request['updated_at'])); ?></span></div>
                    </div>
                </aside>
            </div>
        </main>
        <?php require_once __DIR__ . '/includes/footer.php'; ?>