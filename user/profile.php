<?php

/**
 * AVASTRA — My Profile
 *
 * Two honest differences from the Figma mock, because the schema doesn't
 * back them:
 *  1. "Government ID" isn't in the profile-completion checklist — there's
 *     no column tracking identity-document upload on `users` yet.
 *  2. "Response rate / Response time" is replaced with real owner stats
 *     (spaces listed, bookings received) — nothing tracks message
 *     response speed in the schema, so that number would've been fake.
 */
$pageTitle = 'Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$initials = strtoupper(substr($user['full_name'], 0, 1) . substr(strrchr($user['full_name'], ' ') ?: '', 1, 1));

/* -----------------------------------------------------------
   PROFILE COMPLETION — only counts fields that really exist
----------------------------------------------------------- */
$checks = [
    'Profile photo'   => !empty($user['profile_image']) && $user['profile_image'] !== 'default-avatar.png',
    'Full name'       => !empty($user['full_name']),
    'Email verified'  => (bool) $user['email_verified'],
    'Phone number'    => !empty($user['phone']),
    'Location'        => !empty($user['city']) && !empty($user['state']),
];
$completedCount = count(array_filter($checks));
$percent        = (int) round($completedCount / count($checks) * 100);

/* -----------------------------------------------------------
   REAL "AS AN OWNER" STATS (only shown if they've listed something)
----------------------------------------------------------- */
$spaceCountStmt = $db->prepare("SELECT COUNT(*) FROM spaces WHERE owner_id = :id");
$spaceCountStmt->execute([':id' => $userId]);
$spaceCount = (int) $spaceCountStmt->fetchColumn();

$bookingReceivedStmt = $db->prepare("
    SELECT COUNT(*) FROM bookings b JOIN spaces s ON b.space_id = s.id WHERE s.owner_id = :id
");
$bookingReceivedStmt->execute([':id' => $userId]);
$bookingsReceived = (int) $bookingReceivedStmt->fetchColumn();

$unreadNotifCount = 0; // used by topbar.php
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <div class="greeting-row">
            <div>
                <h1>My Profile</h1>
            </div>
            <a href="<?= APP_URL; ?>/user/edit-profile.php" class="btn btn-ghost-avastra"><i class="bi bi-pencil"></i> Edit Profile</a>
        </div>

        <div class="profile-layout">
            <div>
                <!-- Identity card -->
                <div class="profile-card">
                    <div class="profile-id-row">
                        <div class="avatar-circle-xl"><?= htmlspecialchars($initials); ?></div>
                        <div>
                            <h3><?= htmlspecialchars($user['full_name']); ?></h3>
                            <div class="p-meta">
                                <?php if ($user['email_verified']): ?>
                                    <span class="p-verified"><i class="bi bi-shield-check"></i> Verified</span>
                                <?php endif; ?>
                                <span>Member since <?= date('F Y', strtotime($user['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact info card -->
                <div class="profile-card">
                    <h2>Contact Information</h2>

                    <div class="contact-row">
                        <i class="bi bi-envelope"></i>
                        <div style="flex:1;">
                            <div class="c-label">Email</div>
                            <div class="c-value"><?= htmlspecialchars($user['email']); ?></div>
                        </div>
                        <?php if ($user['email_verified']): ?>
                            <span class="c-badge"><i class="bi bi-check-circle-fill"></i> Verified</span>
                        <?php else: ?>
                            <span class="c-badge" style="color:#8a5a1b;"><i class="bi bi-exclamation-circle"></i> Not verified</span>
                        <?php endif; ?>
                    </div>

                    <div class="contact-row">
                        <i class="bi bi-telephone"></i>
                        <div style="flex:1;">
                            <div class="c-label">Phone</div>
                            <div class="c-value">
                                <?= $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="c-missing">Not added yet</span>'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="contact-row">
                        <i class="bi bi-geo-alt"></i>
                        <div style="flex:1;">
                            <div class="c-label">Location</div>
                            <div class="c-value">
                                <?php if ($user['city'] && $user['state']): ?>
                                    <?= htmlspecialchars($user['city'] . ', ' . $user['state']); ?>
                                <?php else: ?>
                                    <span class="c-missing">Not added yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <!-- Profile completion card -->
                <div class="profile-card">
                    <h2>Profile completion</h2>
                    <div class="completion-percent"><?= $percent; ?>%</div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?= $percent; ?>%"></div>
                    </div>

                    <?php foreach ($checks as $label => $done): ?>
                        <div class="checklist-item <?= $done ? 'done' : 'todo'; ?>">
                            <i class="bi <?= $done ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                            <?= htmlspecialchars($label); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Owner stats card (only if they've listed a space) -->
                <?php if ($spaceCount > 0): ?>
                    <div class="profile-card">
                        <h2>As an owner</h2>
                        <div class="owner-stat-row"><span>Spaces listed</span><strong><?= $spaceCount; ?></strong></div>
                        <div class="owner-stat-row"><span>Bookings received</span><strong><?= $bookingsReceived; ?></strong></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>