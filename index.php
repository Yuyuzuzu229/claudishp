<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Categorie.php';
require_once __DIR__ . '/classes/Produit.php';
require_once __DIR__ . '/classes/Avis.php';
require_once __DIR__ . '/classes/Panier.php';
require_once __DIR__ . '/classes/Notification.php';

$pageTitle = 'Accueil';
$produit = new Produit();
$categorie = new Categorie();
$categories = $categorie->getWithProduitCount();
$soldes = $produit->getSoldes(8);
$nouveautes = $produit->getDerniersSansSoldes(12);
$meilleuresVentes = $produit->getMeilleuresVentes(3);
$avisObj = new Avis();
$avisPublies = $avisObj->getPublies(5);

// Hero: pick one random product from key categories for background images
$heroFemme = $produit->getByCategorie(1);
shuffle($heroFemme);
$heroFemme = $heroFemme[0] ?? null;
$heroHomme = $produit->getByCategorie(2);
shuffle($heroHomme);
$heroHomme = $heroHomme[0] ?? null;
$heroEnfant = $produit->getByCategorie(3);
shuffle($heroEnfant);
$heroEnfant = $heroEnfant[0] ?? null;
$heroAccessoires = $produit->getByCategorie(4);
shuffle($heroAccessoires);
$heroAccessoires = $heroAccessoires[0] ?? null;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- HERO GRID -->
<div class="hero-grid">
    <div class="hero-card">
        <div class="hero-card-img" <?php if ($heroFemme && !empty($heroFemme['photo'])): ?>style="background:url('<?= UPLOADS_URL ?>/<?= $heroFemme['photo'] ?>') center/cover no-repeat;"<?php endif; ?>></div>
        <div class="hero-card-content">
            <span class="hero-tag">Tendance</span>
            <h2>Collection<br>Printemps</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=1" class="btn btn-white btn-sm">Découvrir</a>
        </div>
    </div>
    <div class="hero-card">
        <div class="hero-card-img" <?php if ($heroHomme && !empty($heroHomme['photo'])): ?>style="background:url('<?= UPLOADS_URL ?>/<?= $heroHomme['photo'] ?>') center/cover no-repeat;"<?php endif; ?>></div>
        <div class="hero-card-content">
            <span class="hero-tag">Nouveauté</span>
            <h2>Nouvelle<br>Saison</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-white btn-sm">Découvrir</a>
        </div>
    </div>
</div>

