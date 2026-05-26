<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion des classes nécessaires
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Avis.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

// Récupération et validation de l'identifiant du produit depuis l'URL
$produitId = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Redirection si l'ID est invalide
if (!$produitId) { redirect(BASE_URL . '/pages/boutique.php'); }

// Instanciation de l'objet Produit et récupération des données
$produitObj = new Produit();
$produit = $produitObj->getById($produitId);
// Redirection si le produit n'existe pas
if (!$produit) { redirect(BASE_URL . '/pages/boutique.php'); }

// Récupération des avis clients pour ce produit
$avisObj = new Avis();
$avisListe = $avisObj->getByProduit($produitId);
// Calcul de la note moyenne
$notesMoyenne = count($avisListe) ? round(array_sum(array_column($avisListe,'note')) / count($avisListe), 1) : 0;

// Récupération des produits similaires (même catégorie)
$similaires = $produitObj->getByCategorieWithFilters($produit['categorie_id'] ?? null);
// Exclusion du produit courant de la liste des similaires
$similaires = array_filter($similaires, function($p) use ($produitId) { return $p['id'] != $produitId; });
// Limitation à 4 produits similaires
$similaires = array_slice($similaires, 0, 4);

// Définition du titre de la page avec le nom du produit
$pageTitle = $produit['nom'];
// Inclusion de l'en-tête HTML et de la barre de navigation
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="padding-top:28px;padding-bottom:56px;">
    <!-- BREADCRUMB (fil d'Ariane) -->
    <nav class="breadcrumb">
        <a href="<?= BASE_URL ?>/index.php">Accueil</a>
        <span class="breadcrumb-sep">/</span>
        <a href="<?= BASE_URL ?>/pages/boutique.php">Boutique</a>
        <span class="breadcrumb-sep">/</span>
        <?php // Affichage du lien vers la catégorie si elle existe ?>
        <?php if (!empty($produit['categorie_nom'])): ?>
        <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=<?= $produit['categorie_id'] ?>"><?= securiser($produit['categorie_nom']) ?></a>
        <span class="breadcrumb-sep">/</span>
        <?php endif; ?>
        <?php // Nom du produit en dernière position ?>
        <span><?= securiser($produit['nom']) ?></span>
    </nav>

    <?php // Construction de la liste des images du produit ?>
    <?php
    $imagesListe = [$produit['photo']];
    // Décodage des images supplémentaires stockées en JSON
    $extra = !empty($produit['images']) ? json_decode($produit['images'], true) : [];
    if (is_array($extra)) $imagesListe = array_merge($imagesListe, $extra);
    // Suppression des entrées vides
    $imagesListe = array_filter($imagesListe);
    ?>
    <?php // Mise en page de la fiche produit (galerie + infos) ?>
    <div class="product-detail-layout">
        <!-- GALLERIE D'IMAGES -->
        <div class="product-gallery">
            <?php // Image principale ?>
            <div class="product-gallery-main" id="main-gallery-img">
                <?php if (!empty($imagesListe[0])): ?>
                <img src="<?= UPLOADS_URL . '/' . securiser($imagesListe[0]) ?>" alt="<?= securiser($produit['nom']) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                <?php // Icône par défaut si pas d'image ?>
                <i class="fas fa-image" style="font-size:56px;color:var(--gray-300);"></i>
                <?php endif; ?>
            </div>
            <?php // Miniatures supplémentaires (affichées seulement s'il y en a plus d'une) ?>
            <?php if (count($imagesListe) > 1): ?>
            <div class="product-gallery-thumbs">
                <?php // Boucle sur chaque image pour afficher une miniature cliquable ?>
                <?php foreach ($imagesListe as $i => $img): ?>
                <div class="product-gallery-thumb <?= $i === 0 ? 'active' : '' ?>" onclick="changeImage(this, '<?= UPLOADS_URL . '/' . securiser($img) ?>')" style="overflow:hidden;">
                    <img src="<?= UPLOADS_URL . '/' . securiser($img) ?>" alt="" style="width:100%;height:100%;object-fit:cover;cursor:pointer;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- INFORMATIONS PRODUIT -->
        <div>
            <?php // Détermination de la classe du badge selon la catégorie ?>
            <?php
            $catName = strtolower($produit['categorie_nom'] ?? '');
            $badgeClass = strpos($catName,'femme')!==false?'badge-femme':(strpos($catName,'homme')!==false?'badge-homme':(strpos($catName,'enfant')!==false?'badge-enfant':'badge-accessoires'));
            ?>
            <?php // Badge de catégorie ?>
            <span class="product-badge <?= $badgeClass ?>" style="margin-bottom:10px;display:inline-block;"><?= securiser($produit['categorie_nom'] ?? '') ?></span>

            <?php // Nom du produit ?>
            <h1 style="font-size:26px;font-weight:800;margin-bottom:10px;"><?= securiser($produit['nom']) ?></h1>

            <!-- AFFICHAGE DES NOTES -->
            <div class="stars-row mb-3">
                <?php // Étoiles pleines et vides selon la note moyenne ?>
                <span class="stars-display"><?= str_repeat('★', (int)$notesMoyenne) ?><?= str_repeat('☆', 5 - (int)$notesMoyenne) ?></span>
                <?php // Nombre d'avis ?>
                <span class="stars-count"><?= $notesMoyenne ?> (<?= count($avisListe) ?> avis)</span>
            </div>

            <?php // Affichage du prix (avec ou sans solde) ?>
            <div class="prix" style="font-size:28px;font-weight:800;margin-bottom:20px;">
                <?= renderPrix($produit['prix'], $produit['solde_prix']) ?>
                <?php // Badge de réduction si le produit est en solde ?>
                <?php if (!empty($produit['solde_prix']) && $produit['solde_prix'] > 0): ?>
                <span class="badge-solde-tag" style="position:static;display:inline-block;vertical-align:middle;margin-left:10px;">-<?= round((1 - $produit['solde_prix']/$produit['prix'])*100) ?>%</span>
                <?php endif; ?>
            </div>

            <?php // Description du produit ?>
            <?php if (!empty($produit['description'])): ?>
            <p style="font-size:14px;color:var(--gray-600);line-height:1.7;margin-bottom:24px;"><?= securiser($produit['description']) ?></p>
            <?php endif; ?>

            <!-- AFFICHAGE ET SÉLECTION DES TAILLES DISPONIBLES -->
            <?php if (!empty($produit['taille_disponible'])): ?>
            <div class="mb-4">
                <p class="text-sm font-semibold mb-2">Taille disponible : <span id="taille-choisie" style="color:var(--dark);font-weight:700;"></span></p>
                <div class="size-tags" id="size-selector">
                    <?php foreach (explode(',', $produit['taille_disponible']) as $t): ?>
                    <div class="size-tag" data-taille="<?= securiser(trim($t)) ?>"><?= securiser(trim($t)) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- AFFICHAGE DU STOCK -->
            <div class="mb-4">
                <?php // Affichage du niveau de stock avec couleur appropriée ?>
                <?php if ($produit['stock'] > 5): ?>
                <span class="text-sm" style="color:var(--success);"><i class="fas fa-check-circle"></i> En stock (<?= $produit['stock'] ?> disponibles)</span>
                <?php elseif ($produit['stock'] > 0): ?>
                <span class="text-sm" style="color:var(--warning);"><i class="fas fa-exclamation-circle"></i> Stock limité (<?= $produit['stock'] ?> restants)</span>
                <?php else: ?>
                <span class="text-sm text-danger"><i class="fas fa-times-circle"></i> Rupture de stock</span>
                <?php endif; ?>
            </div>

            <!-- BOUTON AJOUTER AU PANIER (visible seulement si stock > 0) -->
            <?php if ($produit['stock'] > 0): ?>
            <form method="POST" action="<?= BASE_URL ?>/actions/ajouter_panier.php" data-ajax-add="1" style="display:flex;gap:10px;margin-bottom:16px;align-items:center;" id="form-ajout-panier">
                <input type="hidden" name="produit_id" value="<?= $produit['id'] ?>">
                <input type="hidden" name="taille" id="taille-input" value="">
                <?php // Contrôle de quantité (plus/moins) ?>
                <div class="qty-control" style="flex-shrink:0;">
                    <button type="button" class="qty-btn" data-dir="down"><i class="fas fa-minus"></i></button>
                    <input type="number" name="quantite" class="qty-input" value="1" min="1" max="<?= $produit['stock'] ?>">
                    <button type="button" class="qty-btn" data-dir="up"><i class="fas fa-plus"></i></button>
                </div>
                <button type="submit" class="btn btn-dark btn-lg" style="flex:1;"><i class="fas fa-shopping-bag"></i> Ajouter au panier</button>
            </form>
            <?php // Lien d'achat direct ?>
            <a href="<?= BASE_URL ?>/pages/checkout.php?direct=<?= $produit['id'] ?>" class="btn btn-outline-dark btn-block" style="margin-bottom:20px;">Acheter maintenant</a>
            <?php else: ?>
            <?php // Bouton désactivé si rupture de stock ?>
            <button class="btn btn-outline-dark btn-block btn-lg" disabled style="opacity:.5;cursor:default;margin-bottom:16px;">Rupture de stock</button>
            <?php endif; ?>

            <!-- INFORMATIONS LIVRAISON -->
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
            <?php // Boucle sur les 3 premiers avis ?>
            <?php foreach (array_slice($avisListe, 0, 3) as $av): ?>
            <div class="review-card">
                <?php // Étoiles de notation ?>
                <div class="stars"><?= str_repeat('★', $av['note'] ?? 5) ?><?= str_repeat('☆', 5 - ($av['note'] ?? 5)) ?></div>
                <?php // Commentaire de l'avis ?>
                <p><?= securiser($av['commentaire'] ?? '') ?></p>
                <?php // Auteur de l'avis ?>
                <div class="review-author">— <?= securiser(($av['prenom'] ?? '') . ' ' . substr($av['nom'] ?? '', 0, 1) . '.') ?><span class="review-badge"><?= securiser($av['categorie_nom'] ?? 'Acheteur') ?></span></div>
                <?php // Date de l'avis ?>
                <div class="text-xs text-muted" style="margin-top:6px;"><?= date('d/m/Y', strtotime($av['date_creation'] ?? 'now')) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PRODUITS SIMILAIRES -->
    <?php if (!empty($similaires)): ?>
    <div style="margin-top:48px;">
        <?php // En-tête de la section ?>
        <div class="section-header">
            <h2 class="section-title">Produits similaires</h2>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=<?= $produit['categorie_id'] ?>" class="section-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php // Grille des produits similaires ?>
        <div class="grid-4">
            <?php // Boucle sur chaque produit similaire ?>
            <?php foreach ($similaires as $sim): ?>
            <div class="product-card">
                <?php // Lien vers la fiche du produit ?>
                <a href="<?= BASE_URL ?>/pages/detail_produit.php?id=<?= $sim['id'] ?>">
                    <?php // Image du produit similaire ?>
                    <div class="product-image-placeholder" style="position:relative;<?php if (!empty($sim['photo'])): ?>background-image:url('<?= UPLOADS_URL ?>/<?= $sim['photo'] ?>');background-size:cover;background-position:center;<?php endif; ?>"><?php if (empty($sim['photo'])): ?><i class="fas fa-image" style="font-size:28px;"></i><?php endif; ?></div>
                </a>
                <div class="product-info">
                    <?php // Nom du produit similaire ?>
                    <h3><?= securiser($sim['nom']) ?></h3>
                    <?php // Prix ?>
                    <div class="prix"><?= renderPrix($sim['prix'], $sim['solde_prix']) ?></div>
                    <?php // Bouton d'ajout au panier si en stock ?>
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

<?php // Script JavaScript pour la sélection de taille et le changement d'image ?>
<script>
<?php if (!empty($produit['taille_disponible'])): ?>
// Sélection de taille — tags cliquables
(function(){
    var tags = document.querySelectorAll('#size-selector .size-tag');
    var input = document.getElementById('taille-input');
    var chosen = document.getElementById('taille-choisie');
    tags.forEach(function(tag){
        tag.addEventListener('click', function(){
            tags.forEach(function(t){ t.classList.remove('active'); });
            this.classList.add('active');
            var val = this.getAttribute('data-taille');
            input.value = val;
            if (chosen) chosen.textContent = val;
        });
    });
    // Validation : taille requise si le produit a des tailles
    document.getElementById('form-ajout-panier').addEventListener('submit', function(e){
        if (!input.value) {
            e.preventDefault();
            alert('Veuillez sélectionner une taille.');
            return false;
        }
    });
})();
<?php endif; ?>
function changeImage(el, src) {
    // Retrait de la classe 'active' de toutes les miniatures
    document.querySelectorAll('.product-gallery-thumb').forEach(function(t){t.classList.remove('active')});
    // Activation de la miniature cliquée
    el.classList.add('active');
    // Mise à jour de l'image principale
    document.querySelector('#main-gallery-img img').src = src;
}
</script>
<?php
// Inclusion du pied de page
require_once __DIR__ . '/../includes/footer.php'; ?>
