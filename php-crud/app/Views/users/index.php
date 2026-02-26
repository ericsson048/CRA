<?php
$pageTitle = 'Gestion des utilisateurs';
$activeNav = 'users';
$pageSubtitle = 'Admin/gestionnaire: creation TL, TLA et developpeurs';
$topActions = '<a class="btn btn-ghost" href="index.php">Retour ressources</a>';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($created): ?><div class="alert alert-success">Utilisateur cree avec succes.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error); ?></div><?php endforeach; ?>

<div class="card">
    <h3>Creer un utilisateur</h3>
    <p class="hint">Les developpeurs sont rattaches a une team. Le gestionnaire assigne ensuite les projets aux TL/TLA.</p>

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
                <label for="team_id">Team</label>
                <select id="team_id" name="team_id">
                    <option value="">Selectionner une team</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id']; ?>" <?= (string)$teamId === (string)$team['id'] ? 'selected' : ''; ?>><?= h((string)$team['nom']); ?></option>
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
                <th>Team</th>
                <th>Date creation</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="6" class="hint">Aucun utilisateur.</td></tr>
        <?php else: ?>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= (int)$item['id']; ?></td>
                    <td><?= h((string)$item['nom']); ?></td>
                    <td><?= h((string)$item['email']); ?></td>
                    <td><span class="<?= roleBadgeClass((string)$item['role']); ?>"><?= h(roleLabel((string)$item['role'])); ?></span></td>
                    <td><?= h((string)($item['team_name'] ?? '-')); ?></td>
                    <td><?= h((string)$item['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
