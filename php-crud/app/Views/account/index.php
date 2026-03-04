<?php
$pageTitle = 'Mon compte';
$activeNav = 'account';
$pageSubtitle = 'Gestion du mot de passe et securite du compte';
$topActions = '<a class="btn btn-ghost" href="index.php">Retour accueil</a>';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($passwordChanged): ?><div class="alert alert-success">Mot de passe mis a jour.</div><?php endif; ?>
<?php if ($mustChangePassword): ?><div class="alert alert-error">Le changement du mot de passe est obligatoire avant de continuer.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error); ?></div><?php endforeach; ?>

<div class="card">
    <h3>Securiser mon compte</h3>
    <form method="post" action="account.php">
        <?= csrf_field(); ?>
        <div class="form-grid">
            <?php if (!$mustChangePassword): ?>
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input id="current_password" type="password" name="current_password" required>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input id="new_password" type="password" name="new_password" minlength="12" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input id="confirm_password" type="password" name="confirm_password" minlength="12" required>
            </div>
        </div>
        <div class="toolbar" style="margin-top: 12px;">
            <button type="submit" class="btn btn-primary">Mettre a jour le mot de passe</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
