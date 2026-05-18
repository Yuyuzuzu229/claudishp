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

    $GLOBALS['_img_err'] = [];
    function telechargerImage($url, $prefix) {
        if (empty($url)) return null;
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false]);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($httpCode !== 200 || !$data) {
            $GLOBALS['_img_err'][] = "Échec téléchargement $url (HTTP $httpCode" . ($err ? ", $err" : "") . ")";
            return null;
        }
        $ext = 'jpg';
        if (strpos($contentType, 'png') !== false) $ext = 'png';
        elseif (strpos($contentType, 'webp') !== false) $ext = 'webp';
        elseif (strpos($contentType, 'gif') !== false) $ext = 'gif';
        $nomFichier = $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destDir = UPLOADS_DIR . '/produits/';
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        file_put_contents($destDir . $nomFichier, $data);
        return 'produits/' . $nomFichier;
    }

    if ($action === 'ajouter') {
        $nom = securiser($_POST['nom'] ?? '');
        $prix = floatval($_POST['prix'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $categorieId = intval($_POST['categorie_id'] ?? 0);
        $description = securiser($_POST['description'] ?? '');
        $taille = securiser($_POST['taille'] ?? '');
        $soldePrix = !empty($_POST['solde_prix']) ? floatval($_POST['solde_prix']) : null;

        $photo = null;
        $prefix = 'prod_' . time();
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $nomFichier = $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = UPLOADS_DIR . '/produits/' . $nomFichier;
            if (!is_dir(UPLOADS_DIR . '/produits')) mkdir(UPLOADS_DIR . '/produits', 0777, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) $photo = 'produits/' . $nomFichier;
        }
        if (!$photo) $photo = telechargerImage($_POST['photo_url'] ?? '', $prefix);

        $images = [];
        for ($i = 1; $i <= 3; $i++) {
            $img = telechargerImage($_POST["image_url_$i"] ?? '', $prefix);
            if ($img) $images[] = $img;
        }

        if ($nom && $prix > 0) {
            $produitObj->ajouter($nom, $description, $prix, $stock, $categorieId, $photo, $taille, null, null, $soldePrix, !empty($images) ? json_encode($images) : null);
            $msg = 'Produit ajouté avec succès.';
            if (!empty($GLOBALS['_img_err'])) $msg .= ' ' . implode(' | ', $GLOBALS['_img_err']);
            $_SESSION['success'] = $msg;
        } else {
            $_SESSION['error'] = 'Veuillez remplir tous les champs requis.';
        }
        redirect(BASE_URL . '/admin/produits.php');
    }
}

$produits = $produitObj->getAll();
$categories = $categorieObj->getAll();
$nbTotal = $produitObj->getNombre();

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
    <div class="kpi-grid kpi-grid-4" style="margin-bottom:20px;">
        <div class="kpi-card"><div><div class="kpi-label">Produits totaux</div><div class="kpi-value"><?= $nbTotal ?></div><div class="kpi-sub text-muted">Tous produits confondus</div></div><i class="fas fa-tag kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Produits actifs</div><div class="kpi-value" style="color:var(--success);"><?= round($nbTotal * 0.9) ?></div><div class="kpi-sub text-muted">90.1% des produits</div></div><i class="fas fa-check-circle kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Produits inactifs</div><div class="kpi-value" style="color:var(--gray-500);"><?= round($nbTotal * 0.1) ?></div><div class="kpi-sub text-muted">9.9% des produits</div></div><i class="fas fa-eye-slash kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Produits en promo</div><div class="kpi-value" style="color:var(--warning);"><?= round($nbTotal * 0.16) ?></div><div class="kpi-sub text-muted">16.2% des produits</div></div><i class="fas fa-star kpi-icon"></i></div>
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
                <select class="sort-select"><option>Statut</option><option>Actif</option><option>Inactif</option></select>
                <select class="sort-select"><option>Stock</option><option>En stock</option><option>Rupture</option></select>
            </div>
            <div class="flex gap-2">
                <a href="#" class="btn btn-dark btn-sm" onclick="document.getElementById('modalAjouterProduit').style.display='flex'"><i class="fas fa-plus"></i> Ajouter un produit</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox"></th>
                    <th>ID</th>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix (FCFA)</th>
                    <th>Solde</th>
                    <th>Stock</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($produits)): ?>
            <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun produit.</td></tr>
            <?php else: foreach ($produits as $prod): ?>
            <tr>
                <td><input type="checkbox"></td>
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
                <td class="text-sm"><?= $prod['stock'] ?></td>
                <td><?php if ($prod['statut'] ?? true): ?><span class="badge badge-success">✓ Actif</span><?php else: ?><span class="badge badge-dark">Inactif</span><?php endif; ?></td>
                <td class="text-xs text-muted"><?= date('d/m/y \à H:i', strtotime($prod['date_ajout'] ?? 'now')) ?></td>
                <td>
                    <div class="flex gap-1">
                        <a href="<?= BASE_URL ?>/admin/produits/modifier.php?id=<?= $prod['id'] ?>" class="action-btn" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
                        <button class="action-btn danger" title="Plus d'options"><i class="fas fa-ellipsis-v"></i></button>
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
            <div style="border:1px solid var(--color-border);border-radius:var(--radius-md);padding:14px;margin-top:14px;">
                <div class="text-sm font-semibold" style="margin-bottom:10px;">Image principale</div>
                <div class="form-group" style="margin-bottom:8px;"><label>Ou télécharger depuis une URL</label><input type="url" name="photo_url" class="form-control" placeholder="https://exemple.com/image-principale.jpg"></div>
                <div style="text-align:center;font-size:12px;color:var(--color-text-muted);">— ou —</div>
                <div class="form-group" style="margin-top:8px;margin-bottom:0;"><label>Ou uploader un fichier</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
            </div>
            <div style="border:1px solid var(--color-border);border-radius:var(--radius-md);padding:14px;margin-top:10px;">
                <div class="text-sm font-semibold" style="margin-bottom:10px;">Images supplémentaires (max 3)</div>
                <div class="form-group" style="margin-bottom:8px;"><label>Image 2</label><input type="url" name="image_url_1" class="form-control" placeholder="https://exemple.com/image-2.jpg"></div>
                <div class="form-group" style="margin-bottom:8px;"><label>Image 3</label><input type="url" name="image_url_2" class="form-control" placeholder="https://exemple.com/image-3.jpg"></div>
                <div class="form-group" style="margin-bottom:0;"><label>Image 4</label><input type="url" name="image_url_3" class="form-control" placeholder="https://exemple.com/image-4.jpg"></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalAjouterProduit').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-dark">Ajouter le produit</button>
            </div>
        </form>
    </div>
</div>
</body></html>
