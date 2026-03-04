<?php
$pageTitle = 'Gestion des utilisateurs';
$activeNav = 'users';
$pageSubtitle = 'Admin/gestionnaire: creation TL, TLA et developpeurs';
$topActions = '<a class="btn btn-ghost" href="index.php">Retour ressources</a>';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($created): ?><div class="alert alert-success">Utilisateur cree avec succes.</div><?php endif; ?>
<?php if ($activated): ?><div class="alert alert-success">Compte active.</div><?php endif; ?>
<?php if ($deactivated): ?><div class="alert alert-success">Compte desactive.</div><?php endif; ?>
<?php if ($passwordReset): ?><div class="alert alert-success">Mot de passe reinitialise. L utilisateur devra le changer a la prochaine connexion.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error); ?></div><?php endforeach; ?>

<div class="card">
    <h3>Creer un utilisateur</h3>
    <p class="hint">Les developpeurs sont rattaches a une team. Le gestionnaire assigne ensuite les projets aux TL/TLA.</p>

    <form method="post" action="register.php">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="create_user">
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
                <input id="password" type="password" name="password" minlength="12" required>
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
                <th>Etat</th>
                <th>Team</th>
                <th>Derniere connexion</th>
                <th>Date creation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="9" class="hint">Aucun utilisateur.</td></tr>
        <?php else: ?>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= (int)$item['id']; ?></td>
                    <td><?= h((string)$item['nom']); ?></td>
                    <td><?= h((string)$item['email']); ?></td>
                    <td><span class="<?= roleBadgeClass((string)$item['role']); ?>"><?= h(roleLabel((string)$item['role'])); ?></span></td>
                    <td><span class="<?= activeBadgeClass((bool)$item['is_active']); ?>"><?= (bool)$item['is_active'] ? 'Actif' : 'Inactif'; ?></span></td>
                    <td><?= h((string)($item['team_name'] ?? '-')); ?></td>
                    <td><?= h((string)($item['last_login_at'] ?? '-')); ?></td>
                    <td><?= h((string)$item['created_at']); ?></td>
                    <td>
                        <div class="toolbar">
                            <form method="post" action="register.php" class="inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="user_id" value="<?= (int)$item['id']; ?>">
                                <input type="hidden" name="enable" value="<?= (bool)$item['is_active'] ? '0' : '1'; ?>">
                                <button type="submit" class="btn btn-ghost"><?= (bool)$item['is_active'] ? 'Desactiver' : 'Activer'; ?></button>
                            </form>
                            <form method="post" action="register.php" class="inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= (int)$item['id']; ?>">
                                <input type="password" name="reset_password" minlength="12" placeholder="Mot de passe temporaire" required>
                                <button type="submit" class="btn btn-primary">Reset MDP</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
