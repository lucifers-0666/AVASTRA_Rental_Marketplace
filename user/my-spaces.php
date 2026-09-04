<?php

/**
 * AVASTRA — My Spaces (owner management view)
 *
 * NOTE: there's no "draft" status in the schema — a listing becomes
 * `pending` the moment it's submitted via list-space.php. So the status
 * pill here only ever shows: Pending Review, Rejected, Published, Paused.
 */
$pageTitle = 'My Spaces';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

$actionMessage = '';
$actionError   = '';

/* -----------------------------------------------------------
   HANDLE PAUSE / RESUME / DELETE ACTIONS
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['space_id'])) {
    $spaceId = (int) $_POST['space_id'];

    // Always confirm this space actually belongs to the logged-in user before touching it.
    $ownCheck = $db->prepare("SELECT id, is_active FROM spaces WHERE id = :id AND owner_id = :uid");
    $ownCheck->execute([':id' => $spaceId, ':uid' => $userId]);
    $ownedSpace = $ownCheck->fetch();

    if (!$ownedSpace) {
        $actionError = "That listing doesn't belong to your account.";
    } elseif ($_POST['action'] === 'pause') {
        $db->prepare("UPDATE spaces SET is_active = 0 WHERE id = :id")->execute([':id' => $spaceId]);
        $actionMessage = 'Listing paused. It will no longer show up in search.';
    } elseif ($_POST['action'] === 'resume') {
        $db->prepare("UPDATE spaces SET is_active = 1 WHERE id = :id")->execute([':id' => $spaceId]);
        $actionMessage = 'Listing resumed and visible in search again.';
    } elseif ($_POST['action'] === 'delete') {
        $bookingCount = $db->prepare("SELECT COUNT(*) FROM bookings WHERE space_id = :id");
        $bookingCount->execute([':id' => $spaceId]);
        if ((int) $bookingCount->fetchColumn() > 0) {
            $actionError = "Can't delete a listing that has booking history — pause it instead.";
        } else {
            $db->prepare("DELETE FROM spaces WHERE id = :id")->execute([':id' => $spaceId]);
            $actionMessage = 'Listing deleted.';
        }
    }
}

/* -----------------------------------------------------------
   FETCH THIS OWNER'S SPACES
----------------------------------------------------------- */
$stmt = $db->prepare("
    SELECT s.*, c.name AS category_name,
           (SELECT image_path FROM space_images WHERE space_id = s.id AND is_primary = 1 LIMIT 1) AS image_path,
           (SELECT COUNT(*) FROM bookings WHERE space_id = s.id) AS booking_count
    FROM spaces s
    JOIN categories c ON s.category_id = c.id
    WHERE s.owner_id = :uid
    ORDER BY s.created_at DESC
");
$stmt->execute([':uid' => $userId]);
$spaces = $stmt->fetchAll();

function myspaceStatus(array $space): array
{
    if ($space['verification_status'] === 'pending')  return ['Pending Review', 'pending_review'];
    if ($space['verification_status'] === 'rejected') return ['Rejected', 'rejected'];
    if ((int) $space['is_active'] === 0)               return ['Paused', 'paused'];
    return ['Published', 'published'];
}

$unreadNotifCount = 0; // used by topbar.php
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <div class="greeting-row">
            <div>
                <h1>My Spaces</h1>
                <p><?= count($spaces); ?> listing<?= count($spaces) === 1 ? '' : 's'; ?></p>
            </div>
            <a href="<?= APP_URL; ?>/user/list-space.php" class="btn btn-primary-avastra"><i class="bi bi-plus-lg"></i> List a Space</a>
        </div>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="bp-alert success" style="margin-bottom:20px;"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_SESSION['flash_success']); ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <?php if ($actionMessage): ?>
            <div class="bp-alert success" style="margin-bottom:20px;"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($actionMessage); ?></div>
        <?php endif; ?>
        <?php if ($actionError): ?>
            <div class="bp-alert error" style="margin-bottom:20px;"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($actionError); ?></div>
        <?php endif; ?>

        <?php if (empty($spaces)): ?>
            <div class="empty-state">
                <i class="bi bi-building-add"></i>
                <h3>No spaces listed</h3>
                <p>Have unused space? Put it to work.</p>
                <a href="<?= APP_URL; ?>/user/list-space.php" class="btn btn-primary-avastra">List a Space</a>
            </div>
        <?php else: ?>
            <div class="myspace-list">
                <?php foreach ($spaces as $space): [$statusText, $statusClass] = myspaceStatus($space); ?>
                    <div class="myspace-row">

                        <?php if ($space['verification_status'] === 'approved'): ?>
                            <div class="dropdown msr-kebab">
                                <button type="button" data-bs-toggle="dropdown" aria-expanded="false">&#8942;</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form method="POST" action="">
                                            <input type="hidden" name="space_id" value="<?= (int) $space['id']; ?>">
                                            <?php if ((int) $space['is_active'] === 1): ?>
                                                <input type="hidden" name="action" value="pause">
                                                <button type="submit" class="dropdown-item"><i class="bi bi-pause-circle"></i> Pause listing</button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="resume">
                                                <button type="submit" class="dropdown-item"><i class="bi bi-play-circle"></i> Resume listing</button>
                                            <?php endif; ?>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="" onsubmit="return confirm('Delete this listing? This can\'t be undone.');">
                                            <input type="hidden" name="space_id" value="<?= (int) $space['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="dropdown msr-kebab">
                                <button type="button" data-bs-toggle="dropdown" aria-expanded="false">&#8942;</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form method="POST" action="" onsubmit="return confirm('Delete this listing? This can\'t be undone.');">
                                            <input type="hidden" name="space_id" value="<?= (int) $space['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($space['image_path']): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($space['image_path']); ?>" alt="<?= htmlspecialchars($space['title']); ?>">
                        <?php else: ?>
                            <div class="img-fallback"><i class="bi bi-building" style="font-size:24px;color:rgba(23,32,27,0.25);"></i></div>
                        <?php endif; ?>

                        <div class="msr-main">
                            <div class="msr-top">
                                <h3><?= htmlspecialchars($space['title']); ?></h3>
                                <span class="status-pill <?= $statusClass; ?>"><?= $statusText; ?></span>
                            </div>
                            <div class="msr-loc"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($space['city']); ?> · <?= htmlspecialchars($space['category_name']); ?></div>

                            <?php if ($space['verification_status'] === 'rejected' && $space['rejection_reason']): ?>
                                <div class="sd-note" style="color:#8a3324;margin-bottom:8px;">Reason: <?= htmlspecialchars($space['rejection_reason']); ?></div>
                            <?php endif; ?>

                            <div class="msr-bottom">
                                <?php if ($space['verification_status'] === 'pending'): ?>
                                    <div class="msr-under-review"><i class="bi bi-hourglass-split"></i> Under review — you'll be notified once approved.</div>
                                <?php else: ?>
                                    <div class="msr-stats">
                                        <div>
                                            <div class="msr-stat-label">Price</div>
                                            <div class="msr-stat-value">₹<?= number_format((float) $space['daily_rate'], 0); ?>/day</div>
                                        </div>
                                        <div>
                                            <div class="msr-stat-label">Bookings</div>
                                            <div class="msr-stat-value"><?= (int) $space['booking_count']; ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="msr-actions">
                                    <a href="<?= APP_URL; ?>/user/edit-space.php?id=<?= (int) $space['id']; ?>" class="btn btn-ghost-avastra"><i class="bi bi-pencil"></i> Edit</a>
                                    <a href="<?= APP_URL; ?>/user/space-availability.php?id=<?= (int) $space['id']; ?>" class="btn btn-ghost-avastra"><i class="bi bi-calendar-week"></i> Availability</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>