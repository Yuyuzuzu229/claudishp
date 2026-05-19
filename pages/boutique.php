<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Categorie.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

$pageTitle = 'Boutique';
$produit = new Produit();
$categorie = new Categorie();
$categories = $categorie->getAll();

$categorieId = null;
$categorieArr = [];
if (isset($_GET['categorie'])) {
    if (is_array($_GET['categorie'])) {
        $categorieArr = array_map('intval', $_GET['categorie']);
        $categorieId = implode(',', $categorieArr);
    } elseif (strpos($_GET['categorie'], ',') !== false) {
        $categorieArr = array_map('intval', explode(',', $_GET['categorie']));
        $categorieId = $_GET['categorie'];
    } else {
        $categorieId = intval($_GET['categorie']);
        $categorieArr = [$categorieId];
    }
}
$recherche     = isset($_GET['recherche']) ? securiser($_GET['recherche']) : null;
$minPrix       = (isset($_GET['min_prix']) && $_GET['min_prix'] !== '') ? floatval($_GET['min_prix']) : null;
$maxPrix       = (isset($_GET['max_prix']) && $_GET['max_prix'] !== '') ? floatval($_GET['max_prix']) : null;
$soldes        = isset($_GET['soldes']) ? 1 : 0;
$taillesArr    = isset($_GET['taille']) ? (is_array($_GET['taille']) ? $_GET['taille'] : [$_GET['taille']]) : [];
$noteMinArr    = isset($_GET['note_min']) ? (is_array($_GET['note_min']) ? $_GET['note_min'] : [$_GET['note_min']]) : [];
$noteMin       = !empty($noteMinArr) ? min(array_map('intval', $noteMinArr)) : null;
$stockOnly     = isset($_GET['stock_only']) ? 1 : 0;
$pageCourante  = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
$perPage = 12;

$produits = $produit->getByCategorieWithFilters($categorieId, $recherche, $minPrix, $maxPrix, $taillesArr, null, null, $noteMin, $stockOnly, $soldes, $pageCourante, $perPage);
$totalProduits = $produit->countByCategorieWithFilters($categorieId, $recherche, $minPrix, $maxPrix, $taillesArr, null, null, $noteMin, $stockOnly, $soldes);
$totalPages = max(1, ceil($totalProduits / $perPage));
if ($pageCourante > $totalPages) { $pageCourante = $totalPages; }

$catNomActif = '';
if ($categorieId) {
    $ids = is_array($categorieArr) ? $categorieArr : [$categorieId];
    $noms = [];
    foreach ($categories as $c) {
        if (in_array($c['id'], $ids)) $noms[] = $c['nom'];
    }
    $catNomActif = implode(', ', $noms);
}

