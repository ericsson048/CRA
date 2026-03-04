<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Auth::start();
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function verify(?string $token): bool
    {
        Auth::start();
        $current = $_SESSION[self::SESSION_KEY] ?? null;
        return is_string($current) && is_string($token) && hash_equals($current, $token);
    }

    public static function rotate(): void
    {
        Auth::start();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}
