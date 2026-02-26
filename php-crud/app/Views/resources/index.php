<?php
$pageTitle = 'Tableau ressources';
$activeNav = 'dashboard';
$pageSubtitle = 'Ressources humaines, materielles et planning';
$topActions = '';
if ($canManage) {
    $topActions .= '<a class="btn btn-primary" href="create.php">Nouvelle ressource</a>';
}
$topActions .= '<a class="btn btn-rm" href="planning.php">Planning</a>';
$queryExcel = ['format' => 'excel'];
$queryPdf = ['format' => 'pdf'];
if ($search !== '') {
    $queryExcel['q'] = $search;
    $queryPdf['q'] = $search;
}
$topActions .= '<a class="btn btn-ghost" href="export.php?' . h(http_build_query($queryExcel)) . '">Export CSV</a>';
$topActions .= '<a class="btn btn-ghost" href="export.php?' . h(http_build_query($queryPdf)) . '">Export PDF</a>';

require __DIR__ . '/../layout/app_start.php';

if (!function_exists('statusBadgeClass')) {
    function statusBadgeClass(?string $status): string
    {
        $normalized = strtolower(trim((string)$status));
        if (strpos($normalized, 'dispon') !== false) {
            return 'badge badge-ok';
        }
        if (strpos($normalized, 'mainten') !== false) {
            return 'badge badge-warn';
        }
        if (strpos($normalized, 'indispo') !== false) {
            return 'badge badge-danger';
        }
        return 'badge badge-neutral';
    }
}

if (!function_exists('formatDateFr')) {
    function formatDateFr(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }
        return date('d/m/Y H:i', $ts);
    }
}

if (!function_exists('pageUrl')) {
    function pageUrl(int $targetPage, string $search): string
    {
        $query = ['page' => $targetPage];
        if ($search !== '') {
            $query['q'] = $search;
        }
        return 'index.php?' . http_build_query($query);
    }
}
?>

<?php if ($created): ?><div class="alert alert-success">Ressource ajoutee avec succes.</div><?php endif; ?>
<?php if ($updated): ?><div class="alert alert-success">Ressource mise a jour avec succes.</div><?php endif; ?>
<?php if ($deleted): ?><div class="alert alert-success">Ressource supprimee avec succes.</div><?php endif; ?>

<div class="stats">
    <div class="stat"><p class="stat-label">Total ressources</p><p class="stat-value"><?= (int)$totalRows; ?></p></div>
    <div class="stat"><p class="stat-label">Ressources humaines</p><p class="stat-value"><?= (int)$rhCount; ?></p></div>
    <div class="stat"><p class="stat-label">Ressources materielles</p><p class="stat-value"><?= (int)$rmCount; ?></p></div>
    <div class="stat"><p class="stat-label">Taches planning ouvertes</p><p class="stat-value"><?= (int)$planningOpenCount; ?></p></div>
</div>

<div class="card">
    <form method="get" action="index.php">
        <div class="form-grid">
            <div class="form-group full">
                <label for="q">Recherche</label>
                <input type="text" id="q" name="q" value="<?= h($search); ?>" placeholder="Nom, categorie RH/RM, statut, localisation">
            </div>
        </div>
        <div class="toolbar" style="margin-top: 12px;">
            <button class="btn btn-primary" type="submit">Filtrer</button>
            <a class="btn btn-ghost" href="index.php">Reinitialiser</a>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Categorie</th>
                <th>Quantite</th>
                <th>Statut</th>
                <th>Localisation</th>
                <th>Date creation</th>
                <?php if ($canManage): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($resources)): ?>
            <tr><td colspan="<?= $canManage ? 8 : 7; ?>" class="hint">Aucune ressource trouvee.</td></tr>
        <?php else: ?>
            <?php foreach ($resources as $resource): ?>
                <tr>
                    <td><?= (int)$resource['id']; ?></td>
                    <td><?= h((string)$resource['nom']); ?></td>
                    <td><?= h((string)$resource['categorie']); ?></td>
                    <td><?= (int)$resource['quantite']; ?></td>
                    <td><span class="<?= statusBadgeClass((string)$resource['statut']); ?>"><?= h((string)$resource['statut']); ?></span></td>
                    <td><?= h((string)$resource['localisation']); ?></td>
                    <td><?= h(formatDateFr((string)$resource['created_at'])); ?></td>
                    <?php if ($canManage): ?>
                        <td>
                            <div class="toolbar">
                                <a class="btn btn-ghost" href="edit.php?id=<?= (int)$resource['id']; ?>">Modifier</a>
                                <a class="btn btn-danger" href="delete.php?id=<?= (int)$resource['id']; ?>">Supprimer</a>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a class="btn btn-ghost" href="<?= h(pageUrl($page - 1, (string)$search)); ?>">Precedent</a><?php endif; ?>
        <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($p = $startPage; $p <= $endPage; $p++):
        ?>
            <?php if ($p === $page): ?>
                <span class="btn active"><?= (int)$p; ?></span>
            <?php else: ?>
                <a class="btn btn-ghost" href="<?= h(pageUrl($p, (string)$search)); ?>"><?= (int)$p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a class="btn btn-ghost" href="<?= h(pageUrl($page + 1, (string)$search)); ?>">Suivant</a><?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