$pageTitle = $catNomActif ? $catNomActif . ' – Boutique' : 'Boutique';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="padding-top:16px;padding-bottom:48px;">
    <div class="boutique-layout">
        <!-- SIDEBAR FILTRES -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <h4>Filtres</h4>
            </div>
            <form method="GET" action="<?= BASE_URL ?>/pages/boutique.php">
                <div class="sidebar-section">
                    <h4>Catégorie</h4>
                    <?php foreach ($categories as $cat): ?>
                    <label class="sidebar-checkbox">
                        <input type="checkbox" name="categorie[]" value="<?= $cat['id'] ?>" <?= in_array($cat['id'], (array)$categorieArr) ? 'checked' : '' ?>>
                        <?= securiser($cat['nom']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="sidebar-section">
                    <h4>Taille</h4>
                    <?php foreach (['XS','S','M','L','XL','XXL'] as $s): ?>
                    <label class="sidebar-checkbox">
                        <input type="checkbox" name="taille[]" value="<?= $s ?>" <?= in_array($s, (array)$taillesArr) ? 'checked' : '' ?>>
                        <?= $s ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="sidebar-section">
                    <h4>Prix (FCFA)</h4>
                    <div class="flex gap-2" style="margin-bottom:8px;">
                        <input type="number" name="min_prix" class="form-control" placeholder="Min" value="<?= $minPrix ?? '' ?>" style="padding:7px 8px;">
                        <span style="line-height:38px;color:var(--gray-400);">—</span>
                        <input type="number" name="max_prix" class="form-control" placeholder="Max" value="<?= $maxPrix ?? '' ?>" style="padding:7px 8px;">
                    </div>
                </div>
                <div class="sidebar-section">
                    <h4>Note clients</h4>
                    <label class="sidebar-checkbox"><input type="checkbox" name="note_min[]" value="5" <?= in_array('5', (array)$noteMinArr) ? 'checked' : '' ?>> ★★★★★ (5 étoiles)</label>
                    <label class="sidebar-checkbox"><input type="checkbox" name="note_min[]" value="4" <?= in_array('4', (array)$noteMinArr) ? 'checked' : '' ?>> ★★★★☆ (4+ étoiles)</label>
                    <label class="sidebar-checkbox"><input type="checkbox" name="note_min[]" value="3" <?= in_array('3', (array)$noteMinArr) ? 'checked' : '' ?>> ★★★☆☆ (3+ étoiles)</label>
                </div>
                <div class="sidebar-section">
                    <h4>Disponibilité</h4>
                    <label class="sidebar-checkbox"><input type="checkbox" name="stock_only" value="1" <?= $stockOnly ? 'checked' : '' ?>> En stock uniquement</label>
                    <label class="sidebar-checkbox"><input type="checkbox" name="inclure_rupture" value="1"> Inclure rupture de stock</label>
                </div>
                <button type="submit" class="btn-apply">Appliquer les filtres</button>
            </form>
        </aside>

        <!-- PRODUITS -->
        <main class="boutique-main">
            <?php if (empty($produits)): ?>
            <div style="text-align:center;padding:60px 0;">
                <i class="fas fa-search" style="font-size:40px;color:var(--gray-300);margin-bottom:16px;display:block;"></i>
                <p style="font-size:16px;color:var(--gray-500);margin-bottom:16px;">Aucun produit trouvé.</p>
                <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark">Voir tous les produits</a>
            </div>
            <?php else: ?>
            <div class="grid-3">
                <?php foreach ($produits as $prod): ?>
                <div class="product-card">
                    <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $prod['id'] ?>">
                        <div class="product-image-placeholder" style="position:relative;<?php if (!empty($prod['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $prod['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>">
                            <?php if (empty($prod['photo'])): ?><i class="fas fa-image" style="font-size:32px;"></i><?php endif; ?>
                            <?php if (!empty($prod['solde_prix']) && $prod['solde_prix'] > 0): ?><span class="badge-solde-tag">-<?= round((1 - $prod['solde_prix']/$prod['prix'])*100) ?>%</span><?php endif; ?>
                            <?php if ($prod['stock'] <= 0): ?><div style="position:absolute;top:10px;right:10px;background:var(--gray-700);color:white;font-size:10px;font-weight:700;padding:3px 8px;">Rupture</div><?php endif; ?>
                        </div>
                    </a>
                    <div class="product-info">
                        <?php
                        $catName = strtolower($prod['categorie_nom'] ?? '');
                        $badgeClass = strpos($catName,'femme')!==false?'badge-femme':(strpos($catName,'homme')!==false?'badge-homme':(strpos($catName,'enfant')!==false?'badge-enfant':'badge-accessoires'));
                        ?>
                        <span class="product-badge <?= $badgeClass ?>"><?= securiser($prod['categorie_nom'] ?? '') ?></span>
                        <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $prod['id'] ?>" style="color:inherit;">
                            <h3><?= securiser($prod['nom']) ?></h3>
                        </a>
                        <?php if (!empty($prod['taille_disponible'])): ?>
                        <p class="taille"><?= securiser($prod['taille_disponible']) ?></p>
                        <?php endif; ?>
                        <div class="prix"><?= renderPrix($prod['prix'], $prod['solde_prix']) ?></div>
                        <?php if ($prod['stock'] > 0): ?>
                        <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1">
                            <input type="hidden" name="produit_id" value="<?= $prod['id'] ?>">
                            <button type="submit" class="btn btn-dark btn-sm btn-block"><i class="fas fa-shopping-bag"></i> Ajouter au panier</button>
                        </form>
                        <?php else: ?>
                        <button class="btn btn-outline-dark btn-sm btn-block" disabled style="opacity:.5;cursor:default;">Rupture de stock</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
            <?php
            // Build base query string preserving filters
            $qsParams = [];
            if ($categorieId) $qsParams[] = 'categorie=' . $categorieId;
            if ($recherche)   $qsParams[] = 'recherche=' . urlencode($recherche);
            if ($minPrix !== null) $qsParams[] = 'min_prix=' . $minPrix;
            if ($maxPrix !== null) $qsParams[] = 'max_prix=' . $maxPrix;
            if ($soldes)      $qsParams[] = 'soldes=1';
            $qsBase = $qsParams ? implode('&', $qsParams) . '&' : '';
            $pageUrl = BASE_URL . '/pages/boutique.php?' . $qsBase;
            ?>
            <div class="pagination">
                <?php if ($pageCourante > 1): ?>
                <a href="<?= $pageUrl ?>page=<?= $pageCourante - 1 ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php
                $afficher = [];
                for ($i = 1; $i <= $totalPages; $i++) {
                    if ($i == 1 || $i == $totalPages || ($i >= $pageCourante - 2 && $i <= $pageCourante + 2)) {
                        $afficher[] = $i;
                    } elseif (end($afficher) !== '...') {
                        $afficher[] = '...';
                    }
                }
                foreach ($afficher as $p):
                    if ($p === '...'): ?>
                <span class="page-btn dots">...</span>
                    <?php elseif ($p == $pageCourante): ?>
                <a href="<?= $pageUrl ?>page=<?= $p ?>" class="page-btn active"><?= $p ?></a>
                    <?php else: ?>
                <a href="<?= $pageUrl ?>page=<?= $p ?>" class="page-btn"><?= $p ?></a>
                    <?php endif;
                endforeach; ?>

                <?php if ($pageCourante < $totalPages): ?>
                <a href="<?= $pageUrl ?>page=<?= $pageCourante + 1 ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- FOOTER MINI -->
<div class="container" style="padding:20px 24px;border-top:1px solid var(--gray-200);text-align:center;">
    <p class="text-xs text-muted">CLAUDI SHOP – Mode &amp; Accessoires Homme / Femme / Enfant – Cotonou, Bénin</p>
    <p class="text-xs text-muted">&copy; <?= date('Y') ?> Claudi Shop · Paiement MTN MoMo &amp; Moov Money · Livraison partout au Bénin</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