<?php
$produitsEnfant = $produit->getByCategorie(3);
shuffle($produitsEnfant);
$produitsEnfant = array_slice($produitsEnfant, 0, 4);
$produitsAccessoires = $produit->getByCategorie(4);
shuffle($produitsAccessoires);
$produitsAccessoires = array_slice($produitsAccessoires, 0, 4);
?>
<!-- UNIVERS ENFANT -->
<?php if ($produitsEnfant): ?>
<section class="section section-animate">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Univers Enfant</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=3" class="section-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="grid-4">
            <?php foreach ($produitsEnfant as $prod): ?>
            <div class="product-card">
                <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $prod['id'] ?>">
                    <div class="product-image-placeholder" style="position:relative;<?php if (!empty($prod['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $prod['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>">
                        <?php if (empty($prod['photo'])): ?><i class="fas fa-image" style="font-size:32px;"></i><?php endif; ?>
                    </div>
                </a>
                <div class="product-info">
                    <span class="product-badge badge-enfant">Enfant</span>
                    <h3><?= securiser($prod['nom']) ?></h3>
                    <?php if (!empty($prod['taille_disponible'])): ?><p class="taille">Taille : <?= securiser($prod['taille_disponible']) ?></p><?php endif; ?>
                    <div class="prix"><?= renderPrix($prod['prix'], $prod['solde_prix']) ?></div>
                    <?php if ($prod['stock'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1">
                        <input type="hidden" name="produit_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark btn-sm btn-block">Ajouter au panier</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted text-sm">Rupture de stock</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ACCESSOIRES -->
<?php if ($produitsAccessoires): ?>
<section class="section section-gray section-animate">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Accessoires &amp; Sacs</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=4" class="section-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="grid-4">
            <?php foreach ($produitsAccessoires as $prod): ?>
            <div class="product-card">
                <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $prod['id'] ?>">
                    <div class="product-image-placeholder" style="position:relative;<?php if (!empty($prod['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $prod['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>">
                        <?php if (empty($prod['photo'])): ?><i class="fas fa-image" style="font-size:32px;"></i><?php endif; ?>
                    </div>
                </a>
                <div class="product-info">
                    <span class="product-badge badge-accessoires">Accessoires</span>
                    <h3><?= securiser($prod['nom']) ?></h3>
                    <div class="prix"><?= renderPrix($prod['prix'], $prod['solde_prix']) ?></div>
                    <?php if ($prod['stock'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1">
                        <input type="hidden" name="produit_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark btn-sm btn-block">Ajouter au panier</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted text-sm">Rupture de stock</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SOLDES -->
<?php if ($soldes): ?>
<section class="section section-gray section-animate">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" style="color:var(--danger);">Soldes</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php?soldes=1" class="section-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="grid-4">
            <?php foreach (array_slice($soldes, 0, 8) as $prod): ?>
            <div class="product-card">
                <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $prod['id'] ?>">
                    <div class="product-image-placeholder" style="position:relative;<?php if (!empty($prod['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $prod['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>">
                        <?php if (empty($prod['photo'])): ?><i class="fas fa-image" style="font-size:32px;"></i><?php endif; ?>
                        <span class="badge-solde-tag">-<?= round((1 - $prod['solde_prix']/$prod['prix'])*100) ?>%</span>
                    </div>
                </a>
                <div class="product-info">
                    <?php
                    $catName = strtolower($prod['categorie_nom'] ?? '');
                    $badgeClass = strpos($catName,'femme')!==false?'badge-femme':(strpos($catName,'homme')!==false?'badge-homme':(strpos($catName,'enfant')!==false?'badge-enfant':'badge-accessoires'));
                    $catLabel = $prod['categorie_nom'] ?? '';
                    ?>
                    <span class="product-badge <?= $badgeClass ?>"><?= securiser($catLabel) ?></span>
                    <h3><?= securiser($prod['nom']) ?></h3>
                    <?php if (!empty($prod['taille_disponible'])): ?><p class="taille">Taille : <?= securiser($prod['taille_disponible']) ?></p><?php endif; ?>
                    <div class="prix"><?= renderPrix($prod['prix'], $prod['solde_prix']) ?></div>
                    <?php if ($prod['stock'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1">
                        <input type="hidden" name="produit_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark btn-sm btn-block">Ajouter au panier</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted text-sm">Rupture de stock</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- NOUVEAUTES -->
<?php if ($nouveautes): ?>
<section class="section section-animate">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Nouveautés</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="section-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="grid-4">
            <?php foreach (array_slice($nouveautes, 0, 8) as $prod): ?>
            <div class="product-card">
                <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $prod['id'] ?>">
                    <div class="product-image-placeholder" style="position:relative;<?php if (!empty($prod['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $prod['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>">
                        <?php if (empty($prod['photo'])): ?><i class="fas fa-image" style="font-size:32px;"></i><?php endif; ?>
                    </div>
                </a>
                <div class="product-info">
                    <?php
                    $catName = strtolower($prod['categorie_nom'] ?? '');
                    $badgeClass = strpos($catName,'femme')!==false?'badge-femme':(strpos($catName,'homme')!==false?'badge-homme':(strpos($catName,'enfant')!==false?'badge-enfant':'badge-accessoires'));
                    $catLabel = $prod['categorie_nom'] ?? '';
                    ?>
                    <span class="product-badge <?= $badgeClass ?>"><?= securiser($catLabel) ?></span>
                    <h3><?= securiser($prod['nom']) ?></h3>
                    <?php if (!empty($prod['taille_disponible'])): ?><p class="taille">Taille : <?= securiser($prod['taille_disponible']) ?></p><?php endif; ?>
                    <div class="prix"><?= renderPrix($prod['prix'], $prod['solde_prix']) ?></div>
                    <?php if ($prod['stock'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1">
                        <input type="hidden" name="produit_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark btn-sm btn-block">Ajouter au panier</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted text-sm">Rupture de stock</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- PROMO BANNER -->
<div class="promo-banner">
    <h2>SOLDES &mdash; jusqu&apos;&agrave; -40%</h2>
    <p>Sur une sélection de vêtements homme, femme et enfant</p>
    <a href="<?= BASE_URL ?>/pages/boutique.php?soldes=1" class="btn btn-dark">Voir les soldes</a>
</div>

<!-- TENDANCES PAR UNIVERS -->
<section class="section section-animate">
    <div class="container">
        <h2 class="section-title" style="margin-bottom:24px;">Tendances par univers</h2>
        <div class="filter-tags">
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=1" class="filter-tag">Femme</a>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=2" class="filter-tag">Homme</a>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=3" class="filter-tag">Enfant</a>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=4" class="filter-tag">Accessoires</a>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="filter-tag">Nouveautés</a>
            <a href="<?= BASE_URL ?>/pages/boutique.php?soldes=1" class="filter-tag solde">Soldes</a>
        </div>
        <div class="grid-3 category-cards">
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=1" class="category-card" <?php if ($heroFemme && !empty($heroFemme['photo'])): ?>style="background:linear-gradient(to top, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.15) 100%), url('<?= UPLOADS_URL ?>/<?= $heroFemme['photo'] ?>') center/cover no-repeat;color:white;border:none;min-height:200px;justify-content:flex-end;"<?php endif; ?>>
                <span class="product-badge badge-femme" style="margin-bottom:8px;">Femme</span>
                <h3 style="color:white;">Robes &middot; Tailleurs &middot; Blouses</h3>
            </a>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=2" class="category-card" <?php if ($heroHomme && !empty($heroHomme['photo'])): ?>style="background:linear-gradient(to top, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.15) 100%), url('<?= UPLOADS_URL ?>/<?= $heroHomme['photo'] ?>') center/cover no-repeat;color:white;border:none;min-height:200px;justify-content:flex-end;"<?php endif; ?>>
                <span class="product-badge badge-homme" style="margin-bottom:8px;">Homme</span>
                <h3 style="color:white;">Chemises &middot; Pantalons &middot; Costumes</h3>
            </a>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=3" class="category-card" <?php if ($heroEnfant && !empty($heroEnfant['photo'])): ?>style="background:linear-gradient(to top, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.15) 100%), url('<?= UPLOADS_URL ?>/<?= $heroEnfant['photo'] ?>') center/cover no-repeat;color:white;border:none;min-height:200px;justify-content:flex-end;"<?php endif; ?>>
                <span class="product-badge badge-enfant" style="margin-bottom:8px;">Enfant</span>
                <h3 style="color:white;">Ensembles &middot; Robes &middot; Sportswear</h3>
            </a>
        </div>
    </div>
</section>

<!-- MEILLEURES VENTES -->
<?php if ($meilleuresVentes): ?>
<section class="section section-gray section-animate">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Meilleures ventes</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="section-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="grid-3">
            <?php foreach ($meilleuresVentes as $prod): ?>
            <div class="product-card">
                <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $prod['id'] ?>">
                    <div class="product-image-placeholder" style="position:relative;<?php if (!empty($prod['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $prod['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>">
                        <?php if (empty($prod['photo'])): ?><i class="fas fa-image" style="font-size:32px;"></i><?php endif; ?>
                    </div>
                </a>
                <div class="product-info">
                    <h3><?= securiser($prod['nom']) ?></h3>
                    <div class="prix"><?= renderPrix($prod['prix'], $prod['solde_prix']) ?></div>
                    <?php if ($prod['stock'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1">
                        <input type="hidden" name="produit_id" value="<?= $prod['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark btn-sm btn-block">Ajouter au panier</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted text-sm">Rupture de stock</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- POURQUOI ACHETER -->
<div class="container" style="padding-top:0;padding-bottom:0;">
    <div class="why-buy">
        <div class="why-buy-item">
            <i class="fas fa-truck"></i>
            <h4>Livraison domicile</h4>
            <p>Partout au Bénin dès 24-72h</p>
        </div>
        <div class="why-buy-item">
            <i class="fas fa-mobile-alt"></i>
            <h4>Paiement mobile</h4>
            <p>MTN MoMo &amp; Moov Money</p>
        </div>
        <div class="why-buy-item">
            <i class="fas fa-ruler"></i>
            <h4>Tailles disponibles</h4>
            <p>XS à XXL, 2 ans à 16 ans</p>
        </div>
        <div class="why-buy-item">
            <i class="fas fa-undo"></i>
            <h4>Retours faciles</h4>
            <p>Échange sous 7 jours</p>
        </div>
    </div>
</div>

<!-- AVIS CLIENTS -->
<section class="section section-animate">
    <div class="container">
        <h2 class="section-title" style="margin-bottom:24px;">Avis clients</h2>
        <div class="grid-3">
            <?php if (empty($avisPublies)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:32px;color:var(--gray-400);">Aucun avis pour le moment.</div>
            <?php else: foreach ($avisPublies as $a): ?>
            <div class="review-card">
                <div class="stars"><?= str_repeat('★', $a['note']) . str_repeat('☆', 5 - $a['note']) ?></div>
                <p>"<?= securiser($a['commentaire']) ?>"</p>
                <div class="review-author">— <?= securiser($a['prenom'] . ' ' . mb_substr($a['nom'], 0, 1)) ?>. <span class="review-badge">Validé</span></div>
                <div class="text-xs text-muted" style="margin-top:6px;"><?= date('d/m/Y', strtotime($a['date_creation'])) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<!-- NEWSLETTER -->
<?php if (!isLoggedIn()): ?>
<section class="newsletter">
    <div class="container">
        <h2>Restez dans la tendance</h2>
        <p>Inscrivez-vous et recevez nos nouvelles collections &amp; offres exclusives</p>
        <form class="newsletter-form" action="<?= BASE_URL ?>/pages/inscription.php" method="GET">
            <input type="email" name="email" placeholder="Votre adresse email" required>
            <button type="submit">S&apos;inscrire</button>
        </form>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
