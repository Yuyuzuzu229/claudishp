<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Categorie.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Gestion Produits';
$produitObj = new Produit();
$categorieObj = new Categorie();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $nom = securiser($_POST['nom'] ?? '');
        $prix = floatval($_POST['prix'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $categorieId = intval($_POST['categorie_id'] ?? 0);
        $description = securiser($_POST['description'] ?? '');
        $taille = securiser($_POST['taille'] ?? '');
        $soldePrix = !empty($_POST['solde_prix']) ? floatval($_POST['solde_prix']) : null;

        $photo = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $nomFichier = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = UPLOADS_DIR . '/produits/' . $nomFichier;
            if (!is_dir(UPLOADS_DIR . '/produits')) mkdir(UPLOADS_DIR . '/produits', 0777, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) $photo = 'produits/' . $nomFichier;
        }

        if ($nom && $prix > 0) {
            $produitObj->ajouter($nom, $description, $prix, $stock, $categorieId, $photo, $taille, null, null, $soldePrix, null);
            $_SESSION['success'] = 'Produit ajouté avec succès.';
        } else {
            $_SESSION['error'] = 'Veuillez remplir tous les champs requis.';
        }
        redirect(BASE_URL . '/admin/produits.php');
    }

    if ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $produitObj->supprimer($id);
            $_SESSION['success'] = 'Produit supprimé avec succès.';
        } else {
            $_SESSION['error'] = 'Produit introuvable.';
        }
        redirect(BASE_URL . '/admin/produits.php');
    }
}

$search = isset($_GET['q']) ? securiser($_GET['q']) : '';
if ($search) {
    $produits = $produitObj->search($search);
    $nbTotal = count($produits);
} else {
    $produits = $produitObj->getAll();
    $nbTotal = $produitObj->getNombre();
}
$categories = $categorieObj->getAll();

