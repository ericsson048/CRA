<?php
$pageTitle = 'Ajouter une ressource';
$activeNav = 'create';
$pageSubtitle = 'Ressources humaines et materielles';
$topActions = '<a class="btn btn-ghost" href="index.php">Retour liste</a>';
require __DIR__ . '/../layout/app_start.php';
?>
<div class="card">
    <h3>Contexte de gestion</h3>
    <p class="hint">Utilise une categorie commencant par <strong>RH -</strong> pour les ressources humaines (planning, taches) et <strong>RM -</strong> pour le materiel.</p>
</div>

<div class="card">
    <h3>Formulaire</h3>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= h($error); ?></div>
    <?php endforeach; ?>

    <form method="post" action="create.php">
        <div class="form-grid">
            <div class="form-group full">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= h((string)$data['nom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="categorie">Categorie</label>
                <input type="text" id="categorie" name="categorie" list="categorie-list" value="<?= h((string)$data['categorie']); ?>" placeholder="Ex: RH - Developpeur" required>
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
                <input type="text" id="localisation" name="localisation" value="<?= h((string)$data['localisation']); ?>" placeholder="Ex: Sprint Team A / Salle IT" required>
            </div>
        </div>

        <div class="toolbar" style="margin-top: 14px;">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-ghost" href="index.php">Annuler</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../layout/app_end.php'; ?>
