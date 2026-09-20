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

if (!$booking) {
    $unreadNotifCount = 0;
    ?>
    <div id="user-main">
        <?php require_once __DIR__ . '/includes/topbar.php'; ?>
        <main id="user-content">
            <div class="empty-state"><i class="bi bi-calendar-x"></i>
                <h3>Booking not found</h3>
                <p>This booking does not exist or is not available in your account.</p>
                <a class="btn btn-primary-avastra" href="<?= APP_URL; ?>/user/my-bookings.php">Back to My Bookings</a>
            </div>
        </main>
        <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <?php
    exit;
}

// Check for completed or pending payment
$payStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = :bid ORDER BY id DESC LIMIT 1");
$payStmt->execute([':bid' => $bookingId]);
$payment = $payStmt->fetch();

// Check for review
$revStmt = $db->prepare("SELECT * FROM reviews WHERE booking_id = :bid AND reviewer_id = :uid LIMIT 1");
$revStmt->execute([':bid' => $bookingId, ':uid' => $userId]);
$existingReview = $revStmt->fetch();

// Check for complaints
$compStmt = $db->prepare("SELECT * FROM complaints WHERE booking_id = :bid AND user_id = :uid ORDER BY id DESC");
$compStmt->execute([':bid' => $bookingId, ':uid' => $userId]);
$bookingComplaints = $compStmt->fetchAll();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CANCEL BOOKING
    if ($action === 'cancel') {
        $canCancel = in_array($booking['status'], ['pending', 'confirmed'], true)
            && strtotime($booking['start_date']) > strtotime('today');
        if (!$canCancel) {
            $error = 'This booking can no longer be cancelled online.';
        } else {
            $cancel = $db->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = :reason WHERE id = :id AND seeker_id = :user_id AND status IN ('pending', 'confirmed')");
            $cancel->execute([':reason' => 'Cancelled by the seeker.', ':id' => $bookingId, ':user_id' => $userId]);
            $booking['status'] = 'cancelled';
            $notice = 'Your booking has been cancelled successfully.';

            // Notify owner
            $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:uid, 'Booking Cancelled', :msg)")
                ->execute([
                    ':uid' => (int)$booking['owner_id'],
                    ':msg' => "Booking #{$booking['booking_code']} for {$booking['title']} was cancelled by the renter."
                ]);
        }
    }

    // 2. SUBMIT / UPDATE REVIEW
    elseif ($action === 'submit_review') {
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $error = 'Please select a star rating between 1 and 5.';
        } elseif (empty($comment)) {
            $error = 'Please write a brief comment describing your experience.';
        } else {
            if ($existingReview) {
                $upRev = $db->prepare("UPDATE reviews SET rating = :r, comment = :c, updated_at = NOW() WHERE id = :id");
                // Note: reviews table doesn't have updated_at in standard schema, fallback safely
                try {
                    $upRev = $db->prepare("UPDATE reviews SET rating = :r, comment = :c WHERE id = :id");
                    $upRev->execute([':r' => $rating, ':c' => $comment, ':id' => $existingReview['id']]);
                } catch (Throwable $e) {
                    $error = 'Failed to update review.';
                }
            } else {
                $insRev = $db->prepare("INSERT INTO reviews (booking_id, space_id, reviewer_id, rating, comment, is_approved) VALUES (:bid, :sid, :uid, :r, :c, 1)");
                $insRev->execute([
                    ':bid' => $bookingId,
                    ':sid' => (int)$booking['space_id'],
                    ':uid' => $userId,
                    ':r'   => $rating,
                    ':c'   => $comment
                ]);
            }
            $notice = 'Thank you! Your review and rating have been recorded.';
            // Refresh review
            $revStmt->execute([':bid' => $bookingId, ':uid' => $userId]);
            $existingReview = $revStmt->fetch();
        }
    }

    // 3. COMPLETE PAYMENT (SIMULATION)
    elseif ($action === 'make_payment') {
        if ($payment && $payment['status'] === 'completed') {
            $error = 'This booking has already been paid for.';
        } else {
            $method = $_POST['payment_method'] ?? 'razorpay';
            $validMethods = ['razorpay', 'cash', 'bank_transfer', 'pay_later'];
            if (!in_array($method, $validMethods, true)) {
                $method = 'razorpay';
            }
            $txnId = 'TXN-' . strtoupper(bin2hex(random_bytes(5)));
            $amount = (float)$booking['total_amount'];

            $insPay = $db->prepare("INSERT INTO payments (booking_id, transaction_id, payment_method, amount, status, paid_at) VALUES (:bid, :txn, :method, :amt, 'completed', NOW())");
            $insPay->execute([
                ':bid'    => $bookingId,
                ':txn'    => $txnId,
                ':method' => $method,
                ':amt'    => $amount
            ]);

            // If pending, advance to confirmed
            if ($booking['status'] === 'pending') {
                $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = :id")->execute([':id' => $bookingId]);
                $booking['status'] = 'confirmed';
            }

            // Send notification to seeker & owner
            try {
                $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:uid, 'Payment Successful', :msg)")
                    ->execute([
                        ':uid' => $userId,
                        ':msg' => "Payment of ₹" . number_format($amount, 2) . " for booking #{$booking['booking_code']} was completed successfully."
                    ]);
                $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:uid, 'Payment Received', :msg)")
                    ->execute([
                        ':uid' => (int)$booking['owner_id'],
                        ':msg' => "Renter completed payment of ₹" . number_format($amount, 2) . " for booking #{$booking['booking_code']}."
                    ]);
            } catch (Throwable $ignore) {}

            $notice = 'Payment of ₹' . number_format($amount, 0) . ' completed successfully via ' . ucfirst(str_replace('_', ' ', $method)) . '!';

            // Refresh payment
            $payStmt->execute([':bid' => $bookingId]);
            $payment = $payStmt->fetch();
        }
    }

    // 4. SUBMIT COMPLAINT / ISSUE TICKET
    elseif ($action === 'file_complaint') {
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($subject) || empty($description)) {
            $error = 'Please provide both a subject and a description for your complaint.';
        } else {
            $insComp = $db->prepare("INSERT INTO complaints (booking_id, user_id, subject, description, status) VALUES (:bid, :uid, :sub, :desc, 'open')");
            $insComp->execute([
                ':bid'  => $bookingId,
                ':uid'  => $userId,
                ':sub'  => $subject,
                ':desc' => $description
            ]);

            try {
                $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (:uid, 'Support Ticket Created', :msg)")
                    ->execute([
                        ':uid' => $userId,
                        ':msg' => "Your complaint '{$subject}' has been submitted. AVASTRA Support will review it shortly."
                    ]);
            } catch (Throwable $ignore) {}

            $notice = 'Your complaint ticket has been submitted to AVASTRA Support. Our admin team will review it shortly.';

            // Refresh complaints
            $compStmt->execute([':bid' => $bookingId, ':uid' => $userId]);
            $bookingComplaints = $compStmt->fetchAll();
        }
    }
}

