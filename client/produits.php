<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Robes &amp; Jupes – Femme — ClaudiShop</title>
<!-- Polices Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<!-- Feuilles de style principales -->
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/client.css">
<style>
/* Styles spécifiques à la page produits */
body { background:#fff; }
.page-wrapper { max-width:1280px; margin:0 auto; padding:20px 24px 60px; }
.products-layout { display:grid; grid-template-columns:240px 1fr; gap:24px; margin-top:16px; }
.top-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.filter-tags-active { display:flex; gap:6px; flex-wrap:wrap; }
.filter-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; background:#0A0A0A; color:#fff; font-size:.75rem; border-radius:20px; }
.filter-chip button { background:none; border:none; color:#fff; cursor:pointer; font-size:.9rem; line-height:1; padding:0; }
.sort-select { padding:7px 30px 7px 12px; border:1px solid #E5E5E5; border-radius:4px; font-size:.82rem; appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235A5A5A' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; outline:none; cursor:pointer; }
.results-count { font-size:.82rem; color:#5A5A5A; margin-bottom:12px; }
.prod-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
</style>
</head>
<body>

<!-- Inclusion de la configuration -->
<?php include '../includes/config.php'; ?>

<!-- ANNOUNCEMENT BAR : Barre d'annonce promotionnelle -->
<div style="background:#0A0A0A;color:#fff;text-align:center;padding:8px;font-size:.78rem;">
  Livraison gratuite dès 25 000 FCFA • <span style="color:#C9A03D;font-weight:600;">Paiement MTN Momo &amp; Moov Money</span>
</div>

<!-- HEADER : En-tête avec logo, navigation, recherche et icônes compte/panier -->
<header style="background:#fff;border-bottom:1px solid #E5E5E5;position:sticky;top:0;z-index:100;">
  <div style="max-width:1280px;margin:0 auto;padding:0 24px;display:flex;align-items:center;gap:20px;height:56px;">
    <!-- Logo du site -->
    <a href="../index.php" style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;font-weight:700;color:#0A0A0A;text-decoration:none;line-height:1;">CLAUDI<span style="display:block;font-family:'DM Sans',sans-serif;font-size:.5rem;letter-spacing:.18em;color:#9A9A9A;">SHOP</span></a>
    <!-- Navigation principale -->
    <nav style="flex:1;display:flex;gap:2px;">
      <!-- Boucle des catégories de navigation -->
      <?php foreach(['Femme','Homme','Enfant','Accessoires','Nouveautés'] as $n): ?>
      <a href="produits.php?cat=<?= urlencode(strtolower($n)) ?>" style="padding:8px 12px;font-size:.85rem;color:#5A5A5A;border-radius:4px;text-decoration:none;"><?= $n ?></a>
      <?php endforeach; ?>
      <!-- Lien spécial Soldes (en rouge) -->
      <a href="produits.php?cat=soldes" style="padding:8px 12px;font-size:.85rem;color:#B91C1C;font-weight:600;border-radius:4px;text-decoration:none;">Soldes</a>
    </nav>
    <!-- Barre de recherche -->
    <div style="position:relative;width:190px;">
      <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9A9A9A;" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder="Rechercher…" style="width:100%;padding:7px 12px 7px 32px;border:1px solid #E5E5E5;border-radius:20px;font-size:.82rem;outline:none;background:#F5F5F5;">
    </div>
    <!-- Icônes compte et panier -->
    <div style="display:flex;align-items:center;gap:4px;">
      <a href="profil.php" style="display:flex;flex-direction:column;align-items:center;padding:6px 10px;font-size:.68rem;color:#5A5A5A;gap:2px;text-decoration:none;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Compte
      </a>
      <a href="panier.php" style="display:flex;flex-direction:column;align-items:center;padding:6px 10px;font-size:.68rem;color:#5A5A5A;gap:2px;text-decoration:none;">
        <div style="position:relative;">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <!-- Badge du nombre d'articles dans le panier -->
          <span style="position:absolute;top:-6px;right:-8px;background:#0A0A0A;color:#fff;font-size:.6rem;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;">4</span>
        </div>
        Panier
      </a>
    </div>
  </div>
</header>

<div class="page-wrapper">
  <!-- BREADCRUMB : Fil d'Ariane -->
  <div class="breadcrumb-nav">
    <a href="../index.php">Accueil</a><span class="sep">/</span>
    <a href="#">Femme</a><span class="sep">/</span>
    <span class="current">Robes &amp; Jupes</span>
  </div>

  <!-- En-tête de la page avec titre et tri -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
    <h1 style="font-family:'DM Sans',sans-serif;font-size:1.4rem;font-weight:700;">Robes &amp; Jupes – Femme</h1>
    <div style="display:flex;align-items:center;gap:10px;">
      <span style="font-size:.8rem;color:#5A5A5A;">Trier par :</span>
      <!-- Sélecteur de tri -->
      <select class="sort-select">
        <option>Nouveautés</option><option>Prix croissant</option><option>Prix décroissant</option><option>Meilleures ventes</option>
      </select>
    </div>
  </div>

  <!-- Active filters : Filtres actifs affichés sous forme de chips -->
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
    <span style="font-size:.78rem;color:#5A5A5A;">Filtres actifs :</span>
    <div class="filter-chip">Femme <button onclick="this.parentElement.remove()">×</button></div>
    <div class="filter-chip">Robe <button onclick="this.parentElement.remove()">×</button></div>
    <a href="#" style="font-size:.78rem;color:#5A5A5A;margin-left:4px;">Effacer tout</a>
  </div>

  <!-- Mise en page produits : sidebar filtres + grille -->
  <div class="products-layout">
    <!-- SIDEBAR FILTRES -->
    <aside class="filters-panel">
      <div class="filters-title">Filtres</div>

      <!-- Groupe de filtres : Univers -->
      <div class="filter-group">
        <div class="filter-group-title">Univers</div>
        <?php foreach(['Femme','Homme','Enfant'] as $u): ?>
        <label class="filter-checkbox"><input type="checkbox" <?= $u==='Femme'?'checked':'' ?>> <?= $u ?></label>
        <?php endforeach; ?>
      </div>

      <!-- Groupe de filtres : Catégorie -->
      <div class="filter-group">
        <div class="filter-group-title">Catégorie</div>
        <?php foreach(['Robes','Jupes','Blouses','Tailleurs','Combinaisons'] as $c): ?>
        <label class="filter-checkbox"><input type="checkbox" <?= $c==='Robes'?'checked':'' ?>> <?= $c ?></label>
        <?php endforeach; ?>
      </div>

      <!-- Groupe de filtres : Taille -->
      <div class="filter-group">
        <div class="filter-group-title">Taille</div>
        <div class="size-btns">
          <?php foreach(['XS','S','M','L','XL','XXL'] as $s): ?>
          <button class="size-btn <?= $s==='M'?'active':'' ?>"><?= $s ?></button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Groupe de filtres : Prix -->
      <div class="filter-group">
        <div class="filter-group-title">Prix (FCFA)</div>
        <div class="price-range">
          <input type="number" placeholder="Min" value="">
          <span style="color:#9A9A9A;">–</span>
          <input type="number" placeholder="Max" value="">
        </div>
        <button style="margin-top:8px;padding:6px 14px;background:#0A0A0A;color:#fff;border:none;border-radius:4px;font-size:.78rem;cursor:pointer;">Appliquer</button>
      </div>

      <!-- Groupe de filtres : Couleur -->
      <div class="filter-group">
        <div class="filter-group-title">Couleur</div>
        <div class="color-swatches">
          <?php $colors=['#fff','#E5E5E5','#5A5A5A','#0A0A0A','#C9A03D','#B91C1C','#1E40AF','#065F46']; ?>
          <?php foreach($colors as $i=>$c): ?>
          <div class="color-swatch <?= $i===0?'active':'' ?>" style="background:<?= $c ?>;border:<?= $c==='#fff'?'1.5px solid #E5E5E5':'2px solid transparent' ?>;"></div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Groupe de filtres : Matière -->
      <div class="filter-group">
        <div class="filter-group-title">Matière / Tissu</div>
        <?php foreach(['Wax (Pagne)','Coton','Lin','Satin'] as $m): ?>
        <label class="filter-checkbox"><input type="checkbox"> <?= $m ?></label>
        <?php endforeach; ?>
      </div>

      <!-- Groupe de filtres : Note clients -->
      <div class="filter-group">
        <div class="filter-group-title">Note clients</div>
        <?php for($i=5;$i>=3;$i--): ?>
        <label class="filter-checkbox" style="margin-bottom:5px;">
          <input type="checkbox">
          <span class="stars"><?= str_repeat('★',$i).str_repeat('☆',5-$i) ?></span>
          <span style="font-size:.75rem;color:#5A5A5A;">(<?= $i ?>+ étoiles)</span>
        </label>
        <?php endfor; ?>
      </div>

      <!-- Groupe de filtres : Disponibilité -->
      <div class="filter-group">
        <div class="filter-group-title">Disponibilité</div>
        <label class="filter-checkbox"><input type="checkbox"> En stock uniquement</label>
        <label class="filter-checkbox"><input type="checkbox"> Inclure rupture de stock</label>
      </div>

      <!-- Bouton d'application des filtres -->
      <button class="filter-apply-btn">Appliquer les filtres</button>
    </aside>

    <!-- GRILLE PRODUITS -->
    <div>
      <div class="results-count">124 articles trouvés</div>
      <div class="prod-grid">
        <!-- Données de démonstration des produits -->
        <?php
        $demo = [
          ['nom'=>'Robe Wax bleue','cat'=>'Femme','tailles'=>'M · L · XL, 8 disponibles','prix'=>'18 500','badge'=>'femme','rupture'=>false,'solde'=>false],
          ['nom'=>'Jupe longue Kente','cat'=>'Femme','tailles'=>'S · M · L, 3 disponibles','prix'=>'12 000','prix_barre'=>'16 500','badge'=>'femme','rupture'=>false,'solde'=>true],
          ['nom'=>'Combinaison coton','cat'=>'Femme','tailles'=>'XS · S · M · L','prix'=>'22 000','badge'=>'femme','rupture'=>false,'solde'=>false],
          ['nom'=>'Robe soirée satin','cat'=>'Femme','tailles'=>'S · M','prix'=>'34 000','badge'=>'femme','rupture'=>false,'solde'=>false],
          ['nom'=>'Tailleur Wax 2 pièces','cat'=>'Femme','tailles'=>'M · L','prix'=>'28 000','badge'=>'femme','rupture'=>false,'solde'=>false],
          ['nom'=>'Modèle en stock','cat'=>'Femme','tailles'=>'','prix'=>'','badge'=>'femme','rupture'=>true,'solde'=>false],
          ['nom'=>'Blouse imprimée','cat'=>'Femme','tailles'=>'XS · S · M · L · XL','prix'=>'9 500','badge'=>'femme','rupture'=>false,'solde'=>false],
          ['nom'=>'Robe babydoll coton','cat'=>'Femme','tailles'=>'S · M · L','prix'=>'9 100','badge'=>'femme','rupture'=>false,'solde'=>true],
          ['nom'=>'Robe Wax rouge','cat'=>'Femme','tailles'=>'M · L · XL · XXL','prix'=>'16 500','badge'=>'femme','rupture'=>false,'solde'=>false],
        ];
        // Boucle d'affichage des produits
        foreach($demo as $p):
        ?>
        <div class="product-card">
          <div class="product-image-wrap">
            <!-- Image placeholder du produit -->
            <div class="product-image img-placeholder" style="height:200px;">
              <svg width="36" height="36" fill="none" stroke="#D0D0D0" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <!-- Badges (catégorie et solde) -->
            <div class="product-badges">
              <span class="product-badge <?= $p['badge'] ?>"><?= $p['cat'] ?></span>
              <!-- Badge -30% si le produit est en solde -->
              <?php if($p['solde']): ?><span class="product-badge soldes">-30%</span><?php endif; ?>
            </div>
          </div>
          <div class="product-info">
            <!-- Nom du produit -->
            <div class="product-name"><?= htmlspecialchars($p['nom']) ?></div>
            <!-- Tailles disponibles (affichées si présentes) -->
            <?php if($p['tailles']): ?><div class="product-sizes"><?= $p['tailles'] ?></div><?php endif; ?>
            <!-- Si le produit n'est pas en rupture de stock -->
            <?php if(!$p['rupture']): ?>
              <div class="product-price">
                <!-- Prix barré si solde -->
                <?php if(!empty($p['prix_barre'])): ?>
                  <span class="product-old-price"><?= $p['prix_barre'] ?> FCFA</span>
                <?php endif; ?>
                <span class="<?= $p['solde']?'product-sale-price':'' ?>"><?= $p['prix'] ?> FCFA</span>
              </div>
              <!-- Bouton "Ajouter au panier" -->
              <button class="product-add-btn" onclick="addToCart(this)">Ajouter au panier</button>
            <!-- Si le produit est en rupture -->
            <?php else: ?>
              <div style="font-size:.78rem;color:#B91C1C;margin-bottom:4px;">Rupture de stock</div>
              <button class="product-add-btn rupture">Notifier au retour</button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- PAGINATION -->
      <div class="pagination" style="margin-top:28px;">
        <button class="page-btn">‹</button>
        <!-- Boucle des numéros de page -->
        <?php for($i=1;$i<=5;$i++): ?>
        <button class="page-btn <?= $i===1?'active':'' ?>"><?= $i ?></button>
        <?php endfor; ?>
        <span style="font-size:.82rem;color:#9A9A9A;padding:0 4px;">…</span>
        <button class="page-btn">14</button>
        <button class="page-btn">›</button>
      </div>
    </div>
  </div>
</div>

<!-- FOOTER : Pied de page simple -->
<div style="background:#0A0A0A;color:#fff;text-align:center;padding:14px;font-size:.75rem;color:rgba(255,255,255,.5);">
  CLAUDI SHOP – Mode &amp; Accessoires Homme / Femme / Enfant – Cotonou, Bénin<br>
  © 2026 Claudi Shop · Paiement MTN MoMo &amp; Moov Money · Livraison partout au Bénin
</div>

<script>
// Fonction pour ajouter un produit au panier avec animation
function addToCart(btn){
  const orig=btn.textContent;
  // Changement temporaire du texte et de la couleur
  btn.textContent='✓ Ajouté !';
  btn.style.background='#2D7A4F';
  // Retour à l'état initial après 1,8 seconde
  setTimeout(()=>{btn.textContent=orig;btn.style.background='';},1800);
}
// Écouteurs pour les boutons de taille (sélection unique)
document.querySelectorAll('.size-btn').forEach(b=>{
  b.addEventListener('click',function(){
    this.closest('.size-btns').querySelectorAll('.size-btn').forEach(x=>x.classList.remove('active'));
    this.classList.add('active');
  });
});
// Écouteurs pour les sélecteurs de couleur (sélection unique)
document.querySelectorAll('.color-swatch').forEach(s=>{
  s.addEventListener('click',function(){
    this.closest('.color-swatches').querySelectorAll('.color-swatch').forEach(x=>x.classList.remove('active'));
    this.classList.add('active');
  });
});
</script>
</body>
</html>
