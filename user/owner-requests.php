<?php

/**
 * AVASTRA — Booking Requests (owner side)
 *
 * This is the missing half of the request/booking loop: my-requests.php
 * and my-bookings.php show a seeker their own requests. This page is
 * where the SPACE OWNER sees requests made on their spaces and actually
 * accepts or rejects them — nothing else in the app changes a booking's
 * status away from 'pending', so without this page every request stays
 * pending forever.
 *
 * Reuses `cancellation_reason` to store the owner's rejection reason too
 * — there's no separate column for it, and the field is just a generic
 * "why this booking ended this way" note.
 */
$pageTitle = 'Booking Requests';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];
$notice = '';
$error  = '';

/* -----------------------------------------------------------
   ACCEPT / REJECT A REQUEST
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $bookingId = (int) $_POST['booking_id'];
    $action    = $_POST['action'];

    // Confirm this booking is actually pending AND on a space this user owns.
    $ownCheck = $db->prepare("
        SELECT b.id FROM bookings b
        JOIN spaces s ON s.id = b.space_id
        WHERE b.id = :id AND s.owner_id = :uid AND b.status = 'pending'
    ");
    $ownCheck->execute([':id' => $bookingId, ':uid' => $userId]);

    if (!$ownCheck->fetch()) {
        $error = "That request isn't available to act on anymore.";
    } elseif ($action === 'accept') {
        $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = :id")
            ->execute([':id' => $bookingId]);
        $notice = 'Request accepted — the booking is now confirmed.';
    } elseif ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '') ?: 'No reason given.';
        $db->prepare("UPDATE bookings SET status = 'rejected', cancellation_reason = :reason WHERE id = :id")
            ->execute([':reason' => $reason, ':id' => $bookingId]);
        $notice = 'Request declined.';
    }
}

/* -----------------------------------------------------------
   PENDING REQUESTS ON MY SPACES
----------------------------------------------------------- */
$stmt = $db->prepare("
    SELECT b.*, s.title, s.city,
           u.full_name AS seeker_name, u.email_verified AS seeker_verified,
           (SELECT image_path FROM space_images WHERE space_id = s.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS image_path
    FROM bookings b
    JOIN spaces s ON s.id = b.space_id
    JOIN users u ON u.id = b.seeker_id
    WHERE s.owner_id = :uid AND b.status = 'pending'
    ORDER BY b.created_at ASC
");
$stmt->execute([':uid' => $userId]);
$requests = $stmt->fetchAll();

$unreadNotifCount = 0; // used by includes/topbar.php
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main id="user-content" class="owner-requests-page">

        <div class="greeting-row">
            <div>
                <h1>Booking Requests</h1>
                <p><?= count($requests); ?> request<?= count($requests) === 1 ? '' : 's'; ?> waiting on your response</p>
            </div>
        </div>

        <?php if ($notice): ?><div class="settings-alert success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($notice); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="settings-alert error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>No pending requests</h3>
                <p>When someone requests one of your spaces, it'll show up here for you to accept or decline.</p>
                <a href="<?= APP_URL; ?>/user/my-spaces.php" class="btn btn-primary-avastra">View My Spaces</a>
            </div>
        <?php else: ?>
            <div class="owner-request-list">
                <?php foreach ($requests as $r): ?>
                    <?php
                    $nameParts = preg_split('/\s+/', trim($r['seeker_name']));
                    $initials  = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
                    $totalDays = (int) $r['total_days'];
                    $dateRange = $totalDays > 1
                        ? date('j M Y', strtotime($r['start_date'])) . ' – ' . date('j M Y', strtotime($r['end_date']))
                        : date('j F Y', strtotime($r['start_date']));
                    $formId = 'reject-form-' . (int) $r['id'];
                    ?>
                    <div class="owner-request-card">
                        <?php if ($r['image_path']): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($r['image_path']); ?>" alt="<?= htmlspecialchars($r['title']); ?>">
                        <?php else: ?>
                            <div class="img-fallback"><i class="bi bi-building"></i></div>
                        <?php endif; ?>

                        <div class="orc-body">
                            <div class="orc-top">
                                <div>
                                    <h3><?= htmlspecialchars($r['title']); ?></h3>
                                    <div class="orc-loc"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($r['city']); ?></div>
                                </div>
                                <span class="status-pill pending">Pending</span>
                            </div>

                            <div class="orc-seeker">
                                <div class="avatar-circle-xl orc-avatar"><?= htmlspecialchars($initials); ?></div>
                                <div>
                                    <strong>
                                        <?= htmlspecialchars($r['seeker_name']); ?>
                                        <?php if ($r['seeker_verified']): ?><i class="bi bi-patch-check-fill bd-verified"></i><?php endif; ?>
                                    </strong>
                                    <span>Requesting to book</span>
                                </div>
                            </div>

                            <div class="orc-meta">
                                <span><i class="bi bi-calendar3"></i> <?= $dateRange; ?></span>
                                <span><i class="bi bi-moon"></i> <?= $totalDays; ?> day<?= $totalDays > 1 ? '' : ''; ?><?= $totalDays === 1 ? '' : 's'; ?></span>
                                <span><strong>₹<?= number_format((float) $r['total_amount'], 0); ?></strong></span>
                            </div>

                            <div class="orc-purpose"><span>Purpose</span>
                                <p><?= htmlspecialchars($r['purpose']); ?></p>
                            </div>

                            <div class="orc-actions">
                                <form method="POST" onsubmit="return confirm('Accept this booking request?');">
                                    <input type="hidden" name="booking_id" value="<?= (int) $r['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-primary-avastra"><i class="bi bi-check-lg"></i> Accept</button>
                                </form>

                                <button type="button" class="btn btn-ghost-avastra" onclick="document.getElementById('<?= $formId; ?>').classList.toggle('open')">
                                    <i class="bi bi-x-lg"></i> Decline
                                </button>

                                <a href="<?= APP_URL; ?>/user/messages.php?with=<?= (int) $r['seeker_id']; ?>&space_id=<?= (int) $r['space_id']; ?>" class="btn btn-ghost-avastra">
                                    <i class="bi bi-chat"></i> Message
                                </a>
                            </div>

                            <div id="<?= $formId; ?>" class="orc-reject-form">
                                <form method="POST">
                                    <input type="hidden" name="booking_id" value="<?= (int) $r['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <label for="reason-<?= (int) $r['id']; ?>">Reason for declining (shown to the seeker)</label>
                                    <textarea id="reason-<?= (int) $r['id']; ?>" name="reason" rows="2" placeholder="e.g. Dates no longer available"></textarea>
                                    <button type="submit" class="btn btn-primary-avastra btn-sm">Confirm decline</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>