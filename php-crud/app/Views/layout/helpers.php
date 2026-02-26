<?php
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
        return 'badge badge-ok';
    }
}
