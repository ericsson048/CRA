<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function user(): ?array
    {
        self::start();
        $user = $_SESSION['user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(array $roles): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }
        return in_array((string)($user['role'] ?? ''), $roles, true);
    }

    public static function login(array $user): void
    {
        self::start();
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }
}
