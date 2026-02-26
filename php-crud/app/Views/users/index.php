<?php
$pageTitle = 'Gestion des utilisateurs';
$activeNav = 'users';
$pageSubtitle = 'Creation des comptes par administrateur et resource manager';
$topActions = '<a class="btn btn-ghost" href="index.php">Retour ressources</a>';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($created): ?><div class="alert alert-success">Utilisateur cree avec succes.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error); ?></div><?php endforeach; ?>

<div class="card">
    <h3>Creer un utilisateur</h3>
    <p class="hint">Seuls l'administrateur et le resource manager peuvent creer des comptes.</p>

    <form method="post" action="register.php">
        <div class="form-grid">
            <div class="form-group">
                <label for="nom">Nom complet</label>
                <input id="nom" type="text" name="nom" value="<?= h($nom); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="<?= h($email); ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <?php foreach ($allowedRoles as $option): ?>
                        <option value="<?= h($option); ?>" <?= $role === $option ? 'selected' : ''; ?>><?= h(roleLabel($option)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe initial</label>
                <input id="password" type="password" name="password" minlength="8" required>
            </div>
        </div>
        <div class="toolbar" style="margin-top: 12px;">
            <button type="submit" class="btn btn-primary">Creer le compte</button>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Role</th>
                <th>Date creation</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $item): ?>
            <tr>
                <td><?= (int)$item['id']; ?></td>
                <td><?= h((string)$item['nom']); ?></td>
                <td><?= h((string)$item['email']); ?></td>
                <td><span class="<?= roleBadgeClass((string)$item['role']); ?>"><?= h(roleLabel((string)$item['role'])); ?></span></td>
                <td><?= h((string)$item['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
