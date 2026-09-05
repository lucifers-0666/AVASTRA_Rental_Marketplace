<?php
/** AVASTRA — Owner availability calendar */
$pageTitle = 'Manage Availability';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$userId = (int) $currentUser['id'];
$spaceId = (int) ($_GET['id'] ?? $_POST['space_id'] ?? 0);
$monthInput = $_GET['month'] ?? $_POST['month'] ?? date('Y-m');
$month = DateTimeImmutable::createFromFormat('!Y-m', $monthInput) ?: new DateTimeImmutable('first day of this month');
$month = $month->modify('first day of this month');
$monthKey = $month->format('Y-m');
$message = ''; $error = '';

$spaceStmt = $db->prepare('SELECT id, title FROM spaces WHERE id = :id AND owner_id = :user LIMIT 1');
$spaceStmt->execute([':id' => $spaceId, ':user' => $userId]);
$space = $spaceStmt->fetch();

function hasBooking(PDO $db, int $spaceId, string $date): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE space_id = :space AND status IN ('confirmed','active','completed') AND start_date <= :date AND end_date >= :date");
    $stmt->execute([':space' => $spaceId, ':date' => $date]); return (int) $stmt->fetchColumn() > 0;
}
function hasPending(PDO $db, int $spaceId, string $date): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE space_id = :space AND status = 'pending' AND start_date <= :date AND end_date >= :date");
    $stmt->execute([':space' => $spaceId, ':date' => $date]); return (int) $stmt->fetchColumn() > 0;
}

if ($space && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $date = $_POST['date'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $error = 'Please choose a valid date.';
        elseif (hasBooking($db, $spaceId, $date) || hasPending($db, $spaceId, $date)) $error = 'Booked and pending-request dates cannot be changed.';
        else {
            $existing = $db->prepare('SELECT id FROM space_availability WHERE space_id = :space AND is_blocked = 1 AND start_date <= :date AND end_date >= :date LIMIT 1');
            $existing->execute([':space' => $spaceId, ':date' => $date]);
            if ($block = $existing->fetch()) { $db->prepare('DELETE FROM space_availability WHERE id = :id')->execute([':id' => $block['id']]); $message = 'Date unblocked.'; }
            else { $db->prepare('INSERT INTO space_availability (space_id, start_date, end_date, is_blocked, notes) VALUES (:space, :date, :date, 1, :note)')->execute([':space' => $spaceId, ':date' => $date, ':note' => 'Blocked by owner']); $message = 'Date blocked.'; }
        }
    } elseif ($action === 'range') {
        $from = $_POST['from_date'] ?? ''; $to = $_POST['to_date'] ?? '';
        if (!$from || !$to || $to < $from) $error = 'Choose a valid start and end date.';
        else { $db->prepare('INSERT INTO space_availability (space_id, start_date, end_date, is_blocked, notes) VALUES (:space, :from, :to, 1, :note)')->execute([':space'=>$spaceId, ':from'=>$from, ':to'=>$to, ':note'=>'Blocked by owner']); $message='Date range blocked.'; }
    } elseif ($action === 'clear') {
        $db->prepare('DELETE FROM space_availability WHERE space_id = :space AND is_blocked = 1')->execute([':space'=>$spaceId]); $message = 'All owner blocks removed.';
    }
}

$unreadNotifCount = 0;
if (!$space):
?>
<div id="user-main"><?php require_once __DIR__ . '/includes/topbar.php'; ?><main id="user-content"><div class="empty-state"><i class="bi bi-calendar-x"></i><h3>Space not found</h3><p>This listing is not available in your account.</p><a class="btn btn-primary-avastra" href="<?= APP_URL ?>/user/my-spaces.php">Back to My Spaces</a></div></main><?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php exit; endif;

