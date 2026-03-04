<?php
if (!function_exists('teamMemberLabel')) {
    function teamMemberLabel(int $count): string
    {
        return $count > 1 ? $count . ' membres' : $count . ' membre';
    }
}

$pageTitle = 'Gestion des teams';
$activeNav = 'teams';
$pageSubtitle = 'Creation des teams et affectation des membres';
$topActions = '<a class="btn btn-ghost" href="register.php">Voir utilisateurs</a>';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($created): ?><div class="alert alert-success">Team creee avec succes.</div><?php endif; ?>
<?php if ($assigned): ?><div class="alert alert-success">Membre affecte a la team.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error); ?></div><?php endforeach; ?>

<div class="stats">
    <div class="stat"><p class="stat-label">Teams</p><p class="stat-value"><?= count($teams); ?></p></div>
    <div class="stat"><p class="stat-label">Leaders disponibles</p><p class="stat-value"><?= count($leaders); ?></p></div>
    <div class="stat"><p class="stat-label">Membres assignables</p><p class="stat-value"><?= count($assignableUsers); ?></p></div>
</div>

<div class="card">
    <h3>Creer une team</h3>
    <form method="post" action="teams.php">
        <input type="hidden" name="action" value="create_team">
        <div class="form-grid">
            <div class="form-group">
                <label for="nom">Nom</label>
                <input id="nom" type="text" name="nom" value="<?= h($teamNom); ?>" required>
            </div>
            <div class="form-group">
                <label for="tl_user_id">Team Leader</label>
                <select id="tl_user_id" name="tl_user_id" required>
                    <option value="">Selectionner</option>
                    <?php foreach ($leaders as $leader): ?>
                        <?php if ((string)$leader['role'] !== 'team_leader') { continue; } ?>
                        <option value="<?= (int)$leader['id']; ?>" <?= (string)$tlUserId === (string)$leader['id'] ? 'selected' : ''; ?>>
                            <?= h((string)$leader['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Mission, perimetre, objectifs"><?= h($teamDescription); ?></textarea>
            </div>
            <div class="form-group">
                <label for="tla_user_id">Team Leader Adjoint</label>
                <select id="tla_user_id" name="tla_user_id">
                    <option value="">Optionnel</option>
                    <?php foreach ($leaders as $leader): ?>
                        <?php if ((string)$leader['role'] !== 'team_leader_adjoint') { continue; } ?>
                        <option value="<?= (int)$leader['id']; ?>" <?= (string)$tlaUserId === (string)$leader['id'] ? 'selected' : ''; ?>>
                            <?= h((string)$leader['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="toolbar" style="margin-top: 12px;">
            <button class="btn btn-primary" type="submit">Creer la team</button>
        </div>
    </form>
</div>

<div class="card">
    <h3>Affecter un membre</h3>
    <p class="hint">Les roles TL, TLA et developpeur peuvent etre rattaches a une team.</p>
    <form method="post" action="teams.php">
        <input type="hidden" name="action" value="assign_member">
        <div class="form-grid">
            <div class="form-group">
                <label for="team_id">Team</label>
                <select id="team_id" name="team_id" required>
                    <option value="">Selectionner</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id']; ?>"><?= h((string)$team['nom']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="user_id">Utilisateur</label>
                <select id="user_id" name="user_id" required>
                    <option value="">Selectionner</option>
                    <?php foreach ($assignableUsers as $user): ?>
                        <option value="<?= (int)$user['id']; ?>">
                            <?= h((string)$user['nom']); ?> - <?= h(roleLabel((string)$user['role'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="toolbar" style="margin-top: 12px;">
            <button class="btn btn-primary" type="submit">Affecter</button>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Team</th>
                <th>Leadership</th>
                <th>Description</th>
                <th>Membres</th>
                <th>Date creation</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($teams)): ?>
            <tr><td colspan="6" class="hint">Aucune team enregistree.</td></tr>
        <?php else: ?>
            <?php foreach ($teams as $team): ?>
                <tr>
                    <td><?= (int)$team['id']; ?></td>
                    <td>
                        <strong><?= h((string)$team['nom']); ?></strong>
                    </td>
                    <td>
                        <div>TL: <?= h((string)($team['tl_name'] ?? '-')); ?></div>
                        <div class="hint">TLA: <?= h((string)($team['tla_name'] ?? '-')); ?></div>
                    </td>
                    <td><?= h((string)($team['description'] ?? '-')); ?></td>
                    <td><?= h(teamMemberLabel((int)$team['members_count'])); ?></td>
                    <td><?= h((string)$team['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
