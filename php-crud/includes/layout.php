<?php
require_once __DIR__ . '/../auth.php';

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

if (!function_exists('renderLayoutStart')) {
    function renderLayoutStart(string $title, string $active = 'dashboard', string $subtitle = '', string $actions = ''): void
    {
        $user = currentUser();
        $canPlan = hasRole(['admin', 'gestionnaire', 'developpeur']);

        echo '<!doctype html>';
        echo '<html lang="fr">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . h($title) . '</title>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">';
        echo '<link rel="stylesheet" href="assets/app.css">';
        echo '</head>';
        echo '<body>';
        echo '<div class="app-layout">';
        echo '<aside class="sidebar">';
        echo '<div class="brand"><h1>Resource<span>Hub</span></h1><p>Gestion des ressources</p></div>';
        echo '<div class="nav-group">';
        echo '<div class="nav-label">Principal</div>';
        echo '<a class="' . navLinkClass('dashboard', $active) . '" href="index.php"><span class="dot rh"></span>Ressources</a>';
        if (hasRole(['admin', 'gestionnaire'])) {
            echo '<a class="' . navLinkClass('create', $active) . '" href="create.php"><span class="dot rm"></span>Ajouter</a>';
            echo '<a class="' . navLinkClass('users', $active) . '" href="register.php"><span class="dot rh"></span>Utilisateurs</a>';
        }
        if ($canPlan) {
            echo '<a class="' . navLinkClass('planning', $active) . '" href="planning.php"><span class="dot rh"></span>Planning</a>';
        }
        echo '</div>';
        echo '<div class="nav-group">';
        echo '<div class="nav-label">Compte</div>';
        if ($user !== null) {
            echo '<a class="' . navLinkClass('logout', $active) . '" href="logout.php"><span class="dot"></span>Deconnexion</a>';
        } else {
            echo '<a class="' . navLinkClass('login', $active) . '" href="login.php"><span class="dot"></span>Connexion</a>';
        }
        echo '</div>';
        echo '<div class="sidebar-footer">';
        if ($user !== null) {
            echo '<p class="user-name">' . h((string)($user['name'] ?? 'Utilisateur')) . '</p>';
            echo '<p class="user-role">Role: ' . h((string)($user['role'] ?? '')) . '</p>';
        } else {
            echo '<p class="user-name">Visiteur</p>';
            echo '<p class="user-role">Connectez-vous pour continuer</p>';
        }
        echo '</div>';
        echo '</aside>';
        echo '<main class="main">';
        echo '<header class="topbar">';
        echo '<div><h2>' . h($title) . '</h2>';
        if ($subtitle !== '') {
            echo '<p>' . h($subtitle) . '</p>';
        }
        echo '</div>';
        echo '<div class="topbar-actions">' . $actions . '</div>';
        echo '</header>';
        echo '<section class="content">';
    }
}

if (!function_exists('renderLayoutEnd')) {
    function renderLayoutEnd(): void
    {
        echo '</section></main></div></body></html>';
    }
}
