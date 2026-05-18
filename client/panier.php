<?php
$client_page = 'panier';
$client_title = 'Mon panier';
$client_subtitle = 'Vérifiez vos articles et passez votre commande.';
include '../includes/client_header.php';
include '../includes/config.php';

$panier = [
  ['nom'=>'Hoodie Premium Homme','taille'=>'L','couleur'=>'Noir','prix'=>45000,'qte'=>1],
  ['nom'=>'Veste Tailleur Femme','taille'=>'M','couleur'=>'Beige','prix'=>60000,'qte'=>1],
  ['nom'=>'Jean Slim Enfant','taille'=>'8 ans','couleur'=>'Bleu','prix'=>25000,'qte'=>1],
  ['nom'=>'Casquette Brodée Mixte','taille'=>'Unique','couleur'=>'Noir','prix'=>15000,'qte'=>1],
];
$total = array_sum(array_map(fn($i)=>$i['prix']*$i['qte'], $panier));
?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
  <!-- ARTICLES -->
  <div class="card card-lg">
    <h2 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:20px;">Articles dans votre panier (<?= count($panier) ?>)</h2>

    <div style="display:grid;grid-template-columns:auto 1fr auto auto auto;gap:0;border-bottom:1px solid #E5E5E5;padding-bottom:10px;margin-bottom:4px;">
      <div style="padding:0 16px 0 0;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9A9A9A;">Produit</div>
      <div></div>
      <div style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9A9A9A;padding:0 20px;">Prix unitaire</div>
      <div style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9A9A9A;padding:0 20px;">Quantité</div>
      <div style="font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9A9A9A;padding-left:20px;">Total</div>
    </div>

    <?php foreach($panier as $idx => $item): ?>
    <div style="display:grid;grid-template-columns:80px 1fr auto auto 60px;gap:16px;align-items:center;padding:18px 0;border-bottom:1px solid #F5F5F5;">
      <div style="width:70px;height:85px;border-radius:4px;background:#F0F0F0;display:flex;align-items:center;justify-content:center;color:#C0C0C0;">
        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      </div>
      <div>
        <div style="font-weight:500;font-size:.9rem;margin-bottom:4px;"><?= htmlspecialchars($item['nom']) ?></div>
        <div style="font-size:.78rem;color:#9A9A9A;">Taille : <?= $item['taille'] ?></div>
        <div style="font-size:.78rem;color:#9A9A9A;">Couleur : <?= $item['couleur'] ?></div>
      </div>
      <div style="font-weight:500;font-size:.9rem;white-space:nowrap;"><?= number_format($item['prix'],0,',',' ') ?> FCFA</div>
      <div>
        <div style="display:flex;align-items:center;gap:10px;border:1px solid #E5E5E5;border-radius:4px;padding:4px 10px;">
          <button onclick="changeQty(this,-1)" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:#5A5A5A;line-height:1;padding:0 4px;">−</button>
          <span style="font-size:.9rem;font-weight:500;min-width:20px;text-align:center;"><?= $item['qte'] ?></span>
          <button onclick="changeQty(this,1)" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:#5A5A5A;line-height:1;padding:0 4px;">+</button>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-weight:600;font-size:.9rem;white-space:nowrap;"><?= number_format($item['prix']*$item['qte'],0,',',' ') ?> FCFA</span>
        <button onclick="this.closest('div[style*=grid]').remove()" style="background:none;border:none;cursor:pointer;color:#B91C1C;font-size:1rem;">🗑</button>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;">
      <a href="produits.php" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border:1.5px solid #E5E5E5;border-radius:4px;font-size:.82rem;color:#5A5A5A;text-decoration:none;">← Continuer mes achats</a>
      <button onclick="if(confirm('Vider le panier ?'))document.querySelectorAll('[style*=border-bottom]').forEach(e=>e.remove())" style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border:none;background:none;font-size:.82rem;color:#B91C1C;cursor:pointer;">🗑 Vider le panier</button>
    </div>
  </div>

  <!-- RÉSUMÉ -->
  <div class="order-summary">
    <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:16px;">Récapitulatif de panier</h3>

    <div class="summary-line">
      <span style="color:#5A5A5A;">Sous-total (<?= count($panier) ?> articles)</span>
      <span><?= number_format($total,0,',',' ') ?> FCFA</span>
    </div>

    <div class="summary-total">
      <span class="label">Total TTC</span>
      <span class="amount"><?= number_format($total,0,',',' ') ?> FCFA</span>
    </div>

    <div style="margin:18px 0;padding:14px;background:#F8F8F8;border-radius:6px;display:flex;align-items:flex-start;gap:10px;">
      <span style="font-size:1.2rem;">🔒</span>
      <div>
        <div style="font-size:.85rem;font-weight:600;margin-bottom:3px;">Paiement 100% sécurisé</div>
        <div style="font-size:.75rem;color:#5A5A5A;">Vos transactions sont protégées par un chiffrement SSL.</div>
      </div>
    </div>

    <a href="commande.php" style="display:flex;align-items:center;justify-content:center;width:100%;padding:14px;background:#0A0A0A;color:#fff;border-radius:4px;font-size:.95rem;font-weight:600;text-decoration:none;margin-bottom:8px;">Valider le panier</a>
    <div style="text-align:center;font-size:.75rem;color:#9A9A9A;">🔒 Paiement sécurisé</div>
  </div>
</div>

<!-- FEATURES -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid #E5E5E5;margin-top:32px;">
  <?php
  $feats=[['🚚','Livraison rapide','Partout au Bénin'],['🔄','Retour facile','Sous 7 jours'],['🔒','Paiement sécurisé','100% sécurisé'],['🎧','Support client','7j/7 à votre écoute']];
  foreach($feats as $f):
  ?>
  <div style="padding:18px 16px;display:flex;align-items:center;gap:10px;border-right:1px solid #E5E5E5;">
    <span style="font-size:1.4rem;"><?= $f[0] ?></span>
    <div><div style="font-size:.82rem;font-weight:600;"><?= $f[1] ?></div><div style="font-size:.72rem;color:#9A9A9A;"><?= $f[2] ?></div></div>
  </div>
  <?php endforeach; ?>
</div>

<script>
function changeQty(btn, delta){
  const span = btn.parentElement.querySelector('span');
  let v = parseInt(span.textContent)+delta;
  if(v<1) v=1;
  span.textContent=v;
}
</script>

<?php include '../includes/client_footer.php'; ?>
