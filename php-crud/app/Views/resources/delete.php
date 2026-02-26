<?php
$pageTitle = 'Supprimer une ressource';
$activeNav = 'dashboard';
$pageSubtitle = 'Confirmation requise';
$topActions = '<a class="btn btn-ghost" href="index.php">Retour liste</a>';
require __DIR__ . '/../layout/app_start.php';
?>
<div class="card">
    <h3>Suppression</h3>
    <div class="alert alert-error">Cette action est irreversible. Verifie les informations avant de confirmer.</div>
    <div class="table-wrap">
        <table>
            <tbody>
                <tr><th>ID</th><td><?= (int)$resource['id']; ?></td></tr>
                <tr><th>Nom</th><td><?= h((string)$resource['nom']); ?></td></tr>
                <tr><th>Categorie</th><td><?= h((string)$resource['categorie']); ?></td></tr>
                <tr><th>Quantite</th><td><?= (int)$resource['quantite']; ?></td></tr>
                <tr><th>Statut</th><td><?= h((string)$resource['statut']); ?></td></tr>
                <tr><th>Localisation</th><td><?= h((string)$resource['localisation']); ?></td></tr>
            </tbody>
        </table>
    </div>

    <form method="post" action="delete.php?id=<?= (int)$resource['id']; ?>" style="margin-top: 14px;">
        <input type="hidden" name="action" value="delete">
        <div class="toolbar">
            <button class="btn btn-danger" type="submit">Confirmer la suppression</button>
            <a class="btn btn-ghost" href="index.php">Annuler</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/app_end.php'; ?>
