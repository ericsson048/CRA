<?php
if (!function_exists('taskStatusClass')) {
    function taskStatusClass(?string $status): string
    {
        if ($status === 'Terminee') {
            return 'badge badge-ok';
        }
        if ($status === 'En cours') {
            return 'badge badge-warn';
        }
        return 'badge badge-neutral';
    }
}

if (!function_exists('priorityClass')) {
    function priorityClass(?string $priority): string
    {
        if ($priority === 'Haute') {
            return 'badge badge-danger';
        }
        if ($priority === 'Moyenne') {
            return 'badge badge-warn';
        }
        return 'badge badge-ok';
    }
}

$pageTitle = 'Planning des taches';
$activeNav = 'planning';
$pageSubtitle = 'Workflow entreprise: projet -> TL/TLA -> taches developpeurs';
$topActions = '<a class="btn btn-ghost" href="index.php">Voir ressources</a>';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($created): ?><div class="alert alert-success">Tache ajoutee au planning.</div><?php endif; ?>
<?php if ($updated): ?><div class="alert alert-success">Statut de la tache mis a jour.</div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error); ?></div><?php endforeach; ?>

<div class="stats">
    <div class="stat"><p class="stat-label">A faire</p><p class="stat-value"><?= (int)$stats['todo']; ?></p></div>
    <div class="stat"><p class="stat-label">En cours</p><p class="stat-value"><?= (int)$stats['doing']; ?></p></div>
    <div class="stat"><p class="stat-label">Terminees</p><p class="stat-value"><?= (int)$stats['done']; ?></p></div>
</div>

<?php if ($canCreateTasks): ?>
    <div class="card">
        <h3>Nouvelle tache</h3>
        <form method="post" action="planning.php">
            <input type="hidden" name="action" value="create_task">
            <div class="form-grid">
                <div class="form-group full">
                    <label for="title">Titre</label>
                    <input id="title" type="text" name="title" required>
                </div>
                <div class="form-group full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Objectif, contexte, livrable"></textarea>
                </div>
                <div class="form-group">
                    <label for="project_id">Projet</label>
                    <select id="project_id" name="project_id" required>
                        <option value="">Selectionner</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= (int)$project['id']; ?>"><?= h((string)$project['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assigned_user_id">Developpeur assigne</label>
                    <select id="assigned_user_id" name="assigned_user_id" required>
                        <option value="">Selectionner</option>
                        <?php foreach ($developers as $developer): ?>
                            <option value="<?= (int)$developer['id']; ?>"><?= h((string)$developer['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="resource_id">Ressource liee (optionnel)</label>
                    <select id="resource_id" name="resource_id">
                        <option value="">Aucune ressource</option>
                        <?php foreach ($resources as $resource): ?>
                            <option value="<?= (int)$resource['id']; ?>"><?= h((string)$resource['categorie']); ?> - <?= h((string)$resource['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priority">Priorite</label>
                    <select id="priority" name="priority" required>
                        <?php foreach ($priorityOptions as $option): ?>
                            <option value="<?= h($option); ?>"><?= h($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="due_date">Date limite</label>
                    <input id="due_date" type="date" name="due_date">
                </div>
            </div>
            <div class="toolbar" style="margin-top: 12px;">
                <button class="btn btn-primary" type="submit">Ajouter la tache</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Tache</th>
                <th>Assigne a</th>
                <th>Projet</th>
                <th>Ressource liee</th>
                <th>Priorite</th>
                <th>Statut</th>
                <th>Echeance</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tasks)): ?>
            <tr><td colspan="8" class="hint">Aucune tache de planning.</td></tr>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <?php
                    $canUpdateThisTask = $canUpdateAnyTask
                        || ($canUpdateOwnTask && (int)$task['assigned_user_id'] === (int)($sessionUser['id'] ?? 0))
                        || $canUpdateLeadScope;
                    $dueDate = $task['due_date'] ? date('d/m/Y', strtotime((string)$task['due_date'])) : '-';
                ?>
                <tr>
                    <td>
                        <strong><?= h((string)$task['titre']); ?></strong>
                        <br>
                        <span class="hint"><?= h((string)($task['description'] ?? '')); ?></span>
                    </td>
                    <td><?= h((string)$task['assigned_name']); ?></td>
                    <td><?= h((string)($task['project_name'] ?? '-')); ?></td>
                    <td><?= h((string)($task['resource_name'] ?? '-')); ?></td>
                    <td><span class="<?= priorityClass((string)$task['priorite']); ?>"><?= h((string)$task['priorite']); ?></span></td>
                    <td><span class="<?= taskStatusClass((string)$task['statut']); ?>"><?= h((string)$task['statut']); ?></span></td>
                    <td><?= h($dueDate); ?></td>
                    <td>
                        <?php if ($canUpdateThisTask): ?>
                            <form method="post" class="inline" action="planning.php">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="task_id" value="<?= (int)$task['id']; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <?php foreach ($statusOptions as $option): ?>
                                        <option value="<?= h($option); ?>" <?= (string)$task['statut'] === $option ? 'selected' : ''; ?>><?= h($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php else: ?>
                            <span class="hint">Lecture seule</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
