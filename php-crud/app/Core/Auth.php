<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => AppConfig::secureCookie(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        self::enforceInactivityTimeout();
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
        session_regenerate_id(true);
        $_SESSION['_session_created_at'] = time();
        $_SESSION['_last_activity_at'] = time();
        $_SESSION['user'] = $user;
        Csrf::rotate();
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

    private static function enforceInactivityTimeout(): void
    {
        $lastActivity = isset($_SESSION['_last_activity_at']) ? (int)$_SESSION['_last_activity_at'] : null;
        if ($lastActivity !== null && $lastActivity > 0) {
            $maxIdle = AppConfig::sessionTimeout();
            if ((time() - $lastActivity) > $maxIdle) {
                self::logout();
                return;
            }
        }

        $_SESSION['_last_activity_at'] = time();
    }
}