$unreadNotifCount = 0;

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
$canReview = in_array($booking['status'], ['confirmed', 'completed', 'active'], true) || strtotime($booking['end_date']) <= strtotime('today');
$isPaid = $payment && $payment['status'] === 'completed';
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    <main id="user-content" class="booking-details-page">
        <a href="<?= APP_URL; ?>/user/my-bookings.php" class="bd-back"><i class="bi bi-arrow-left"></i> Back to bookings</a>

        <?php if ($notice): ?>
            <div class="settings-alert success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="settings-alert error"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="bd-title-row">
            <div>
                <h1><?= htmlspecialchars($booking['title']); ?></h1>
                <p>Booking <?= htmlspecialchars($booking['booking_code']); ?></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="status-pill <?= $statusClass; ?>"><?= htmlspecialchars($statusLabel); ?></span>
                <?php if ($isPaid): ?>
                    <span class="status-pill completed" style="background:#E7F5EC;color:var(--avastra-primary);"><i class="bi bi-check2-circle"></i> Paid</span>
                <?php else: ?>
                    <span class="status-pill pending" style="background:#FFF3CD;color:#856404;"><i class="bi bi-clock-history"></i> Payment Pending</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="bd-layout">
            <div class="bd-main">
                <?php if ($booking['image_path']): ?>
                    <img class="bd-space-image" src="<?= APP_URL . '/' . htmlspecialchars($booking['image_path']); ?>" alt="<?= htmlspecialchars($booking['title']); ?>">
                <?php else: ?>
                    <div class="bd-image-fallback"><i class="bi bi-building"></i></div>
                <?php endif; ?>

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
                    </div>
                    <?php if (!empty($booking['cancellation_reason'])): ?>
                        <div class="bd-copy-row"><span>Cancellation reason</span>
                            <p><?= htmlspecialchars($booking['cancellation_reason']); ?></p>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- REVIEW & RATING SECTION -->
                <section class="bd-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0">Renter Review &amp; Rating</h2>
                        <?php if ($canReview): ?>
                            <button type="button" class="btn btn-ghost-avastra btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="bi bi-star"></i> <?= $existingReview ? 'Edit Review' : 'Rate & Review'; ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($existingReview): ?>
                        <div class="p-3" style="background:var(--avastra-light); border-radius:10px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="bi bi-star-fill" style="color: <?= $s <= (int)$existingReview['rating'] ? '#F59E0B' : '#E5E7EB'; ?>; font-size:18px;"></i>
                                    <?php endfor; ?>
                                    <strong class="ms-2" style="color:var(--avastra-dark);"><?= (int)$existingReview['rating']; ?> / 5</strong>
                                </div>
                                <span class="text-muted small"><?= date('j M Y', strtotime($existingReview['created_at'])); ?></span>
                            </div>
                            <p class="mb-0 text-secondary" style="font-size:14px; line-height:1.6;"><?= nl2br(htmlspecialchars($existingReview['comment'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="p-3 text-center text-muted" style="background:#FAFAFA; border:1px dashed var(--avastra-border); border-radius:10px;">
                            <i class="bi bi-chat-square-heart" style="font-size:24px; color:var(--avastra-primary);"></i>
                            <p class="mb-1 mt-2" style="font-weight:500; color:var(--avastra-dark);">No review submitted yet</p>
                            <span class="small">Share feedback on cleanliness, location, amenities, and owner support.</span>
                            <?php if ($canReview): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-primary-avastra btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">Write a Review</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- DISPUTES / COMPLAINTS SECTION -->
                <section class="bd-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0">Support &amp; Issue Resolution</h2>
                        <button type="button" class="btn btn-ghost-avastra btn-sm" data-bs-toggle="modal" data-bs-target="#complaintModal">
                            <i class="bi bi-shield-exclamation"></i> Report an Issue
                        </button>
                    </div>

                    <?php if (!empty($bookingComplaints)): ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($bookingComplaints as $c): ?>
                                <?php
                                $cBadge = match ($c['status']) {
                                    'open' => 'background:#FFF3CD; color:#856404;',
                                    'in_progress' => 'background:#CCE5FF; color:#004085;',
                                    'resolved' => 'background:#E7F5EC; color:var(--avastra-primary);',
                                    'closed' => 'background:#E9ECEF; color:#6C757D;',
                                    default => 'background:#E9ECEF; color:#6C757D;'
                                };
                                ?>
                                <div class="p-3" style="border:1px solid var(--avastra-border); border-radius:10px; background:#fff;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong style="color:var(--avastra-dark);"><?= htmlspecialchars($c['subject']); ?></strong>
                                        <span class="badge rounded-pill" style="<?= $cBadge; ?> font-weight:600; font-size:11px; padding:4px 10px;"><?= strtoupper(str_replace('_', ' ', $c['status'])); ?></span>
                                    </div>
                                    <p class="text-secondary small mb-2"><?= nl2br(htmlspecialchars($c['description'])); ?></p>
                                    <?php if (!empty($c['resolution_notes'])): ?>
                                        <div class="p-2 mt-2" style="background:#F0FDF4; border-left:3px solid var(--avastra-primary); border-radius:4px;">
                                            <strong class="small text-success"><i class="bi bi-check-circle"></i> Admin Resolution:</strong>
                                            <p class="small text-secondary mb-0 mt-1"><?= nl2br(htmlspecialchars($c['resolution_notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-muted small mt-2"><i class="bi bi-clock"></i> Filed on <?= date('j M Y, g:i A', strtotime($c['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Have an issue with this booking or listing? You can submit a dispute or support ticket anytime, and the AVASTRA operations team will intervene.</p>
                    <?php endif; ?>
                </section>

                <section class="bd-card bd-owner-card">
                    <h2>Space Owner</h2>
                    <div class="bd-owner-row">
                        <div class="avatar-circle-xl"><?= htmlspecialchars($ownerInitials); ?></div>
                        <div>
                            <strong><?= htmlspecialchars($booking['owner_name']); ?>
                                <?php if ($booking['owner_verified']): ?>
                                    <i class="bi bi-patch-check-fill bd-verified" title="Verified Owner"></i>
                                <?php endif; ?>
                            </strong>
                            <span>Space owner</span>
                        </div>
                        <a class="btn btn-ghost-avastra btn-sm" href="<?= APP_URL; ?>/user/messages.php?with=<?= (int) $booking['owner_id']; ?>&space_id=<?= (int) $booking['space_id']; ?>">
                            <i class="bi bi-chat"></i> Message
                        </a>
                    </div>
                </section>
            </div>

            <aside class="bd-sidebar">
                <!-- PRICE & PAYMENT CARD -->
                <section class="bd-card bd-price-card">
                    <h2>Price Breakdown</h2>
                    <div><span>Space rate</span><strong>₹<?= number_format((float) $booking['base_amount'], 0); ?></strong></div>
                    <div><span>Platform fee</span><strong>₹<?= number_format((float) $booking['platform_fee'], 0); ?></strong></div>
                    <?php if ((float) $booking['deposit_amount'] > 0): ?>
                        <div><span>Security deposit</span><strong>₹<?= number_format((float) $booking['deposit_amount'], 0); ?></strong></div>
                    <?php endif; ?>
                    <div class="bd-total"><span>Total</span><strong>₹<?= number_format((float) $booking['total_amount'], 0); ?></strong></div>

                    <!-- PAYMENT STATUS RECEIPT -->
                    <div class="mt-3 pt-3" style="border-top:1px solid var(--avastra-border);">
                        <?php if ($isPaid): ?>
                            <div class="p-3" style="background:#E7F5EC; border-radius:8px; border:1px solid rgba(20,92,74,0.2);">
                                <div class="d-flex align-items-center gap-2 mb-1" style="color:var(--avastra-primary); font-weight:600;">
                                    <i class="bi bi-check-circle-fill"></i> Payment Completed
                                </div>
                                <div class="small text-muted mb-1">Method: <strong><?= strtoupper($payment['payment_method']); ?></strong></div>
                                <div class="small text-muted mb-1">Txn: <code style="color:var(--avastra-primary);"><?= htmlspecialchars($payment['transaction_id']); ?></code></div>
                                <div class="small text-muted">Paid at: <?= date('j M Y, g:i A', strtotime($payment['paid_at'])); ?></div>
                            </div>
                        <?php elseif ($booking['status'] !== 'cancelled' && $booking['status'] !== 'rejected'): ?>
                            <button type="button" class="btn btn-primary-avastra w-100 justify-content-center mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="bi bi-credit-card-2-front"></i> Pay ₹<?= number_format((float) $booking['total_amount'], 0); ?>
                            </button>
                            <p class="text-muted small text-center mb-0">Secure checkout simulation</p>
                        <?php endif; ?>
                    </div>
                </section>

                <a class="btn btn-ghost-avastra bd-view-space" href="<?= APP_URL; ?>/user/space-details.php?id=<?= (int) $booking['space_id']; ?>">View Space</a>

                <?php if ($canCancel): ?>
                    <form method="post" onsubmit="return confirm('Cancel this booking? This action cannot be undone.');">
                        <input type="hidden" name="action" value="cancel">
                        <button class="bd-cancel-btn" type="submit"><i class="bi bi-x-lg"></i> Cancel Booking</button>
                    </form>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <!-- PAYMENT MODAL -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 12px 36px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid var(--avastra-border); padding:20px 24px;">
                    <h5 class="modal-title" id="paymentModalLabel" style="font-family:'DM Serif Display',serif; font-size:22px; color:var(--avastra-dark);">
                        <i class="bi bi-credit-card" style="color:var(--avastra-primary);"></i> Complete Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="make_payment">
                    <div class="modal-body" style="padding:24px;">
                        <div class="p-3 mb-3" style="background:var(--avastra-light); border-radius:10px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary small">Total Amount to Pay</span>
                                <strong style="font-size:22px; color:var(--avastra-primary);">₹<?= number_format((float) $booking['total_amount'], 2); ?></strong>
                            </div>
                            <div class="small text-muted mt-1">For Booking <?= htmlspecialchars($booking['booking_code']); ?> (<?= htmlspecialchars($booking['title']); ?>)</div>
                        </div>

                        <label class="form-label fw-bold mb-2" style="font-size:14px;">Select Payment Method</label>
                        <div class="d-flex flex-column gap-2 mb-3">
                            <label class="p-3 d-flex align-items-center gap-3 border rounded-3 cursor-pointer payment-opt" style="cursor:pointer;">
                                <input type="radio" name="payment_method" value="razorpay" checked>
                                <div>
                                    <strong class="d-block" style="color:var(--avastra-dark);"><i class="bi bi-shield-check text-primary"></i> Razorpay Simulator (Cards / UPI / NetBanking)</strong>
                                    <span class="small text-muted">Instant online confirmation via test gateway</span>
                                </div>
                            </label>
                            <label class="p-3 d-flex align-items-center gap-3 border rounded-3 cursor-pointer payment-opt" style="cursor:pointer;">
                                <input type="radio" name="payment_method" value="cash">
                                <div>
                                    <strong class="d-block" style="color:var(--avastra-dark);"><i class="bi bi-cash-stack text-success"></i> Cash on Arrival</strong>
                                    <span class="small text-muted">Pay directly to the space owner at check-in</span>
                                </div>
                            </label>
                            <label class="p-3 d-flex align-items-center gap-3 border rounded-3 cursor-pointer payment-opt" style="cursor:pointer;">
                                <input type="radio" name="payment_method" value="bank_transfer">
                                <div>
                                    <strong class="d-block" style="color:var(--avastra-dark);"><i class="bi bi-bank text-info"></i> Bank IMPS / NEFT Transfer</strong>
                                    <span class="small text-muted">Direct transfer to AVASTRA Escrow Account</span>
                                </div>
                            </label>
                            <label class="p-3 d-flex align-items-center gap-3 border rounded-3 cursor-pointer payment-opt" style="cursor:pointer;">
                                <input type="radio" name="payment_method" value="pay_later">
                                <div>
                                    <strong class="d-block" style="color:var(--avastra-dark);"><i class="bi bi-calendar-check text-warning"></i> Pay Later (Credit Terms)</strong>
                                    <span class="small text-muted">Invoice generated, payable within 7 days</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--avastra-border); padding:16px 24px;">
                        <button type="button" class="btn btn-ghost-avastra" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-avastra">Confirm &amp; Pay ₹<?= number_format((float) $booking['total_amount'], 0); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- REVIEW & RATING MODAL -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 12px 36px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid var(--avastra-border); padding:20px 24px;">
                    <h5 class="modal-title" id="reviewModalLabel" style="font-family:'DM Serif Display',serif; font-size:22px; color:var(--avastra-dark);">
                        <i class="bi bi-star-fill text-warning"></i> <?= $existingReview ? 'Update Your Review' : 'Rate & Review Space'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="submit_review">
                    <div class="modal-body" style="padding:24px;">
                        <p class="text-secondary small mb-3">Rate your overall experience with <strong><?= htmlspecialchars($booking['title']); ?></strong>.</p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1" style="font-size:14px;">Star Rating</label>
                            <div class="d-flex gap-2 rating-stars">
                                <?php $currentRating = (int)($existingReview['rating'] ?? 5); ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label style="cursor:pointer; font-size:26px;">
                                        <input type="radio" name="rating" value="<?= $i; ?>" class="d-none" <?= $currentRating === $i ? 'checked' : ''; ?>>
                                        <i class="bi bi-star-fill star-icon <?= $i <= $currentRating ? 'text-warning' : 'text-muted'; ?>" data-val="<?= $i; ?>"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1" style="font-size:14px;">Your Comments &amp; Feedback</label>
                            <textarea name="comment" class="form-control" rows="4" required placeholder="Describe your experience with the space, amenities, cleanliness, and communication with the owner..." style="border-radius:8px;"><?= htmlspecialchars($existingReview['comment'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--avastra-border); padding:16px 24px;">
                        <button type="button" class="btn btn-ghost-avastra" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-avastra">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- COMPLAINT / ISSUE MODAL -->
    <div class="modal fade" id="complaintModal" tabindex="-1" aria-labelledby="complaintModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 12px 36px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid var(--avastra-border); padding:20px 24px;">
                    <h5 class="modal-title" id="complaintModalLabel" style="font-family:'DM Serif Display',serif; font-size:22px; color:var(--avastra-dark);">
                        <i class="bi bi-shield-exclamation text-danger"></i> Report an Issue / Dispute
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="file_complaint">
                    <div class="modal-body" style="padding:24px;">
                        <p class="text-secondary small mb-3">Our support team will review your report and coordinate between you and the owner to resolve the issue.</p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1" style="font-size:14px;">Issue Subject</label>
                            <input type="text" name="subject" class="form-control" required placeholder="e.g. Space not clean upon arrival, Key not provided, Amenities missing" style="border-radius:8px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1" style="font-size:14px;">Detailed Description</label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="Please provide exact details about what occurred, time of event, and what resolution you are requesting..." style="border-radius:8px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--avastra-border); padding:16px 24px;">
                        <button type="button" class="btn btn-ghost-avastra" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-avastra" style="background:#DC2626; border-color:#DC2626;">Submit Dispute Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Interactive star rating in modal
    document.querySelectorAll('.rating-stars label').forEach(label => {
        label.addEventListener('click', function() {
            const input = this.querySelector('input');
            const val = parseInt(input.value);
            document.querySelectorAll('.star-icon').forEach(icon => {
                const sVal = parseInt(icon.dataset.val);
                if (sVal <= val) {
                    icon.classList.remove('text-muted');
                    icon.classList.add('text-warning');
                } else {
                    icon.classList.remove('text-warning');
                    icon.classList.add('text-muted');
                }
            });
        });
    });
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>