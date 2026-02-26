<?php
$pageTitle = 'Modifier une ressource';
$activeNav = 'dashboard';
$pageSubtitle = 'Edition de la ressource #' . (int)$id;
$topActions = '<a class="btn btn-ghost" href="index.php">Retour liste</a>';
require __DIR__ . '/../layout/app_start.php';
?>
<div class="card">
    <h3>Formulaire d edition</h3>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= h($error); ?></div>
    <?php endforeach; ?>

    <form method="post" action="edit.php?id=<?= (int)$id; ?>">
        <div class="form-grid">
            <div class="form-group full">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= h((string)$data['nom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="categorie">Categorie</label>
                <input type="text" id="categorie" name="categorie" list="categorie-list" value="<?= h((string)$data['categorie']); ?>" required>
                <datalist id="categorie-list">
                    <?php foreach ($categorySuggestions as $suggestion): ?>
                        <option value="<?= h($suggestion); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label for="quantite">Quantite</label>
                <input type="number" id="quantite" name="quantite" min="0" value="<?= h((string)$data['quantite']); ?>" required>
            </div>

            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut" required>
                    <?php foreach ($allowedStatuts as $option): ?>
                        <option value="<?= h($option); ?>" <?= (string)$data['statut'] === $option ? 'selected' : ''; ?>><?= h($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="localisation">Localisation</label>
                <input type="text" id="localisation" name="localisation" value="<?= h((string)$data['localisation']); ?>" required>
            </div>
        </div>

        <div class="toolbar" style="margin-top: 14px;">
            <button class="btn btn-primary" type="submit">Mettre a jour</button>
            <a class="btn btn-ghost" href="index.php">Annuler</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/app_end.php'; ?>
