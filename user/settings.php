<?php

/**
 * AVASTRA — Account Settings
 * Account details and password changes use the existing users table. The
 * preference controls are kept in the authenticated session because the
 * current schema has no user-preferences table or columns yet.
 */
$pageTitle = 'Account Settings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];
$tabs   = ['account', 'notifications', 'privacy', 'security'];
$tab    = $_GET['tab'] ?? 'account';
$tab    = in_array($tab, $tabs, true) ? $tab : 'account';
$notice = '';
$error  = '';

$preferenceDefaults = [
    'booking_updates' => true,
    'request_updates' => true,
    'messages'        => true,
    'space_updates'   => true,
    'avastra_updates' => false,
    'public_profile'  => true,
    'response_time'   => true,
    'anonymous_data'  => true,
];
$_SESSION['settings_preferences'] = array_merge(
    $preferenceDefaults,
    $_SESSION['settings_preferences'] ?? []
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';

    if ($form === 'account') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');

        if ($fullName === '' || $email === '') {
            $error = 'Please enter both your display name and email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $exists = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $exists->execute([':email' => $email, ':id' => $userId]);

            if ($exists->fetch()) {
                $error = 'That email address is already in use.';
            } else {
                $update = $db->prepare('UPDATE users SET full_name = :name, email = :email WHERE id = :id');
                $update->execute([':name' => $fullName, ':email' => $email, ':id' => $userId]);
                $_SESSION['user_name']  = $fullName;
                $_SESSION['user_email'] = $email;
                $currentUser = Auth::getUser();
                $notice = 'Your account details have been saved.';
            }
        }
    }

    if ($form === 'preferences') {
        foreach ($preferenceDefaults as $key => $default) {
            $_SESSION['settings_preferences'][$key] = isset($_POST['preferences'][$key]);
        }
        $notice = 'Your preferences have been saved for this session.';
    }

    if ($form === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $userStmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute([':id' => $userId]);
        $userRecord = $userStmt->fetch();

        if (!$userRecord || !password_verify($currentPassword, $userRecord['password_hash'])) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Your new password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'The new passwords do not match.';
        } else {
            $update = $db->prepare('UPDATE users SET password_hash = :password WHERE id = :id');
            $update->execute([':password' => password_hash($newPassword, PASSWORD_DEFAULT), ':id' => $userId]);
            $notice = 'Your password has been updated.';
        }
    }
}

$userStmt = $db->prepare('SELECT full_name, email FROM users WHERE id = :id LIMIT 1');
$userStmt->execute([':id' => $userId]);
$user = $userStmt->fetch() ?: $currentUser;
$prefs = $_SESSION['settings_preferences'];
$unreadNotifCount = 0;

function settingsToggle(string $key, array $prefs): string
{
    return '<label class="settings-switch"><input type="checkbox" name="preferences[' . htmlspecialchars($key) . ']"' .
        (!empty($prefs[$key]) ? ' checked' : '') . '><span></span></label>';
}
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <main id="user-content" class="settings-page">
        <h1>Account Settings</h1>

        <?php if ($notice): ?><div class="settings-alert success"><i class="bi bi-check-circle"></i><?= htmlspecialchars($notice); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="settings-alert error"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="settings-layout">
            <nav class="settings-tabs" aria-label="Account settings sections">
                <a href="?tab=account" class="<?= $tab === 'account' ? 'active' : ''; ?>"><i class="bi bi-person"></i>Account</a>
                <a href="?tab=notifications" class="<?= $tab === 'notifications' ? 'active' : ''; ?>"><i class="bi bi-bell"></i>Notifications</a>
                <a href="?tab=privacy" class="<?= $tab === 'privacy' ? 'active' : ''; ?>"><i class="bi bi-shield"></i>Privacy</a>
                <a href="?tab=security" class="<?= $tab === 'security' ? 'active' : ''; ?>"><i class="bi bi-lock"></i>Security</a>
            </nav>

            <section class="settings-panel">
                <?php if ($tab === 'account'): ?>
                    <h2>Account</h2>
                    <form method="post">
                        <input type="hidden" name="form" value="account">
                        <label class="settings-field">Display name
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>" maxlength="100" required>
                        </label>
                        <label class="settings-field">Email address
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" maxlength="150" required>
                        </label>
                        <div class="settings-form-action"><button class="btn btn-primary-avastra" type="submit">Save changes</button></div>
                    </form>
                    <div class="settings-danger-zone">
                        <span>Danger zone</span>
                        <a href="mailto:support@avastra.in?subject=Account%20deletion%20request">Delete my account</a>
                    </div>
                <?php elseif ($tab === 'notifications'): ?>
                    <h2>Notification preferences</h2>
                    <form method="post">
                        <input type="hidden" name="form" value="preferences">
                        <div class="settings-toggle-list">
                            <div><strong>Booking updates</strong>
                                <p>Confirmations, cancellations, reminders</p><?= settingsToggle('booking_updates', $prefs); ?>
                            </div>
                            <div><strong>Request updates</strong>
                                <p>New requests, status changes</p><?= settingsToggle('request_updates', $prefs); ?>
                            </div>
                            <div><strong>Messages</strong>
                                <p>New messages from owners and renters</p><?= settingsToggle('messages', $prefs); ?>
                            </div>
                            <div><strong>Space updates</strong>
                                <p>Listing approvals, review feedback</p><?= settingsToggle('space_updates', $prefs); ?>
                            </div>
                            <div><strong>AVASTRA updates</strong>
                                <p>New features, tips and best practices</p><?= settingsToggle('avastra_updates', $prefs); ?>
                            </div>
                        </div>
                        <div class="settings-form-action"><button class="btn btn-primary-avastra" type="submit">Save changes</button></div>
                    </form>
                <?php elseif ($tab === 'privacy'): ?>
                    <h2>Privacy</h2>
                    <form method="post">
                        <input type="hidden" name="form" value="preferences">
                        <div class="settings-toggle-list">
                            <div><strong>Show my profile publicly</strong>
                                <p>Allow other users to view your profile page</p><?= settingsToggle('public_profile', $prefs); ?>
                            </div>
                            <div><strong>Show response time</strong>
                                <p>Display your typical response time on listings</p><?= settingsToggle('response_time', $prefs); ?>
                            </div>
                            <div><strong>Allow AVASTRA to share anonymised data</strong>
                                <p>Help improve our recommendations</p><?= settingsToggle('anonymous_data', $prefs); ?>
                            </div>
                        </div>
                        <div class="settings-form-action"><button class="btn btn-primary-avastra" type="submit">Save changes</button></div>
                    </form>
                <?php else: ?>
                    <h2>Change Password</h2>
                    <form method="post">
                        <input type="hidden" name="form" value="password">
                        <label class="settings-field">Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
                        <label class="settings-field">New password<input type="password" name="new_password" autocomplete="new-password" minlength="8" required></label>
                        <label class="settings-field">Confirm new password<input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></label>
                        <div class="settings-form-action"><button class="btn btn-primary-avastra" type="submit">Update password</button></div>
                    </form>
                    <div class="settings-session-card">
                        <h2>Active sessions</h2>
                        <p>Devices currently signed into your account.</p>
                        <div class="settings-session">
                            <div><strong>This browser</strong><span>Current session</span></div><span class="settings-current">Active now</span>
                        </div>
                    </div>
                    <a class="settings-signout-link" href="<?= APP_URL; ?>/public/logout.php"><i class="bi bi-box-arrow-right"></i> Sign out of this device</a>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>