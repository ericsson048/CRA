<?php
require_once __DIR__ . '/helpers.php';
$pageTitle = $pageTitle ?? 'ResourceHub';
$activeNav = $activeNav ?? 'dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$topActions = $topActions ?? '';
$sessionUser = $sessionUser ?? null;
$canPlan = in_array((string)($sessionUser['role'] ?? ''), ['admin', 'gestionnaire', 'developpeur'], true);
$canManage = in_array((string)($sessionUser['role'] ?? ''), ['admin', 'gestionnaire'], true);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="brand"><h1>Resource<span>Hub</span></h1><p>Gestion des ressources</p></div>
        <div class="nav-group">
            <div class="nav-label">Principal</div>
            <a class="<?= navLinkClass('dashboard', $activeNav); ?>" href="index.php"><span class="dot rh"></span>Ressources</a>
            <?php if ($canManage): ?>
                <a class="<?= navLinkClass('create', $activeNav); ?>" href="create.php"><span class="dot rm"></span>Ajouter</a>
                <a class="<?= navLinkClass('users', $activeNav); ?>" href="register.php"><span class="dot rh"></span>Utilisateurs</a>
            <?php endif; ?>
            <?php if ($canPlan): ?>
                <a class="<?= navLinkClass('planning', $activeNav); ?>" href="planning.php"><span class="dot rh"></span>Planning</a>
            <?php endif; ?>
        </div>
        <div class="nav-group">
            <div class="nav-label">Compte</div>
            <?php if ($sessionUser): ?>
                <a class="<?= navLinkClass('logout', $activeNav); ?>" href="logout.php"><span class="dot"></span>Deconnexion</a>
            <?php else: ?>
                <a class="<?= navLinkClass('login', $activeNav); ?>" href="login.php"><span class="dot"></span>Connexion</a>
            <?php endif; ?>
        </div>
        <div class="sidebar-footer">
            <?php if ($sessionUser): ?>
                <p class="user-name"><?= h((string)($sessionUser['name'] ?? 'Utilisateur')); ?></p>
                <p class="user-role">Role: <?= h((string)($sessionUser['role'] ?? '')); ?></p>
            <?php else: ?>
                <p class="user-name">Visiteur</p>
                <p class="user-role">Connectez-vous pour continuer</p>
            <?php endif; ?>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div>
                <h2><?= h($pageTitle); ?></h2>
                <?php if ($pageSubtitle !== ''): ?><p><?= h($pageSubtitle); ?></p><?php endif; ?>
            </div>
            <div class="topbar-actions"><?= $topActions; ?></div>
        </header>
        <section class="content">
