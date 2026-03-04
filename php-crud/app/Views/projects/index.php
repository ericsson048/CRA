<?php
if (!function_exists('projectStatusClass')) {
    function projectStatusClass(?string $status): string
    {
        if ($status === 'Termine') {
            return 'badge badge-ok';
        }
        if ($status === 'En cours') {
            return 'badge badge-warn';
        }
        if ($status === 'En pause') {
            return 'badge badge-danger';
        }
        return 'badge badge-neutral';
    }
}

if (!function_exists('projectDateLabel')) {
    function projectDateLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }
        return date('d/m/Y', $timestamp);
    }
}

$pageTitle = 'Gestion des projets';
$activeNav = 'projects';
$pageSubtitle = 'Affectation des projets aux teams et a leurs leaders';
$topActions = '<a class="btn btn-rm" href="planning.php">Voir planning</a>';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($created): ?><div class="alert alert-success">Projet cree avec succes.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error); ?></div><?php endforeach; ?>

<div class="stats">
    <div class="stat"><p class="stat-label">Projets</p><p class="stat-value"><?= count($projects); ?></p></div>
    <div class="stat"><p class="stat-label">Teams</p><p class="stat-value"><?= count($teams); ?></p></div>
    <div class="stat"><p class="stat-label">TL disponibles</p><p class="stat-value"><?= count($teamLeaders); ?></p></div>
    <div class="stat"><p class="stat-label">TLA disponibles</p><p class="stat-value"><?= count($teamLeaderAdjoints); ?></p></div>
</div>

<div class="card">
    <h3>Creer un projet</h3>
    <form method="post" action="projects.php">
        <?= csrf_field(); ?>
        <input type="hidden" name="action" value="create_project">
        <div class="form-grid">
            <div class="form-group">
                <label for="nom">Nom du projet</label>
                <input id="nom" type="text" name="nom" value="<?= h($nom); ?>" required>
            </div>
            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut" required>
                    <?php foreach ($statusOptions as $option): ?>
                        <option value="<?= h($option); ?>" <?= $statut === $option ? 'selected' : ''; ?>><?= h($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Perimetre, livrables, contexte"><?= h($description); ?></textarea>
            </div>
            <div class="form-group">
                <label for="team_id">Team</label>
                <select id="team_id" name="team_id" required>
                    <option value="">Selectionner</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int)$team['id']; ?>" <?= (string)$teamId === (string)$team['id'] ? 'selected' : ''; ?>>
                            <?= h((string)$team['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tl_user_id">Team Leader</label>
                <select id="tl_user_id" name="tl_user_id" required>
                    <option value="">Selectionner</option>
                    <?php foreach ($teamLeaders as $leader): ?>
                        <option value="<?= (int)$leader['id']; ?>" <?= (string)$tlUserId === (string)$leader['id'] ? 'selected' : ''; ?>>
                            <?= h((string)$leader['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tla_user_id">Team Leader Adjoint</label>
                <select id="tla_user_id" name="tla_user_id">
                    <option value="">Optionnel</option>
                    <?php foreach ($teamLeaderAdjoints as $leader): ?>
                        <option value="<?= (int)$leader['id']; ?>" <?= (string)$tlaUserId === (string)$leader['id'] ? 'selected' : ''; ?>>
                            <?= h((string)$leader['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">Date debut</label>
                <input id="start_date" type="date" name="start_date" value="<?= h($startDate); ?>">
            </div>
            <div class="form-group">
                <label for="end_date">Date fin</label>
                <input id="end_date" type="date" name="end_date" value="<?= h($endDate); ?>">
            </div>
        </div>
        <div class="toolbar" style="margin-top: 12px;">
            <button class="btn btn-primary" type="submit">Creer le projet</button>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Projet</th>
                <th>Team</th>
                <th>Leadership</th>
                <th>Statut</th>
                <th>Periode</th>
                <th>Assigne par</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($projects)): ?>
            <tr><td colspan="7" class="hint">Aucun projet enregistre.</td></tr>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= (int)$project['id']; ?></td>
                    <td>
                        <strong><?= h((string)$project['nom']); ?></strong>
                        <br>
                        <span class="hint"><?= h((string)($project['description'] ?? '')); ?></span>
                    </td>
                    <td><?= h((string)$project['team_name']); ?></td>
                    <td>
                        <div>TL: <?= h((string)$project['tl_name']); ?></div>
                        <div class="hint">TLA: <?= h((string)($project['tla_name'] ?? '-')); ?></div>
                    </td>
                    <td><span class="<?= projectStatusClass((string)$project['statut']); ?>"><?= h((string)$project['statut']); ?></span></td>
                    <td>
                        <div><?= h(projectDateLabel((string)($project['start_date'] ?? null))); ?></div>
                        <div class="hint">au <?= h(projectDateLabel((string)($project['end_date'] ?? null))); ?></div>
                    </td>
                    <td><?= h((string)$project['assigned_by_name']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
