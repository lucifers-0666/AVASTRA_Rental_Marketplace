<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/** Escape output for HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/** Call at the top of every POST handler. */
function csrf_verify(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF verification failed.');
    }
}

/** Human-friendly booking reference, e.g. SS-2026-9F3A1C2B. */
function booking_ref(): string
{
    return 'SS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function current_commission_percent(): float
{
    $row = Database::fetch(
        'SELECT percent FROM commission_settings
         WHERE effective_from <= CURDATE()
         ORDER BY effective_from DESC, id DESC LIMIT 1'
    );
    return $row !== null ? (float) $row['percent'] : 0.0;
}
