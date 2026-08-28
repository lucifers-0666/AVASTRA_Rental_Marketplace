<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$adminModel = new Admin();

$message = '';
$error = '';

// Handle Status Toggle (Block / Activate User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_status'])) {
    $userId = (int) $_POST['user_id'];
    $newStatus = $_POST['new_status'];

    if (in_array($newStatus, ['active', 'blocked', 'inactive'])) {
        $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id AND role_id != 1");
        if ($stmt->execute([':status' => $newStatus, ':id' => $userId])) {
            $adminModel->logAction($currentUser['id'], 'USER_STATUS_CHANGE', 'USER', $userId, "Status changed to {$newStatus}");
            $message = "User #{$userId} status updated to " . strtoupper($newStatus);
        } else {
            $error = "Failed to update user status.";
        }
    }
}

// Search & Filter Query
$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';

$whereClauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($roleFilter !== 'all') {
    $whereClauses[] = "r.role_name = :role";
    $params[':role'] = $roleFilter;
}

$sql = "
    SELECT u.*, r.role_name, 
           (SELECT COUNT(*) FROM spaces WHERE owner_id = u.id) AS spaces_count,
           (SELECT COUNT(*) FROM bookings WHERE seeker_id = u.id) AS bookings_count
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY u.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div id="admin-main">
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <main class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">User Management</h3>
                <p class="text-muted small mb-0">Manage registered accounts, view activity metrics, and control account status.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Card -->
        <div class="admin-card mb-4">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="role" class="form-select">
                        <option value="all" <?= ($roleFilter === 'all') ? 'selected' : ''; ?>>All Roles</option>
                        <option value="user" <?= ($roleFilter === 'user') ? 'selected' : ''; ?>>Registered User (Seeker/Owner)</option>
                        <option value="admin" <?= ($roleFilter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Filter Users</button>
                </div>
            </form>
        </div>

        <!-- Users Table Card -->
        <div class="admin-card">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Role</th>
                            <th>Contact Info</th>
                            <th>Spaces Listed</th>
                            <th>Bookings Made</th>
                            <th>Status</th>
                            <th>Registered Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($u['full_name']); ?></div>
                                    <small class="text-muted">ID: #<?= $u['id']; ?></small>
                                </td>
                                <td>
                                    <?php if ($u['role_name'] === 'admin'): ?>
                                        <span class="badge bg-purple text-white px-2 py-1" style="background:#6b21a8;"><i class="bi bi-shield-lock-fill me-1"></i> Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white px-2 py-1"><i class="bi bi-person me-1"></i> User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($u['email']); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['phone'] ?? 'N/A'); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $u['spaces_count']; ?> spaces</span></td>
                                <td><span class="badge bg-light text-dark border"><?= $u['bookings_count']; ?> bookings</span></td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge-status active"><i class="bi bi-check-circle"></i> Active</span>
                                    <?php elseif ($u['status'] === 'blocked'): ?>
                                        <span class="badge-status blocked"><i class="bi bi-slash-circle"></i> Blocked</span>
                                    <?php else: ?>
                                        <span class="badge-status pending"><?= ucfirst($u['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= date('d M Y', strtotime($u['created_at'])); ?></small></td>
                                <td>
                                    <?php if ($u['role_name'] !== 'admin'): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
                                            <?php if ($u['status'] === 'active'): ?>
                                                <input type="hidden" name="new_status" value="blocked">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-slash-circle me-1"></i> Block User
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check-circle me-1"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
