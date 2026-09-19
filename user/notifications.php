<?php

/**
 * AVASTRA — User Notifications Center
 */

$pageTitle = 'Notifications';

require_once __DIR__ . '/../classes/Auth.php';

Auth::initSession();
Auth::requireLogin();

$currentUser = Auth::getUser();

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

/*
|--------------------------------------------------------------------------
| MARK ALL AS READ
| IMPORTANT: This must happen BEFORE header.php/sidebar.php output.
|--------------------------------------------------------------------------
*/
if (isset($_GET['action']) && $_GET['action'] === 'read_all') {

    $markStmt = $db->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = :uid
    ");

    $markStmt->execute([
        ':uid' => $userId
    ]);

    header("Location: " . APP_URL . "/user/notifications.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH NOTIFICATIONS
|--------------------------------------------------------------------------
*/
$stmt = $db->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 50
");

$stmt->execute([
    ':uid' => $userId
]);

$notifications = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/
$totalNotifications = count($notifications);

$unreadCount = 0;

foreach ($notifications as $notification) {
    if ((int) $notification['is_read'] === 0) {
        $unreadCount++;
    }
}

/*
|--------------------------------------------------------------------------
| NOTIFICATION ICON / STYLE
|--------------------------------------------------------------------------
|
| We detect the notification from its title/message so this works
| without requiring a new database column.
|--------------------------------------------------------------------------
*/
function getNotificationStyle(array $notification): array
{
    $text = strtolower(
        ($notification['title'] ?? '') . ' ' .
            ($notification['message'] ?? '')
    );

    /*
    | Booking
    */
    if (
        str_contains($text, 'booking') ||
        str_contains($text, 'booked') ||
        str_contains($text, 'reservation')
    ) {
        return [
            'icon'  => 'bi-calendar-check-fill',
            'class' => 'notification-booking',
            'label' => 'Booking'
        ];
    }

    /*
    | Request
    */
    if (
        str_contains($text, 'request') ||
        str_contains($text, 'approved') ||
        str_contains($text, 'rejected') ||
        str_contains($text, 'accepted')
    ) {
        return [
            'icon'  => 'bi-file-earmark-check-fill',
            'class' => 'notification-request',
            'label' => 'Request'
        ];
    }

    /*
    | Message
    */
    if (
        str_contains($text, 'message') ||
        str_contains($text, 'replied') ||
        str_contains($text, 'chat')
    ) {
        return [
            'icon'  => 'bi-chat-left-text-fill',
            'class' => 'notification-message',
            'label' => 'Message'
        ];
    }

    /*
    | Space / Listing
    */
    if (
        str_contains($text, 'space') ||
        str_contains($text, 'listing') ||
        str_contains($text, 'listed') ||
        str_contains($text, 'published')
    ) {
        return [
            'icon'  => 'bi-building-fill',
            'class' => 'notification-space',
            'label' => 'Space'
        ];
    }

    /*
    | Payment
    */
    if (
        str_contains($text, 'payment') ||
        str_contains($text, 'paid') ||
        str_contains($text, 'refund')
    ) {
        return [
            'icon'  => 'bi-credit-card-fill',
            'class' => 'notification-payment',
            'label' => 'Payment'
        ];
    }

    /*
    | Default
    */
    return [
        'icon'  => 'bi-bell-fill',
        'class' => 'notification-general',
        'label' => 'Update'
    ];
}

/*
|--------------------------------------------------------------------------
| TIME FORMAT
|--------------------------------------------------------------------------
*/
function notificationTime(string $date): string
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return '';
    }

    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'Just now';
    }

    if ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' min ago';
    }

    if ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    }

    if ($difference < 172800) {
        return 'Yesterday, ' . date('g:i A', $timestamp);
    }

    return date('d M Y, g:i A', $timestamp);
}

$unreadNotifCount = $unreadCount;

