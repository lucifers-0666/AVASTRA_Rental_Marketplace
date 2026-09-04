<?php

/**
 * AVASTRA — User Dashboard (Overview)
 * The authenticated landing page for a Seeker+Owner account.
 */
$pageTitle = 'Overview';
require_once __DIR__ . '/includes/header.php';   // Auth::requireLogin() happens in here
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

/* -----------------------------------------------------------
   SUMMARY CARD COUNTS
----------------------------------------------------------- */
$stmt = $db->prepare("
    SELECT COUNT(*) FROM bookings
    WHERE seeker_id = :uid AND status IN ('pending','confirmed') AND start_date >= CURDATE()
");
$stmt->execute([':uid' => $userId]);
$upcomingBookingsCount = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE seeker_id = :uid AND status = 'pending'");
$stmt->execute([':uid' => $userId]);
$pendingRequestsCount = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM spaces WHERE owner_id = :uid AND is_active = 1");
$stmt->execute([':uid' => $userId]);
$activeSpacesCount = (int) $stmt->fetchColumn();

// NOTE: there is no `messages` table in db/schema.sql yet, so this is
// hardcoded to 0 for now. Wire this up once a messaging table exists.
$unreadMessagesCount = 0;
$unreadNotifCount    = 0; // used by includes/topbar.php for the bell dot

/* -----------------------------------------------------------
   NEXT UPCOMING BOOKING (as seeker)
----------------------------------------------------------- */
$stmt = $db->prepare("
    SELECT b.*, s.title, s.city, s.address,
           (SELECT image_path FROM space_images WHERE space_id = s.id AND is_primary = 1 LIMIT 1) AS image_path
    FROM bookings b
    JOIN spaces s ON b.space_id = s.id
    WHERE b.seeker_id = :uid AND b.status IN ('pending','confirmed') AND b.start_date >= CURDATE()
    ORDER BY b.start_date ASC
    LIMIT 1
");
$stmt->execute([':uid' => $userId]);
$upcomingBooking = $stmt->fetch();

/* -----------------------------------------------------------
   RECENT ACTIVITY — built from real booking + space events
   (there's no dedicated "activity feed" table yet, so this
   reads the two tables that actually change over time)
----------------------------------------------------------- */
$stmt = $db->prepare("
    (SELECT CONCAT('Booking ', b.status, ' — ', s.title) AS activity_text,
            b.status AS activity_status, b.updated_at AS activity_time
     FROM bookings b JOIN spaces s ON b.space_id = s.id
     WHERE b.seeker_id = :uid1)
    UNION ALL
    (SELECT CONCAT('Space listing ',
            CASE sp.verification_status
                WHEN 'approved' THEN 'approved'
                WHEN 'rejected' THEN 'rejected'
                ELSE 'submitted for review'
            END, ' — ', sp.title) AS activity_text,
            sp.verification_status AS activity_status, sp.updated_at AS activity_time
     FROM spaces sp
     WHERE sp.owner_id = :uid2)
    ORDER BY activity_time DESC
    LIMIT 4
");
$stmt->execute([':uid1' => $userId, ':uid2' => $userId]);
$recentActivity = $stmt->fetchAll();

/* -----------------------------------------------------------
   MY SPACES (as owner) — latest 3
----------------------------------------------------------- */
$stmt = $db->prepare("
    SELECT s.*, c.name AS category_name,
           (SELECT COUNT(*) FROM bookings WHERE space_id = s.id) AS booking_count,
           (SELECT image_path FROM space_images WHERE space_id = s.id AND is_primary = 1 LIMIT 1) AS image_path
    FROM spaces s
    JOIN categories c ON s.category_id = c.id
    WHERE s.owner_id = :uid
    ORDER BY s.created_at DESC
    LIMIT 3
");
$stmt->execute([':uid' => $userId]);
$mySpaces = $stmt->fetchAll();

/* Helpers to turn DB values into the little status pills */
function spaceStatusLabel(array $space): array
{
    if ($space['verification_status'] === 'pending')  return ['Pending Review', 'pending_review'];
    if ($space['verification_status'] === 'rejected') return ['Rejected', 'rejected'];
    if ((int) $space['is_active'] === 0)               return ['Paused', 'paused'];
    return ['Published', 'published'];
}
function activityIcon(string $status): string
{
    return match ($status) {
        'confirmed', 'approved'            => 'bi-check-lg',
        'pending'                          => 'bi-send',
        'completed'                        => 'bi-flag',
        'cancelled', 'rejected'            => 'bi-x-lg',
        default                            => 'bi-clock-history',
    };
}
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)   return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return date('d M, g:i A', strtotime($datetime));
}
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <!-- Greeting -->
        <?php
        $hour = (int) date('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', trim($currentUser['full_name']))[0];
        ?>
        <div class="greeting-row">
            <div>
                <h1><?= htmlspecialchars($greeting . ', ' . $firstName); ?>.</h1>
                <p>Manage your spaces, bookings and requests from one place.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Find a Space</a>
                <a href="<?= APP_URL; ?>/user/list-space.php" class="btn btn-ghost-avastra">List a Space</a>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="summary-grid">
            <a href="<?= APP_URL; ?>/user/my-bookings.php" class="summary-card" style="color:inherit;">
                <div class="top-row"><i class="bi bi-calendar-check icon"></i><i class="bi bi-arrow-right"></i></div>
                <div class="count"><?= $upcomingBookingsCount; ?></div>
                <div class="label">Upcoming Bookings</div>
            </a>
            <a href="<?= APP_URL; ?>/user/my-requests.php" class="summary-card" style="color:inherit;">
                <div class="top-row"><i class="bi bi-file-earmark-text icon"></i><i class="bi bi-arrow-right"></i></div>
                <div class="count"><?= $pendingRequestsCount; ?></div>
                <div class="label">Pending Requests</div>
            </a>
            <a href="<?= APP_URL; ?>/user/my-spaces.php" class="summary-card" style="color:inherit;">
                <div class="top-row"><i class="bi bi-building icon"></i><i class="bi bi-arrow-right"></i></div>
                <div class="count"><?= $activeSpacesCount; ?></div>
                <div class="label">Active Spaces</div>
            </a>
            <a href="<?= APP_URL; ?>/user/messages.php" class="summary-card" style="color:inherit;">
                <div class="top-row"><i class="bi bi-chat-dots icon"></i><i class="bi bi-arrow-right"></i></div>
                <div class="count"><?= $unreadMessagesCount; ?></div>
                <div class="label">Unread Messages</div>
            </a>
        </div>

        <!-- Upcoming booking + Recent activity -->
        <div class="dashboard-grid">
            <div>
                <div class="section-row">
                    <h2>Upcoming Booking</h2>
                    <a href="<?= APP_URL; ?>/user/my-bookings.php">View all</a>
                </div>

                <?php if ($upcomingBooking): ?>
                    <?php
                    $totalDays = (int) $upcomingBooking['total_days'];
                    $dateRange = $totalDays > 1
                        ? date('j M', strtotime($upcomingBooking['start_date'])) . ' – ' . date('j M Y', strtotime($upcomingBooking['end_date']))
                        : date('j F Y', strtotime($upcomingBooking['start_date']));
                    ?>
                    <div class="booking-card">
                        <?php if ($upcomingBooking['image_path']): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($upcomingBooking['image_path']); ?>" alt="<?= htmlspecialchars($upcomingBooking['title']); ?>">
                        <?php else: ?>
                            <div style="width:180px;background:var(--paper);display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-building" style="font-size:28px;color:rgba(23,32,27,0.25);"></i>
                            </div>
                        <?php endif; ?>
                        <div class="b-body">
                            <div class="b-top">
                                <div>
                                    <h3><?= htmlspecialchars($upcomingBooking['title']); ?></h3>
                                    <div class="b-loc"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($upcomingBooking['city']); ?></div>
                                </div>
                                <span class="status-pill <?= htmlspecialchars($upcomingBooking['status']); ?>"><?= htmlspecialchars($upcomingBooking['status']); ?></span>
                            </div>
                            <div class="b-meta">
                                <span><i class="bi bi-calendar3"></i> <?= $dateRange; ?></span>
                                <span><i class="bi bi-moon"></i> <?= $totalDays; ?> day<?= $totalDays > 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="b-bottom">
                                <span class="b-price">₹<?= number_format((float) $upcomingBooking['total_amount'], 0); ?></span>
                                <a href="<?= APP_URL; ?>/user/booking-details.php?id=<?= (int) $upcomingBooking['id']; ?>" class="b-view">View Booking <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <h3>No upcoming bookings</h3>
                        <p>Find a space for your next plan.</p>
                        <a href="<?= APP_URL; ?>/user/find-spaces.php" class="btn btn-primary-avastra">Find a Space</a>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="section-row">
                    <h2>Recent Activity</h2>
                </div>
                <div class="activity-list">
                    <?php if (empty($recentActivity)): ?>
                        <div class="activity-item">
                            <div class="a-icon"><i class="bi bi-info-circle"></i></div>
                            <div>
                                <div class="a-text">No activity yet — this fills up once you book or list a space.</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $item): ?>
                            <div class="activity-item">
                                <div class="a-icon"><i class="bi <?= activityIcon($item['activity_status']); ?>"></i></div>
                                <div>
                                    <div class="a-text"><?= htmlspecialchars($item['activity_text']); ?></div>
                                    <div class="a-time"><?= timeAgo($item['activity_time']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Spaces -->
        <div class="section-row">
            <h2>My Spaces</h2>
            <a href="<?= APP_URL; ?>/user/my-spaces.php">Manage spaces</a>
        </div>

        <?php if (empty($mySpaces)): ?>
            <div class="empty-state">
                <i class="bi bi-building-add"></i>
                <h3>No spaces listed</h3>
                <p>Have unused space? Put it to work.</p>
                <a href="<?= APP_URL; ?>/user/list-space.php" class="btn btn-primary-avastra">List a Space</a>
            </div>
        <?php else: ?>
            <div class="space-grid">
                <?php foreach ($mySpaces as $space): [$statusText, $statusClass] = spaceStatusLabel($space); ?>
                    <a href="<?= APP_URL; ?>/user/my-spaces.php?id=<?= (int) $space['id']; ?>" class="space-card" style="color:inherit;">
                        <?php if ($space['image_path']): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($space['image_path']); ?>" alt="<?= htmlspecialchars($space['title']); ?>">
                        <?php else: ?>
                            <div style="height:110px;background:var(--paper);display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-building" style="font-size:22px;color:rgba(23,32,27,0.25);"></i>
                            </div>
                        <?php endif; ?>
                        <div class="s-body">
                            <h3><?= htmlspecialchars($space['title']); ?></h3>
                            <div class="s-type"><?= htmlspecialchars($space['category_name']); ?></div>
                            <div class="s-bottom">
                                <span class="status-pill <?= $statusClass; ?>"><?= $statusText; ?></span>
                                <span class="s-bookings"><?= (int) $space['booking_count']; ?> bookings</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>