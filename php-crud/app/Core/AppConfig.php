<?php
declare(strict_types=1);

namespace App\Core;

final class AppConfig
{
    private static bool $bootstrapped = false;

    public static function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }

        date_default_timezone_set(self::timezone());
        self::$bootstrapped = true;
    }

    public static function appName(): string
    {
        return Env::get('APP_NAME', 'ResourceHub') ?? 'ResourceHub';
    }

    public static function environment(): string
    {
        return Env::get('APP_ENV', 'local') ?? 'local';
    }

    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    public static function debug(): bool
    {
        return Env::bool('APP_DEBUG', !self::isProduction());
    }

    public static function timezone(): string
    {
        return Env::get('APP_TIMEZONE', 'UTC') ?? 'UTC';
    }

    public static function autoMigrate(): bool
    {
        return Env::bool('APP_AUTO_MIGRATE', !self::isProduction());
    }

    public static function seedDemoData(): bool
    {
        return Env::bool('APP_SEED_DEMO_DATA', false);
    }

    public static function dbHost(): string
    {
        return Env::get('DB_HOST', '127.0.0.1') ?? '127.0.0.1';
    }

    public static function dbPort(): int
    {
        return Env::int('DB_PORT', 3306);
    }

    public static function dbName(): string
    {
        return Env::get('DB_NAME', 'resource_manager') ?? 'resource_manager';
    }

    public static function dbUser(): string
    {
        return Env::get('DB_USER', 'root') ?? 'root';
    }

    public static function dbPassword(): string
    {
        return Env::get('DB_PASSWORD', '') ?? '';
    }

    public static function sessionTimeout(): int
    {
        return max(300, Env::int('SESSION_TIMEOUT', 1800));
    }

    public static function secureCookie(): bool
    {
        if (Env::get('SESSION_SECURE_COOKIE') !== null) {
            return Env::bool('SESSION_SECURE_COOKIE', false);
        }

        $https = (string)($_SERVER['HTTPS'] ?? '');
        return $https !== '' && strtolower($https) !== 'off';
    }

    public static function loginMaxAttempts(): int
    {
        return max(3, Env::int('LOGIN_MAX_ATTEMPTS', 5));
    }

    public static function loginWindowMinutes(): int
    {
        return max(5, Env::int('LOGIN_WINDOW_MINUTES', 15));
    }

    public static function logFilePath(): string
    {
        return BASE_PATH . '/storage/logs/app.log';
    }

    public static function appUrl(): string
    {
        return rtrim(Env::get('APP_URL', 'http://localhost/php-crud') ?? 'http://localhost/php-crud', '/');
    }

    public static function emailHost(): string
    {
        return Env::get('EMAIL_HOST', '') ?? '';
    }

    public static function emailPort(): int
    {
        return Env::int('EMAIL_PORT', 587);
    }

    public static function emailUsername(): string
    {
        return Env::get('EMAIL_HOST_USER', '') ?? '';
    }

    public static function emailPassword(): string
    {
        return Env::get('EMAIL_HOST_PASSWORD', '') ?? '';
    }

    public static function emailUseTls(): bool
    {
        return Env::bool('EMAIL_USE_TLS', true);
    }

    public static function emailImplicitTls(): bool
    {
        return Env::bool('EMAIL_IMPLICIT_TLS', false);
    }

    public static function emailFromAddress(): string
    {
        return Env::get('DEFAULT_FROM_EMAIL', self::emailUsername()) ?? self::emailUsername();
    }

    public static function emailFromName(): string
    {
        return self::appName();
    }

    public static function emailEnabled(): bool
    {
        return self::emailHost() !== '' && self::emailUsername() !== '' && self::emailPassword() !== '' && self::emailFromAddress() !== '';
    }

    public static function applyHttpSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header(
            "Content-Security-Policy: default-src 'self'; " .
            "base-uri 'self'; " .
            "frame-ancestors 'none'; " .
            "form-action 'self'; " .
            "img-src 'self' data:; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com data:; " .
            "script-src 'self' 'unsafe-inline';"
        );
    }
}
