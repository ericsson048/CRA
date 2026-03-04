<?php
use App\Core\Csrf;

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('navLinkClass')) {
    function navLinkClass(string $target, string $active): string
    {
        return $target === $active ? 'nav-link active' : 'nav-link';
    }
}

if (!function_exists('roleLabel')) {
    function roleLabel(string $role): string
    {
        if ($role === 'admin') {
            return 'Administrateur';
        }
        if ($role === 'gestionnaire') {
            return 'Resource manager';
        }
        if ($role === 'team_leader') {
            return 'Team Leader';
        }
        if ($role === 'team_leader_adjoint') {
            return 'Team Leader Adjoint';
        }
        return 'Developpeur';
    }
}

if (!function_exists('roleBadgeClass')) {
    function roleBadgeClass(string $role): string
    {
        if ($role === 'admin') {
            return 'badge badge-danger';
        }
        if ($role === 'gestionnaire') {
            return 'badge badge-warn';
        }
        if ($role === 'team_leader') {
            return 'badge badge-neutral';
        }
        if ($role === 'team_leader_adjoint') {
            return 'badge badge-neutral';
        }
        return 'badge badge-ok';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . h(csrf_token()) . '">';
    }
}

if (!function_exists('activeBadgeClass')) {
    function activeBadgeClass(bool $isActive): string
    {
        return $isActive ? 'badge badge-ok' : 'badge badge-danger';
    }
}

if (!function_exists('notificationBadge')) {
    function notificationBadge(int $count): string
    {
        if ($count <= 0) {
            return '';
        }
        return '<span class="nav-counter">' . h((string)$count) . '</span>';
    }
}
