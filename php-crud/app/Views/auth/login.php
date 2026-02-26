<?php
$pageTitle = 'Connexion';
require __DIR__ . '/../layout/guest_start.php';
?>
<div class="auth-shell">
    <div class="auth-card">
        <h1>Connexion</h1>
        <p>Accede au suivi des ressources RH, RM et planning.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= h($error); ?></div>
        <?php endforeach; ?>

        <form method="post" action="login.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= h($email); ?>" required>
            </div>
            <div class="form-group" style="margin-top: 12px;">
                <label for="password">Mot de passe</label>
                <input id="password" type="password" name="password" required>
            </div>
            <div class="toolbar" style="margin-top: 14px;">
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </div>
        </form>

        <div class="auth-footer">
            Les comptes sont crees par l'administrateur ou le resource manager.
        </div>
    </div>
</div>
<?php require __DIR__ . '/../layout/guest_end.php'; ?>
