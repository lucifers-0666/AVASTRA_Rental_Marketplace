<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Auth
{
    public static function login(string $email, string $password): bool
    {
        $user = Database::fetch(
            'SELECT u.*, r.name AS role
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.status = "active"',
            [$email]
        );

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? null) === 'admin';
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return Database::fetch(
            'SELECT u.id, u.name, u.email, u.phone, u.status, r.name AS role
             FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
            [self::id()]
        );
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . APP_URL . '/visitor/login.php');
            exit;
        }
    }

    /** Server-side guard for every /admin route — never rely on hidden links. */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('403 Forbidden — admins only');
        }
    }
}
