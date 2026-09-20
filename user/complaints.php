<?php

/**
 * AVASTRA — User Complaints & Dispute Tickets
 */
$pageTitle = 'My Complaints';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

$compSql = "
    SELECT c.*, b.booking_code, b.start_date, b.end_date, s.id AS space_id, s.title AS space_title
    FROM complaints c
    JOIN bookings b ON c.booking_id = b.id
    JOIN spaces s ON b.space_id = s.id
    WHERE c.user_id = :uid
    ORDER BY c.created_at DESC
";
$stmt = $db->prepare($compSql);
$stmt->execute([':uid' => $userId]);
$complaints = $stmt->fetchAll();

$totalCount = count($complaints);
$openCount = 0;
$resolvedCount = 0;

foreach ($complaints as $c) {
    if (in_array($c['status'], ['open', 'in_progress'], true)) {
        $openCount++;
    } elseif ($c['status'] === 'resolved') {
        $resolvedCount++;
    }
}

$unreadNotifCount = 0;
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main id="user-content">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 style="font-family:'DM Serif Display',serif; font-size:28px; color:var(--avastra-dark); margin:0;">Support &amp; Complaints</h1>
                <p class="text-secondary mb-0" style="font-size:14px;">Track your reported disputes, booking issues, and admin resolution updates.</p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge" style="background:var(--avastra-light); color:var(--avastra-primary); font-size:13px; font-weight:600; padding:8px 14px; border-radius:20px; border:1px solid rgba(20,92,74,0.15);">
                    Total: <?= $totalCount; ?>
                </span>
                <span class="badge" style="background:#FFF3CD; color:#856404; font-size:13px; font-weight:600; padding:8px 14px; border-radius:20px;">
                    Active: <?= $openCount; ?>
                </span>
                <span class="badge" style="background:#E7F5EC; color:var(--avastra-primary); font-size:13px; font-weight:600; padding:8px 14px; border-radius:20px;">
                    Resolved: <?= $resolvedCount; ?>
                </span>
            </div>
        </div>

        <?php if (empty($complaints)): ?>
            <div class="empty-state">
                <i class="bi bi-shield-check" style="color:var(--avastra-primary);"></i>
                <h3>No issues or complaints filed</h3>
                <p>All your rental interactions are running smoothly. If you ever face an issue with a space or booking, you can submit a ticket from the Booking Details page.</p>
                <a href="<?= APP_URL; ?>/user/my-bookings.php" class="btn btn-primary-avastra">View My Bookings</a>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($complaints as $c): ?>
                    <?php
                    $statusConfig = match ($c['status']) {
                        'open' => ['label' => 'Open', 'class' => 'bg-warning-subtle text-warning-emphasis', 'icon' => 'bi-hourglass-split'],
                        'in_progress' => ['label' => 'In Progress', 'class' => 'bg-info-subtle text-info-emphasis', 'icon' => 'bi-gear-wide-connected'],
                        'resolved' => ['label' => 'Resolved', 'class' => 'bg-success-subtle text-success-emphasis', 'icon' => 'bi-check-circle-fill'],
                        'closed' => ['label' => 'Closed', 'class' => 'bg-secondary-subtle text-secondary-emphasis', 'icon' => 'bi-x-circle'],
                        default => ['label' => ucfirst($c['status']), 'class' => 'bg-secondary-subtle text-secondary-emphasis', 'icon' => 'bi-info-circle']
                    };
                    ?>
                    <div class="p-4" style="background:#fff; border:1px solid var(--avastra-border); border-radius:14px; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div>
                                <span class="text-muted small">Ticket #TKT-<?= str_pad((string)$c['id'], 5, '0', STR_PAD_LEFT); ?> · Booking <strong><?= htmlspecialchars($c['booking_code']); ?></strong></span>
                                <h3 style="font-size:18px; font-weight:600; color:var(--avastra-dark); margin:4px 0 0 0;"><?= htmlspecialchars($c['subject']); ?></h3>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars($c['space_title']); ?>
                                </div>
                            </div>
                            <span class="badge rounded-pill <?= $statusConfig['class']; ?>" style="font-size:12px; font-weight:600; padding:6px 12px;">
                                <i class="bi <?= $statusConfig['icon']; ?> me-1"></i> <?= $statusConfig['label']; ?>
                            </span>
                        </div>

                        <div class="p-3 my-3" style="background:var(--avastra-light); border-radius:8px; font-size:14px; color:#374151; line-height:1.6;">
                            <?= nl2br(htmlspecialchars($c['description'])); ?>
                        </div>

                        <?php if (!empty($c['resolution_notes'])): ?>
                            <div class="p-3 mb-3" style="background:#F0FDF4; border-left:4px solid var(--avastra-primary); border-radius:6px;">
                                <div class="d-flex align-items-center gap-2 mb-1" style="color:var(--avastra-primary); font-weight:600; font-size:13px;">
                                    <i class="bi bi-shield-check"></i> AVASTRA Operations Team Resolution Note:
                                </div>
                                <p class="mb-0 text-secondary" style="font-size:13px; line-height:1.6;"><?= nl2br(htmlspecialchars($c['resolution_notes'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center pt-2" style="border-top:1px solid var(--avastra-border); font-size:13px;">
                            <span class="text-muted"><i class="bi bi-clock"></i> Filed on <?= date('j M Y, g:i A', strtotime($c['created_at'])); ?></span>
                            <a href="<?= APP_URL; ?>/user/booking-details.php?id=<?= (int)$c['booking_id']; ?>" class="btn btn-ghost-avastra btn-sm">
                                View Booking <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
