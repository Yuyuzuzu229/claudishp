<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/HeroCollection.php';
require_once __DIR__ . '/../classes/Categorie.php';
require_once __DIR__ . '/../classes/Produit.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Collections hero';
$adminPage = 'collections';
$heroObj = new HeroCollection();
$categorieObj = new Categorie();
$produitObj = new Produit();
$success = $error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter' || $action === 'modifier') {
        $titre       = trim($_POST['titre'] ?? '');
        $tag         = trim($_POST['tag'] ?? '');
        $type        = $_POST['type'] ?? 'categorie';
        $categorieId = $type === 'categorie' ? intval($_POST['categorie_id'] ?? 0) : null;
        $produitIds  = $type === 'produits' ? ($_POST['produit_ids'] ?? '') : '';
        $ordre       = intval($_POST['ordre'] ?? 0);
        $statut      = intval($_POST['statut'] ?? 1);

        if (empty($titre)) {
            $error = 'Le titre est obligatoire.';
        } elseif ($action === 'ajouter') {
            $heroObj->ajouter($titre, $tag, $type, $categorieId, $produitIds, $ordre);
            $success = 'Collection ajoutée.';
        } else {
            $id = intval($_POST['id'] ?? 0);
            $heroObj->modifier($id, $titre, $tag, $type, $categorieId, $produitIds, $statut, $ordre);
            $success = 'Collection modifiée.';
        }
    } elseif ($action === 'supprimer') {
        $heroObj->supprimer(intval($_POST['id'] ?? 0));
        $success = 'Collection supprimée.';
    } elseif ($action === 'toggle') {
        $heroObj->toggleStatut(intval($_POST['id'] ?? 0));
        $success = 'Statut mis à jour.';
    }
}

