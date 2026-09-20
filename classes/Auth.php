<?php
/**
 * SpaceShare — Authentication & Session Manager
 */

require_once __DIR__ . '/Database.php';

class Auth {

    public static function initSession(): void {
        if (ob_get_level() === 0) {
            ob_start();
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn(): bool {
        self::initSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function isAdminLoggedIn(): bool {
        self::initSession();
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']) && ((int)($_SESSION['admin_role_id'] ?? 0) === 1);
    }

    public static function getUser(): ?array {
        self::initSession();
        $isAdminRoute = (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin') !== false);
        if ($isAdminRoute && self::isAdminLoggedIn()) {
            return self::getAdminUser();
        }
        if (self::isLoggedIn()) {
            return [
                'id'        => $_SESSION['user_id'],
                'full_name' => $_SESSION['user_name'] ?? 'User',
                'email'     => $_SESSION['user_email'] ?? '',
                'role_id'   => $_SESSION['user_role_id'] ?? 2,
                'role_name' => $_SESSION['user_role'] ?? 'user'
            ];
        }
        if (self::isAdminLoggedIn()) {
            return self::getAdminUser();
        }
        return null;
    }

    public static function getAdminUser(): ?array {
        if (!self::isAdminLoggedIn()) return null;
        return [
            'id'        => $_SESSION['admin_id'],
            'full_name' => $_SESSION['admin_name'] ?? 'Administrator',
            'email'     => $_SESSION['admin_email'] ?? '',
            'role_id'   => (int)($_SESSION['admin_role_id'] ?? 1),
            'role_name' => $_SESSION['admin_role'] ?? 'admin'
        ];
    }

    public static function isAdmin(): bool {
        return self::isAdminLoggedIn();
    }

    public static function requireLogin(): void {
        self::initSession();
        if (!self::isLoggedIn()) {
            header("Location: " . APP_URL . "/public/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI'] ?? ''));
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::initSession();
        if (!self::isAdminLoggedIn()) {
            header("Location: " . APP_URL . "/admin/login.php");
            exit;
        }
    }

    public static function login(string $email, string $password): array {
        self::initSession();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT u.*, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if ($user['status'] === 'blocked') {
            return ['success' => false, 'message' => 'Your account has been suspended. Please contact admin.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Set session
        session_regenerate_id(true);
        if ((int)$user['role_id'] === 1 || $user['role_name'] === 'admin') {
            $_SESSION['admin_id']      = $user['id'];
            $_SESSION['admin_name']    = $user['full_name'];
            $_SESSION['admin_email']   = $user['email'];
            $_SESSION['admin_role_id'] = $user['role_id'];
            $_SESSION['admin_role']    = $user['role_name'];
        } else {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['full_name'];
            $_SESSION['user_email']   = $user['email'];
            $_SESSION['user_role_id'] = $user['role_id'];
            $_SESSION['user_role']    = $user['role_name'];
        }

        return ['success' => true, 'user' => $user];
    }

    public static function loginAdmin(string $email, string $password): array {
        self::initSession();
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT u.*, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid admin email or password.'];
        }

        if ($user['status'] === 'blocked') {
            return ['success' => false, 'message' => 'This account has been suspended.'];
        }

        if ((int)$user['role_id'] !== 1 && $user['role_name'] !== 'admin') {
            return ['success' => false, 'message' => 'Access denied. Only Administrator accounts can log in here.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid admin email or password.'];
        }

        session_regenerate_id(true);
        $_SESSION['admin_id']      = $user['id'];
        $_SESSION['admin_name']    = $user['full_name'];
        $_SESSION['admin_email']   = $user['email'];
        $_SESSION['admin_role_id'] = $user['role_id'];
        $_SESSION['admin_role']    = $user['role_name'];

        return ['success' => true, 'user' => $user];
    }

    public static function register(array $data): array {
        self::initSession();
        $db = Database::getInstance();

        $fullName = trim($data['full_name'] ?? '');
        $email    = strtolower(trim($data['email'] ?? ''));
        $phone    = trim($data['phone'] ?? '');
        $city     = trim($data['city'] ?? '');
        $state    = trim($data['state'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if ($fullName === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'Please fill in all required fields (Name, Email, Password).'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        // Check if email already exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'An account with this email address already exists.'];
        }

        try {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $db->prepare("
                INSERT INTO users (role_id, full_name, email, password_hash, phone, city, state, status, email_verified)
                VALUES (2, :full_name, :email, :password_hash, :phone, :city, :state, 'active', 1)
            ");
            $insertStmt->execute([
                ':full_name'     => $fullName,
                ':email'         => $email,
                ':password_hash' => $passwordHash,
                ':phone'         => $phone !== '' ? $phone : null,
                ':city'          => $city !== '' ? $city : null,
                ':state'         => $state !== '' ? $state : null,
            ]);

            $newUserId = (int) $db->lastInsertId();

            // Auto-login newly registered user
            session_regenerate_id(true);
            $_SESSION['user_id']      = $newUserId;
            $_SESSION['user_name']    = $fullName;
            $_SESSION['user_email']   = $email;
            $_SESSION['user_role_id'] = 2;
            $_SESSION['user_role']    = 'user';

            return ['success' => true, 'user_id' => $newUserId];
        } catch (Exception $e) {
            error_log("Registration failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again later.'];
        }
    }

    public static function logout(): void {
        self::initSession();
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_email'],
            $_SESSION['user_role_id'],
            $_SESSION['user_role']
        );
    }

    public static function logoutAdmin(): void {
        self::initSession();
        unset(
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_email'],
            $_SESSION['admin_role_id'],
            $_SESSION['admin_role']
        );
    }
}