$start = $month; $end = $month->modify('last day of this month');
$rangeStart = $start->format('Y-m-d'); $rangeEnd = $end->format('Y-m-d');
$bookingStmt = $db->prepare("SELECT start_date, end_date, status FROM bookings WHERE space_id = :space AND status IN ('pending','confirmed','active','completed') AND start_date <= :end AND end_date >= :start");
$bookingStmt->execute([':space'=>$spaceId, ':start'=>$rangeStart, ':end'=>$rangeEnd]); $bookingRanges = $bookingStmt->fetchAll();
$blockStmt = $db->prepare('SELECT start_date, end_date FROM space_availability WHERE space_id = :space AND is_blocked = 1 AND start_date <= :end AND end_date >= :start');
$blockStmt->execute([':space'=>$spaceId, ':start'=>$rangeStart, ':end'=>$rangeEnd]); $blockRanges = $blockStmt->fetchAll();
function rangeState(array $ranges, string $date, string $status = ''): bool { foreach ($ranges as $range) if (($status === '' || $range['status'] === $status) && $range['start_date'] <= $date && $range['end_date'] >= $date) return true; return false; }
$firstOffset = (int) $start->format('w'); $days = (int) $end->format('j');
?>
<div id="user-main"><?php require_once __DIR__ . '/includes/topbar.php'; ?>
<main id="user-content" class="availability-page">
    <a class="bd-back" href="<?= APP_URL ?>/user/my-spaces.php"><i class="bi bi-arrow-left"></i> Back to My Spaces</a>
    <h1>Availability</h1><p class="availability-space-name"><?= htmlspecialchars($space['title']) ?></p>
    <?php if ($message): ?><div class="settings-alert success"><i class="bi bi-check-circle"></i><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($error): ?><div class="settings-alert error"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="availability-layout"><section class="availability-calendar-card"><div class="availability-month"><a href="?id=<?= $spaceId ?>&month=<?= $month->modify('-1 month')->format('Y-m') ?>"><i class="bi bi-chevron-left"></i></a><h2><?= $month->format('F Y') ?></h2><a href="?id=<?= $spaceId ?>&month=<?= $month->modify('+1 month')->format('Y-m') ?>"><i class="bi bi-chevron-right"></i></a></div><div class="calendar-weekdays"><?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $weekday): ?><span><?= $weekday ?></span><?php endforeach ?></div><div class="calendar-grid"><?php for($slot=0;$slot<$firstOffset+$days;$slot++): ?><?php if($slot<$firstOffset): ?><span class="calendar-empty"></span><?php else: ?><?php $day=$slot-$firstOffset+1;$date=$month->format('Y-m-').str_pad((string)$day,2,'0',STR_PAD_LEFT);$booked=rangeState($bookingRanges,$date,'confirmed')||rangeState($bookingRanges,$date,'active')||rangeState($bookingRanges,$date,'completed');$pending=rangeState($bookingRanges,$date,'pending');$blocked=rangeState($blockRanges,$date);$state=$booked?'booked':($pending?'pending':($blocked?'blocked':'available'));$today=$date===date('Y-m-d'); ?><form method="post" class="calendar-day-form"><input type="hidden" name="space_id" value="<?= $spaceId ?>"><input type="hidden" name="month" value="<?= $monthKey ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="date" value="<?= $date ?>"><button class="calendar-day <?= $state ?> <?= $today?'today':'' ?>" <?= ($booked||$pending)?'disabled':'' ?> title="<?= ucfirst($state) ?>"><?= $day ?></button></form><?php endif ?><?php endfor ?></div></section>
    <aside class="availability-side"><section class="availability-card"><h2>Legend</h2><div><i class="legend-swatch booked"></i>Booked</div><div><i class="legend-swatch pending"></i>Pending request</div><div><i class="legend-swatch blocked"></i>Blocked (unavailable)</div><div><i class="legend-swatch today"></i>Today</div><div><i class="legend-swatch available"></i>Available</div></section><section class="availability-card"><h2>Quick actions</h2><button class="btn btn-ghost-avastra availability-action" type="button" data-bs-toggle="collapse" data-bs-target="#rangeBlock"><i class="bi bi-plus-lg"></i> Block a date range</button><div class="collapse" id="rangeBlock"><form method="post" class="availability-range"><input type="hidden" name="space_id" value="<?= $spaceId ?>"><input type="hidden" name="month" value="<?= $monthKey ?>"><input type="hidden" name="action" value="range"><label>From<input required type="date" name="from_date"></label><label>Until<input required type="date" name="to_date"></label><button class="btn btn-primary-avastra" type="submit">Block range</button></form></div><form method="post" onsubmit="return confirm('Remove all owner blocks for this space?');"><input type="hidden" name="space_id" value="<?= $spaceId ?>"><input type="hidden" name="month" value="<?= $monthKey ?>"><input type="hidden" name="action" value="clear"><button class="btn btn-ghost-avastra availability-action" type="submit"><i class="bi bi-x-lg"></i> Remove all blocks</button></form><p>Click an available date to block or unblock it. Booked and pending dates cannot be changed.</p></section></aside></div>
</main><?php require_once __DIR__ . '/includes/footer.php'; ?>
