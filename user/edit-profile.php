<?php

/**
 * AVASTRA — Edit Profile
 *
 * Matches profile.php's data model:
 *  - Email is NOT editable here (email_verified would need to be reset
 *    and re-verified, which is a separate flow — out of scope for this page).
 *  - Government ID is not handled (no column for it on `users` yet,
 *    same reasoning as profile.php).
 */
$pageTitle = 'Edit Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$errors  = [];

/* -----------------------------------------------------------
   HANDLE FORM SUBMIT
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $state    = trim($_POST['state'] ?? '');
    $profileImage = $user['profile_image']; // keep existing unless a new one is uploaded

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
        $errors[] = 'Enter a valid phone number.';
    }

    /* --- Profile photo upload (optional) --- */
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $type    = $_FILES['profile_image']['type'];
        $size    = $_FILES['profile_image']['size'];

        if (!in_array($type, $allowed, true)) {
            $errors[] = 'Profile photo must be JPG, PNG, or WEBP.';
        } elseif ($size > 2 * 1024 * 1024) {
            $errors[] = 'Profile photo must be under 2MB.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext      = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $userId . '_' . time() . '.' . $ext;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $filename)) {
                $profileImage = 'uploads/profiles/' . $filename;
            } else {
                $errors[] = 'Failed to upload photo. Please try again.';
            }
        }
    }

    /* --- Save if no errors --- */
    if (empty($errors)) {
        $update = $db->prepare("
            UPDATE users
            SET full_name = :full_name,
                phone = :phone,
                city = :city,
                state = :state,
                profile_image = :profile_image
            WHERE id = :id
        ");
        $update->execute([
            ':full_name'     => $fullName,
            ':phone'         => $phone,
            ':city'          => $city,
            ':state'         => $state,
            ':profile_image' => $profileImage,
            ':id'            => $userId,
        ]);

        header('Location: ' . APP_URL . '/user/profile.php');
        exit();
    }

    // re-fill with what the user just typed, not stale DB data, so errors don't wipe the form
    $user['full_name']     = $fullName;
    $user['phone']         = $phone;
    $user['city']          = $city;
    $user['state']         = $state;
    $user['profile_image'] = $profileImage;
}

$initials = strtoupper(substr($user['full_name'], 0, 1) . substr(strrchr($user['full_name'], ' ') ?: '', 1, 1));
$unreadNotifCount = 0; // used by topbar.php

/* -----------------------------------------------------------
   PROFILE COMPLETION + OWNER STATS — same logic as profile.php,
   computed AFTER the form-submit block above so it reflects
   whatever the user just typed (not stale pre-submit data)
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

$spaceCountStmt = $db->prepare("SELECT COUNT(*) FROM spaces WHERE owner_id = :id");
$spaceCountStmt->execute([':id' => $userId]);
$spaceCount = (int) $spaceCountStmt->fetchColumn();

$bookingReceivedStmt = $db->prepare("
    SELECT COUNT(*) FROM bookings b JOIN spaces s ON b.space_id = s.id WHERE s.owner_id = :id
");
$bookingReceivedStmt->execute([':id' => $userId]);
$bookingsReceived = (int) $bookingReceivedStmt->fetchColumn();
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">

        <div class="greeting-row">
            <div>
                <h1>Edit Profile</h1>
            </div>
            <a href="<?= APP_URL; ?>/user/profile.php" class="btn btn-ghost-avastra"><i class="bi bi-x-lg"></i> Cancel</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="profile-card form-error-card">
                <?php foreach ($errors as $err): ?>
                    <div><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= APP_URL; ?>/user/edit-profile.php" method="POST" enctype="multipart/form-data">

            <div class="profile-layout">
                <div>
                    <!-- Identity card -->
                    <div class="profile-card">
                        <div class="photo-upload-row">
                            <?php if (!empty($user['profile_image']) && $user['profile_image'] !== 'default-avatar.png'): ?>
                                <img src="<?= APP_URL; ?>/<?= htmlspecialchars($user['profile_image']); ?>"
                                    class="avatar-circle-xl" style="object-fit:cover;">
                            <?php else: ?>
                                <div class="avatar-circle-xl"><?= htmlspecialchars($initials); ?></div>
                            <?php endif; ?>
                            <div>
                                <label class="pu-label" for="profile_image">Change profile photo</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                    </div>

                    <!-- Contact info card -->
                    <div class="profile-card">
                        <h2>Contact Information</h2>

                        <div class="edit-field-row">
                            <i class="bi bi-person"></i>
                            <label class="field-label" for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="field-input"
                                value="<?= htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="edit-field-row">
                            <i class="bi bi-envelope"></i>
                            <div class="field-static">
                                <div class="fs-label">Email</div>
                                <div class="fs-value"><?= htmlspecialchars($user['email']); ?></div>
                                <div class="fs-note">Email can't be changed here.</div>
                            </div>
                        </div>

                        <div class="edit-field-row">
                            <i class="bi bi-telephone"></i>
                            <label class="field-label" for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" class="field-input"
                                value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. +91 98200 45678">
                        </div>

                        <div class="edit-field-row">
                            <i class="bi bi-geo-alt"></i>
                            <label class="field-label" for="city">City</label>
                            <input type="text" id="city" name="city" class="field-input"
                                value="<?= htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>

                        <div class="edit-field-row">
                            <i class="bi bi-map"></i>
                            <label class="field-label" for="state">State</label>
                            <input type="text" id="state" name="state" class="field-input"
                                value="<?= htmlspecialchars($user['state'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary-avastra" style="margin-top:1rem;">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
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

        </form>

    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>