/*
|--------------------------------------------------------------------------
| START PAGE OUTPUT
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

?>

<div id="user-main">

    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->
        <div class="notifications-page-header">

            <div class="notifications-heading">

                <div class="notifications-title-row">

                    <div class="notifications-title-icon">
                        <i class="bi bi-bell-fill"></i>
                    </div>

                    <div>
                        <h1>Notifications</h1>

                        <p>
                            Stay updated with your bookings, requests,
                            spaces and messages.
                        </p>
                    </div>

                </div>

            </div>

            <?php if ($unreadCount > 0): ?>

                <a
                    href="<?= APP_URL; ?>/user/notifications.php?action=read_all"
                    class="notifications-mark-read">
                    <i class="bi bi-check2-all"></i>
                    Mark all as read
                </a>

            <?php endif; ?>

        </div>


        <!-- =====================================================
             SUMMARY
        ====================================================== -->
        <div class="notifications-summary">

            <div class="notification-summary-card">

                <div class="notification-summary-icon">
                    <i class="bi bi-bell"></i>
                </div>

                <div>
                    <strong><?= $totalNotifications; ?></strong>
                    <span>Total notifications</span>
                </div>

            </div>


            <div class="notification-summary-card unread-summary">

                <div class="notification-summary-icon">
                    <i class="bi bi-envelope"></i>
                </div>

                <div>
                    <strong><?= $unreadCount; ?></strong>
                    <span>Unread notifications</span>
                </div>

            </div>

        </div>


        <!-- =====================================================
             NOTIFICATION LIST
        ====================================================== -->
        <div class="notifications-card">

            <div class="notifications-card-header">

                <div>
                    <h2>Recent activity</h2>
                    <p>Your latest AVASTRA updates</p>
                </div>

                <?php if ($totalNotifications > 0): ?>

                    <span class="notifications-count">
                        <?= $totalNotifications; ?>
                        <?= $totalNotifications === 1 ? 'notification' : 'notifications'; ?>
                    </span>

                <?php endif; ?>

            </div>


            <?php if (empty($notifications)): ?>

                <!-- =================================================
                     EMPTY STATE
                ================================================== -->
                <div class="notifications-empty">

                    <div class="notifications-empty-icon">
                        <i class="bi bi-bell-slash"></i>
                    </div>

                    <h3>You're all caught up</h3>

                    <p>
                        You don't have any notifications right now.
                        We'll let you know when something important
                        happens with your bookings or spaces.
                    </p>

                    <a
                        href="<?= APP_URL; ?>/user/find-spaces.php"
                        class="notifications-empty-btn">
                        <i class="bi bi-search"></i>
                        Explore Spaces
                    </a>

                </div>

            <?php else: ?>

                <div class="notifications-list">

                    <?php foreach ($notifications as $notification): ?>

                        <?php

                        $notificationStyle =
                            getNotificationStyle($notification);

                        $isUnread =
                            (int) $notification['is_read'] === 0;

                        ?>

                        <div
                            class="notification-item <?= $isUnread ? 'is-unread' : 'is-read'; ?>">

                            <!-- Icon -->
                            <div
                                class="notification-icon <?= $notificationStyle['class']; ?>">
                                <i class="bi <?= $notificationStyle['icon']; ?>"></i>
                            </div>


                            <!-- Content -->
                            <div class="notification-content">

                                <div class="notification-top">

                                    <div class="notification-title-wrap">

                                        <?php if ($isUnread): ?>

                                            <span class="notification-unread-dot"></span>

                                        <?php endif; ?>

                                        <h3>
                                            <?= htmlspecialchars(
                                                $notification['title']
                                            ); ?>
                                        </h3>

                                    </div>

                                    <span class="notification-time">
                                        <i class="bi bi-clock"></i>
                                        <?= notificationTime(
                                            $notification['created_at']
                                        ); ?>
                                    </span>

                                </div>


                                <p class="notification-message">
                                    <?= htmlspecialchars(
                                        $notification['message']
                                    ); ?>
                                </p>


                                <div class="notification-meta">

                                    <span class="notification-type">
                                        <?= $notificationStyle['label']; ?>
                                    </span>

                                    <?php if ($isUnread): ?>

                                        <span class="notification-new">
                                            New
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

</div>