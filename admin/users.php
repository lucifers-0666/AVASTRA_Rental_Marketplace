<?php
$pageTitle = 'Users';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = Database::getInstance();
$adminModel = new Admin();

$message = '';
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? 'User@123');
    $phone = trim($_POST['phone'] ?? '');
    $roleId = (int) ($_POST['role_id'] ?? 2);

    if (!empty($fullName) && !empty($email)) {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, password, phone, role_id, status) VALUES (:name, :email, :pass, :phone, :role, 'active')");
            $stmt->execute([':name' => $fullName, ':email' => $email, ':pass' => $hash, ':phone' => $phone, ':role' => $roleId]);
            $newId = (int) $db->lastInsertId();
            $adminModel->logAction($currentUser['id'], 'ADD_USER', 'USER', $newId, "Added new user account {$email}");
            $message = "User account '{$fullName}' created successfully!";
        } catch (Exception $e) {
            $error = "Failed to create user: " . $e->getMessage();
        }
    }
}

// Handle Status Toggle (Block / Activate / Suspend User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_status'])) {
    $userId = (int) $_POST['user_id'];
    $newStatus = $_POST['new_status'];

    if (in_array($newStatus, ['active', 'blocked', 'inactive', 'suspended'])) {
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
$statusFilter = $_GET['status'] ?? 'all';

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

if ($statusFilter !== 'all') {
    $whereClauses[] = "u.status = :status";
    $params[':status'] = $statusFilter;
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

    <main class="p-3 p-md-4">
        <!-- Header Controls -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <p class="text-muted small mb-0">Manage registered user accounts, view activity, and control user permissions.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-avastra btn-sm rounded-pill px-3">
                    <i class="bi bi-download me-1"></i> Export
                </button>
                <button class="btn btn-avastra rounded-pill px-3 py-1" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus me-1"></i> Add User
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Card -->
        <div class="avastra-card p-3 mb-3">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm">
                        <option value="all" <?= ($roleFilter === 'all') ? 'selected' : ''; ?>>All Roles</option>
                        <option value="user" <?= ($roleFilter === 'user') ? 'selected' : ''; ?>>Registered User</option>
                        <option value="admin" <?= ($roleFilter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="active" <?= ($statusFilter === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="blocked" <?= ($statusFilter === 'blocked') ? 'selected' : ''; ?>>Suspended / Blocked</option>
                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-avastra btn-sm"><i class="bi bi-funnel me-1"></i> Filter Users</button>
                </div>
            </form>
        </div>

        <!-- Users Table Card -->
        <div class="avastra-card p-3">
            <div class="table-responsive">
                <table class="table table-avastra align-middle mb-0" style="font-size:0.825rem;">
                    <thead>
                        <tr>
                            <th><i class="bi bi-person me-1 text-muted"></i> User Name</th>
                            <th><i class="bi bi-shield-check me-1 text-muted"></i> Role</th>
                            <th><i class="bi bi-envelope me-1 text-muted"></i> Contact Info</th>
                            <th><i class="bi bi-building me-1 text-muted"></i> Spaces</th>
                            <th><i class="bi bi-calendar-check me-1 text-muted"></i> Bookings</th>
                            <th><i class="bi bi-clock me-1 text-muted"></i> Joined Date</th>
                            <th><i class="bi bi-info-circle me-1 text-muted"></i> Status</th>
                            <th><i class="bi bi-three-dots me-1 text-muted"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']); ?></div>
                                    <small class="text-muted font-mono">ID: #<?= $u['id']; ?></small>
                                </td>
                                <td>
                                    <?php if ($u['role_name'] === 'admin'): ?>
                                        <span class="badge bg-dark text-white px-2 py-1"><i class="bi bi-shield-lock-fill me-1"></i> Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-person me-1"></i> User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($u['email']); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($u['phone'] ?? 'N/A'); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border font-mono"><?= $u['spaces_count']; ?> spaces</span></td>
                                <td><span class="badge bg-light text-dark border font-mono"><?= $u['bookings_count']; ?> bookings</span></td>
                                <td><small class="text-muted"><?= date('d M Y', strtotime($u['created_at'])); ?></small></td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge-status active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                    <?php elseif ($u['status'] === 'blocked' || $u['status'] === 'suspended'): ?>
                                        <span class="badge-status blocked"><i class="bi bi-slash-circle-fill"></i> Suspended</span>
                                    <?php else: ?>
                                        <span class="badge-status pending"><i class="bi bi-clock"></i> <?= ucfirst($u['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#viewUserModal<?= $u['id']; ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <?php if ($u['role_name'] !== 'admin'): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?= $u['id']; ?>">
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <input type="hidden" name="new_status" value="blocked">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.75rem;">
                                                        <i class="bi bi-slash-circle"></i> Suspend
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:0.75rem;">
                                                        <i class="bi bi-check-circle"></i> Activate
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <!-- User Detail Modal -->
                                    <div class="modal fade" id="viewUserModal<?= $u['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold"><i class="bi bi-person-badge me-2 text-success"></i> User Profile #<?= $u['id']; ?></h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="d-flex align-items-center gap-3 mb-3">
                                                        <div class="user-avatar" style="width:48px; height:48px; font-size:1.25rem;">
                                                            <?= strtoupper(substr($u['full_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold fs-6"><?= htmlspecialchars($u['full_name']); ?></div>
                                                            <div class="text-muted small"><?= htmlspecialchars($u['email']); ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="row g-2 small border-top pt-2">
                                                        <div class="col-6"><strong>Phone:</strong> <?= htmlspecialchars($u['phone'] ?? 'Not provided'); ?></div>
                                                        <div class="col-6"><strong>Role:</strong> <?= ucfirst($u['role_name']); ?></div>
                                                        <div class="col-6"><strong>Status:</strong> <?= ucfirst($u['status']); ?></div>
                                                        <div class="col-6"><strong>Joined:</strong> <?= date('d M Y', strtotime($u['created_at'])); ?></div>
                                                        <div class="col-6"><strong>Listed Spaces:</strong> <?= $u['spaces_count']; ?></div>
                                                        <div class="col-6"><strong>Reservations:</strong> <?= $u['bookings_count']; ?></div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Add User -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="add_user" value="1">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-success"></i> Add New User Account</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Full Name</label>
                            <input type="text" name="full_name" class="form-control form-control-sm" required placeholder="e.g. Ananya Roy">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-sm" required placeholder="e.g. ananya@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="e.g. +91 9876543210">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Initial Password</label>
                            <input type="password" name="password" class="form-control form-control-sm" value="User@123" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-avastra">Create User Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
