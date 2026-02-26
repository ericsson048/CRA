<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;

Auth::start();

function currentUser(): ?array
{
    return Auth::user();
}

function isLoggedIn(): bool
{
    return Auth::check();
}

/**
 * @param array<int, string> $roles
 */
function hasRole(array $roles): bool
{
    return Auth::hasRole($roles);
}

function requireLogin(): void
{
    if (!Auth::check()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * @param array<int, string> $roles
 */
function requireRole(array $roles): void
{
    requireLogin();
    if (!Auth::hasRole($roles)) {
        http_response_code(403);
        echo 'Acces refuse.';
        exit;
    }
}

function logoutUser(): void
{
    Auth::logout();
}