require_once __DIR__ . '/../includes/header.php';
$adminPage = 'produits';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Gestion</div>
        <h1 class="dash-page-title">Produits</h1>
        <p class="dash-page-sub">Gérez votre catalogue de produits</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <!-- KPI -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-card--navy"><div><div class="kpi-label">Produits totaux</div><div class="kpi-value"><?= $nbTotal ?></div><div class="kpi-sub text-muted">Tous produits confondus</div></div><i class="fas fa-tag kpi-icon"></i></div>
        <div class="kpi-card kpi-card--green"><div><div class="kpi-label">Produits actifs</div><div class="kpi-value"><?= round($nbTotal * 0.9) ?></div><div class="kpi-sub text-muted">90.1% des produits</div></div><i class="fas fa-check-circle kpi-icon"></i></div>
        <div class="kpi-card kpi-card--red"><div><div class="kpi-label">Produits inactifs</div><div class="kpi-value"><?= round($nbTotal * 0.1) ?></div><div class="kpi-sub text-muted">9.9% des produits</div></div><i class="fas fa-eye-slash kpi-icon"></i></div>
        <div class="kpi-card kpi-card--amber"><div><div class="kpi-label">En promo</div><div class="kpi-value"><?= round($nbTotal * 0.16) ?></div><div class="kpi-sub text-muted">16.2% des produits</div></div><i class="fas fa-star kpi-icon"></i></div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="flex gap-3 items-center flex-wrap" style="gap:10px;">
                <div class="dash-topbar-search" style="max-width:220px;flex:none;">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher un produit..." id="searchProd">
                </div>
                <select class="sort-select">
                    <option>Toutes catégories</option>
                    <?php foreach ($categories as $cat): ?><option><?= securiser($cat['nom']) ?></option><?php endforeach; ?>
                </select>

            </div>
            <div class="flex gap-2">
                <a href="#" class="btn btn-dark btn-sm" onclick="document.getElementById('modalAjouterProduit').style.display='flex'"><i class="fas fa-plus"></i> Ajouter un produit</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix (FCFA)</th>
                    <th>Solde</th>
                    <th>Taille</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($produits)): ?>
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun produit.</td></tr>
            <?php else: foreach ($produits as $prod): ?>
            <tr>
                <td class="text-xs text-muted">#P<?= $prod['id'] ?></td>
                <td>
                    <div class="flex gap-2 items-center">
                        <div class="admin-thumb" <?php if (!empty($prod['photo'])): ?>style="background-image:url('<?= UPLOADS_URL ?>/<?= $prod['photo'] ?>');background-size:cover;background-position:center;"<?php endif; ?>><?php if (empty($prod['photo'])): ?><i class="fas fa-image"></i><?php endif; ?></div>
                        <div>
                            <div class="text-sm font-semibold"><?= securiser($prod['nom']) ?></div>
                            <div class="text-xs text-muted">SKU : <?= strtoupper(substr($prod['nom'],0,3)) ?>-<?= str_pad($prod['id'],5,'0',STR_PAD_LEFT) ?></div>
                        </div>
                    </div>
                </td>
                <td class="text-sm"><?= securiser($prod['categorie_nom'] ?? '—') ?></td>
                <td class="text-sm font-semibold"><?= number_format($prod['prix'],0,',',' ') ?></td>
                <td class="text-sm"><?php if (!empty($prod['solde_prix']) && $prod['solde_prix'] > 0): ?><span class="badge badge-danger">-<?= round((1 - $prod['solde_prix']/$prod['prix'])*100) ?>%</span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                <td class="text-sm"><?= !empty($prod['taille_disponible']) ? securiser($prod['taille_disponible']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-xs text-muted"><?= date('d/m/y \à H:i', strtotime($prod['date_ajout'] ?? 'now')) ?></td>
                <td>
                    <div class="flex gap-1">
                        <a href="<?= BASE_URL ?>/admin/produits/modifier.php?id=<?= $prod['id'] ?>" class="action-btn" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
                        <form method="POST" onsubmit="return confirm('Supprimer ce produit ?')" style="display:inline;">
            <input type="hidden" name="action" value="supprimer">
            <input type="hidden" name="id" value="<?= $prod['id'] ?>">
            <button class="action-btn danger" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center;">
            <span class="text-xs text-muted">Affichage 1-<?= min(count($produits),10) ?> sur <?= $nbTotal ?> produits</span>
            <div class="pagination" style="margin-top:0;">
                <a href="#" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                <a href="#" class="page-btn active">1</a>
                <a href="#" class="page-btn">2</a>
                <a href="#" class="page-btn">3</a>
                <span class="page-btn" style="border:none;">...</span>
                <a href="#" class="page-btn">15</a>
                <a href="#" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
    </div>
</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>

<!-- MODAL AJOUTER PRODUIT -->
<div id="modalAjouterProduit" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('modalAjouterProduit').style.display='none'">✕</button>
        <h2 class="modal-title">Ajouter un produit</h2>
        <form method="POST" action="<?= BASE_URL ?>/admin/produits.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ajouter">
            <div class="form-group"><label>Nom du produit</label><input type="text" name="nom" class="form-control" required></div>
            <div class="grid-2" style="gap:12px;">
                <div class="form-group" style="margin-bottom:0;"><label>Prix (FCFA)</label><input type="number" name="prix" class="form-control" required></div>
                <div class="form-group" style="margin-bottom:0;"><label>Stock</label><input type="number" name="stock" class="form-control" required></div>
            </div>
            <div class="form-group" style="margin-top:14px;"><label>Prix soldé (FCFA) <span class="text-muted">(laisser vide si pas de solde)</span></label><input type="number" name="solde_prix" class="form-control" placeholder="Ex: 15000"></div>
            <div class="form-group" style="margin-top:14px;"><label>Catégorie</label><select name="categorie_id" class="form-control"><option value="">Sélectionner...</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= securiser($cat['nom']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Taille(s) disponibles <span class="text-muted">(optionnel)</span></label><input type="text" name="taille" class="form-control" placeholder="Ex: XS, S, M, L, XL — laisser vide pour les accessoires"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            <div class="form-group">
                <label>Image du produit</label>
                <div>
                    <label for="photoInput" class="btn btn-dark" style="cursor:pointer;margin-bottom:0;">Choisir un fichier</label>
                    <span id="photoFileName" style="margin-left:10px;font-size:.8rem;color:#888;">Aucune image</span>
                </div>
                <input type="file" name="photo" id="photoInput" accept="image/*" style="position:absolute;left:-9999px;opacity:0;width:1px;height:1px;">
            </div>
            <script>
            document.getElementById('photoInput').addEventListener('change', function(e) {
                var span = document.getElementById('photoFileName');
                if (span && this.files && this.files[0]) span.textContent = this.files[0].name;
            });
            </script>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalAjouterProduit').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-dark">Ajouter le produit</button>
            </div>
        </form>
    </div>
</div>

</body></html>