$collections = $heroObj->getAll();
$categories  = $categorieObj->getAll();
$produits    = $produitObj->getAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Accueil</div>
        <h1 class="dash-page-title">Collections de la bannière</h1>
        <p class="dash-page-sub">Gérez les blocs « Collection Printemps », « Nouvelle Saison » affichés sur la page d'accueil.</p>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= securiser($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= securiser($error) ?></div><?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Collections (<?= count($collections) ?>)</span>
            <button class="btn btn-dark btn-sm" onclick="ouvrirModal()"><i class="fas fa-plus"></i> Ajouter</button>
        </div>
        <table>
            <thead><tr><th>Ordre</th><th>Titre</th><th>Tag</th><th>Type</th><th>Cible</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($collections)): ?>
            <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-400);">Aucune collection.</td></tr>
            <?php else: foreach ($collections as $c): ?>
            <tr>
                <td class="text-sm"><?= $c['ordre'] ?></td>
                <td class="text-sm font-semibold"><?= securiser($c['titre']) ?></td>
                <td><span class="badge badge-info"><?= securiser($c['tag']) ?></span></td>
                <td class="text-sm"><?= $c['type'] === 'categorie' ? 'Catégorie' : 'Produits' ?></td>
                <td class="text-sm text-muted">
                    <?php if ($c['type'] === 'categorie' && $c['categorie_id']):
                        $cat = $categorieObj->getById($c['categorie_id']);
                        echo securiser($cat ? $cat['nom'] : '—');
                    elseif ($c['type'] === 'produits' && $c['produit_ids']):
                        $ids = explode(',', $c['produit_ids']);
                        echo count($ids) . ' produit(s)';
                    else: echo '—';
                    endif; ?>
                </td>
                <td><?= $c['statut'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-dark">Inactive</span>' ?></td>
                <td>
                    <button class="action-btn" onclick="ouvrirModal(<?= $c['id'] ?>)" title="Modifier"><i class="fas fa-pencil-alt"></i></button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="action-btn" title="Activer/Désactiver"><i class="fas fa-<?= $c['statut'] ? 'pause' : 'play' ?>"></i></button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette collection ?');">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop</span></div>
</div>
</div>

<!-- Modal Ajout / Modification -->
<div id="modalCollection" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box" style="max-width:520px;">
        <button class="modal-close" onclick="fermerModal()">✕</button>
        <h2 class="modal-title" id="modal-title">Ajouter une collection</h2>
        <form method="POST" id="form-collection">
            <input type="hidden" name="action" id="input-action" value="ajouter">
            <input type="hidden" name="id" id="input-id" value="">

            <div class="form-group">
                <label>Titre de la collection</label>
                <input type="text" name="titre" id="input-titre" class="form-control" required placeholder="Ex: Collection Printemps">
            </div>

            <div class="form-group">
                <label>Tag / Étiquette</label>
                <input type="text" name="tag" id="input-tag" class="form-control" placeholder="Ex: Tendance, Nouveauté">
            </div>

            <div class="form-group">
                <label>Type de sélection</label>
                <select name="type" id="input-type" class="form-control" onchange="toggleType()">
                    <option value="categorie">Par catégorie</option>
                    <option value="produits">Par produits spécifiques</option>
                </select>
            </div>

            <div class="form-group" id="group-categorie">
                <label>Catégorie</label>
                <select name="categorie_id" id="input-categorie" class="form-control">
                    <option value="">— Sélectionner une catégorie —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= securiser($cat['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="group-produits" style="display:none;">
                <label>Sélectionner des produits</label>
                <select name="produit_ids" id="input-produits" class="form-control" multiple size="6">
                    <?php foreach ($produits as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= securiser($p['nom']) ?> (<?= formatPrix($p['prix']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#666;">Maintenez Ctrl pour sélectionner plusieurs produits. Un produit sera choisi aléatoirement.</small>
            </div>

            <div class="form-group">
                <label>Ordre d'affichage</label>
                <input type="number" name="ordre" id="input-ordre" class="form-control" value="0" min="0">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="statut" value="1" checked id="input-statut">
                    Active
                </label>
            </div>

            <div class="flex gap-2 justify-between" style="margin-top:20px;">
                <button type="button" class="btn btn-outline-dark" onclick="fermerModal()">Annuler</button>
                <button type="submit" class="btn btn-dark">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
var collectionsData = <?= json_encode($collections) ?>;

function toggleType() {
    var type = document.getElementById('input-type').value;
    document.getElementById('group-categorie').style.display = type === 'categorie' ? 'block' : 'none';
    document.getElementById('group-produits').style.display = type === 'produits' ? 'block' : 'none';
}

function ouvrirModal(id) {
    document.getElementById('modalCollection').style.display = 'flex';
    if (id) {
        var c = collectionsData.find(function(x) { return parseInt(x.id) === parseInt(id); });
        if (!c) return;
        document.getElementById('modal-title').textContent = 'Modifier la collection';
        document.getElementById('input-action').value = 'modifier';
        document.getElementById('input-id').value = c.id;
        document.getElementById('input-titre').value = c.titre;
        document.getElementById('input-tag').value = c.tag;
        document.getElementById('input-type').value = c.type;
        document.getElementById('input-categorie').value = c.categorie_id || '';
        document.getElementById('input-ordre').value = c.ordre;
        document.getElementById('input-statut').checked = parseInt(c.statut) === 1;

        // Select produits
        var select = document.getElementById('input-produits');
        if (c.produit_ids) {
            var ids = c.produit_ids.split(',');
            for (var i = 0; i < select.options.length; i++) {
                select.options[i].selected = ids.indexOf(select.options[i].value) !== -1;
            }
        } else {
            for (var i = 0; i < select.options.length; i++) select.options[i].selected = false;
        }
        toggleType();
    } else {
        document.getElementById('modal-title').textContent = 'Ajouter une collection';
        document.getElementById('input-action').value = 'ajouter';
        document.getElementById('input-id').value = '';
        document.getElementById('input-titre').value = '';
        document.getElementById('input-tag').value = '';
        document.getElementById('input-type').value = 'categorie';
        document.getElementById('input-categorie').value = '';
        document.getElementById('input-ordre').value = '0';
        document.getElementById('input-statut').checked = true;
        for (var i = 0; i < document.getElementById('input-produits').options.length; i++) {
            document.getElementById('input-produits').options[i].selected = false;
        }
        toggleType();
    }
}

function fermerModal() {
    document.getElementById('modalCollection').style.display = 'none';
}
</script>
</body></html>
