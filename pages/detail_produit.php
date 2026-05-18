<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Avis.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

$produitId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$produitId) { redirect(BASE_URL . '/pages/boutique.php'); }

$produitObj = new Produit();
$produit = $produitObj->getById($produitId);
if (!$produit) { redirect(BASE_URL . '/pages/boutique.php'); }

$avisObj = new Avis();
$avisListe = $avisObj->getByProduit($produitId);
$notesMoyenne = count($avisListe) ? round(array_sum(array_column($avisListe,'note')) / count($avisListe), 1) : 0;

$similaires = $produitObj->getByCategorieWithFilters($produit['categorie_id'] ?? null);
$similaires = array_filter($similaires, function($p) use ($produitId) { return $p['id'] != $produitId; });
$similaires = array_slice($similaires, 0, 4);

$pageTitle = $produit['nom'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="padding-top:28px;padding-bottom:56px;">
    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="<?= BASE_URL ?>/index.php">Accueil</a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= BASE_URL ?>/pages/boutique.php">Boutique</a>
        <span class="breadcrumb-sep">/</span>
        <?php if (!empty($produit['categorie_nom'])): ?>
        <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=<?= $produit['categorie_id'] ?>"><?= securiser($produit['categorie_nom']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <?php endif; ?>
        <span><?= securiser($produit['nom']) ?></span>
    </nav>

    <?php
    $imagesListe = [$produit['photo']];
    $extra = !empty($produit['images']) ? json_decode($produit['images'], true) : [];
    if (is_array($extra)) $imagesListe = array_merge($imagesListe, $extra);
    $imagesListe = array_filter($imagesListe);
    ?>
    <div class="product-detail-layout">
        <!-- GALLERY -->
        <div class="product-gallery">
            <div class="product-gallery-main" id="main-gallery-img">
                <?php if (!empty($imagesListe[0])): ?>
                <img src="<?= UPLOADS_URL . '/' . securiser($imagesListe[0]) ?>" alt="<?= securiser($produit['nom']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                <i class="fas fa-image" style="font-size:56px;color:var(--gray-300);"></i>
                <?php endif; ?>
            </div>
            <?php if (count($imagesListe) > 1): ?>
            <div class="product-gallery-thumbs">
                <?php foreach ($imagesListe as $i => $img): ?>
                <div class="product-gallery-thumb <?= $i === 0 ? 'active' : '' ?>" onclick="changeImage(this, '<?= UPLOADS_URL . '/' . securiser($img) ?>')" style="overflow:hidden;">
                    <img src="<?= UPLOADS_URL . '/' . securiser($img) ?>" alt="" style="width:100%;height:100%;object-fit:cover;cursor:pointer;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- INFOS PRODUIT -->
        <div>
            <?php
            $catName = strtolower($produit['categorie_nom'] ?? '');
            $badgeClass = strpos($catName,'femme')!==false?'badge-femme':(strpos($catName,'homme')!==false?'badge-homme':(strpos($catName,'enfant')!==false?'badge-enfant':'badge-accessoires'));
            ?>
            <span class="product-badge <?= $badgeClass ?>" style="margin-bottom:10px;display:inline-block;"><?= securiser($produit['categorie_nom'] ?? '') ?></span>

            <h1 style="font-size:26px;font-weight:800;margin-bottom:10px;"><?= securiser($produit['nom']) ?></h1>

            <!-- NOTES -->
            <div class="stars-row mb-3">
                <span class="stars-display"><?= str_repeat('★', (int)$notesMoyenne) ?><?= str_repeat('☆', 5 - (int)$notesMoyenne) ?></span>
                <span class="stars-count"><?= $notesMoyenne ?> (<?= count($avisListe) ?> avis)</span>
            </div>

            <div class="prix" style="font-size:28px;font-weight:800;margin-bottom:20px;">
                <?= renderPrix($produit['prix'], $produit['solde_prix']) ?>
                <?php if (!empty($produit['solde_prix']) && $produit['solde_prix'] > 0): ?>
                <span class="badge-solde-tag" style="position:static;display:inline-block;vertical-align:middle;margin-left:10px;">-<?= round((1 - $produit['solde_prix']/$produit['prix'])*100) ?>%</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($produit['description'])): ?>
            <p style="font-size:14px;color:var(--gray-600);line-height:1.7;margin-bottom:24px;"><?= securiser($produit['description']) ?></p>
            <?php endif; ?>

            <!-- TAILLES -->
            <?php if (!empty($produit['taille_disponible'])): ?>
            <div class="mb-4">
                <p class="text-sm font-semibold mb-2">Taille disponible :</p>
                <div class="size-tags">
                    <?php foreach (explode(',', $produit['taille_disponible']) as $t): ?>
                    <div class="size-tag"><?= securiser(trim($t)) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- STOCK -->
            <div class="mb-4">
                <?php if ($produit['stock'] > 5): ?>
                <span class="text-sm" style="color:var(--success);"><i class="fas fa-check-circle"></i> En stock (<?= $produit['stock'] ?> disponibles)</span>
                <?php elseif ($produit['stock'] > 0): ?>
                <span class="text-sm" style="color:var(--warning);"><i class="fas fa-exclamation-circle"></i> Stock limité (<?= $produit['stock'] ?> restants)</span>
                <?php else: ?>
                <span class="text-sm text-danger"><i class="fas fa-times-circle"></i> Rupture de stock</span>
                <?php endif; ?>
            </div>

            <!-- ADD TO CART -->
            <?php if ($produit['stock'] > 0): ?>
            <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1" style="display:flex;gap:10px;margin-bottom:16px;align-items:center;">
                <input type="hidden" name="produit_id" value="<?= $produit['id'] ?>">
                <div class="qty-control" style="flex-shrink:0;">
                    <button type="button" class="qty-btn" data-dir="down"><i class="fas fa-minus"></i></button>
                    <input type="number" name="quantite" class="qty-input" value="1" min="1" max="<?= $produit['stock'] ?>">
                    <button type="button" class="qty-btn" data-dir="up"><i class="fas fa-plus"></i></button>
                </div>
                <button type="submit" class="btn btn-dark btn-lg" style="flex:1;"><i class="fas fa-shopping-bag"></i> Ajouter au panier</button>
            </form>
            <a href="<?= BASE_URL ?>/pages/checkout.php?direct=<?= $produit['id'] ?>" class="btn btn-outline-dark btn-block" style="margin-bottom:20px;">Acheter maintenant</a>
            <?php else: ?>
            <button class="btn btn-outline-dark btn-block btn-lg" disabled style="opacity:.5;cursor:default;margin-bottom:16px;">Rupture de stock</button>
            <?php endif; ?>

            <!-- INFOS LIVRAISON -->
            <div class="why-buy" style="margin-top:8px;">
                <div class="why-buy-item"><i class="fas fa-truck"></i><h4>Livraison rapide</h4><p>Partout au Bénin</p></div>
                <div class="why-buy-item"><i class="fas fa-undo"></i><h4>Retour facile</h4><p>Sous 7 jours</p></div>
                <div class="why-buy-item"><i class="fas fa-shield-alt"></i><h4>Paiement sécurisé</h4><p>MTN MoMo &amp; Moov Money</p></div>
                <div class="why-buy-item"><i class="fas fa-headset"></i><h4>Support 7j/7</h4><p>À votre écoute</p></div>
            </div>
        </div>
    </div>

    <!-- AVIS CLIENTS -->
    <?php if (!empty($avisListe)): ?>
    <div style="margin-top:56px;border-top:1px solid var(--gray-200);padding-top:40px;">
        <h2 class="section-title" style="margin-bottom:24px;">Avis clients (<?= count($avisListe) ?>)</h2>
        <div class="grid-3">
            <?php foreach (array_slice($avisListe, 0, 3) as $av): ?>
            <div class="review-card">
                <div class="stars"><?= str_repeat('★', $av['note'] ?? 5) ?><?= str_repeat('☆', 5 - ($av['note'] ?? 5)) ?></div>
                <p><?= securiser($av['commentaire'] ?? '') ?></p>
                <div class="review-author">— <?= securiser(($av['prenom'] ?? '') . ' ' . substr($av['nom'] ?? '', 0, 1) . '.') ?><span class="review-badge"><?= securiser($av['categorie_nom'] ?? 'Acheteur') ?></span></div>
                <div class="text-xs text-muted" style="margin-top:6px;"><?= date('d/m/Y', strtotime($av['date_creation'] ?? 'now')) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PRODUITS SIMILAIRES -->
    <?php if (!empty($similaires)): ?>
    <div style="margin-top:48px;">
        <div class="section-header">
            <h2 class="section-title">Produits similaires</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=<?= $produit['categorie_id'] ?>" class="section-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="grid-4">
            <?php foreach ($similaires as $sim): ?>
            <div class="product-card">
                <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $sim['id'] ?>">
                    <div class="product-image-placeholder" style="position:relative;<?php if (!empty($sim['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $sim['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>"><?php if (empty($sim['photo'])): ?><i class="fas fa-image" style="font-size:28px;"></i><?php endif; ?></div>
                </a>
                <div class="product-info">
                    <h3><?= securiser($sim['nom']) ?></h3>
                    <div class="prix"><?= renderPrix($sim['prix'], $sim['solde_prix']) ?></div>
                    <?php if ($sim['stock'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1">
                        <input type="hidden" name="produit_id" value="<?= $sim['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark btn-sm btn-block">Ajouter au panier</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeImage(el, src) {
    document.querySelectorAll('.product-gallery-thumb').forEach(function(t){t.classList.remove('active')});
    el.classList.add('active');
    document.querySelector('#main-gallery-img img').src = src;
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
