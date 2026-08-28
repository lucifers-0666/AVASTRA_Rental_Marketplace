<?php
/**
 * SpaceShare — Authentication & Session Manager
 */

require_once __DIR__ . '/Database.php';

class Auth {

    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn(): bool {
        self::initSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function getUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return [
            'id'        => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'] ?? 'User',
            'email'     => $_SESSION['user_email'] ?? '',
            'role_id'   => $_SESSION['user_role_id'] ?? 2,
            'role_name' => $_SESSION['user_role'] ?? 'user'
        ];
    }

    public static function isAdmin(): bool {
        if (!self::isLoggedIn()) return false;
        return (($_SESSION['user_role_id'] ?? 2) == 1) || (($_SESSION['user_role'] ?? '') === 'admin');
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header("Location: " . APP_URL . "/public/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            header("Location: " . APP_URL . "/user/dashboard.php?error=unauthorized");
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
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['user_name']    = $user['full_name'];
        $_SESSION['user_email']   = $user['email'];
        $_SESSION['user_role_id'] = $user['role_id'];
        $_SESSION['user_role']    = $user['role_name'];

        return ['success' => true, 'user' => $user];
    }

    public static function logout(): void {
        self::initSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
