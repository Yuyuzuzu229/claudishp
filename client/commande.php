<?php
$client_page = 'commande';
$client_title = 'Passer commande';
$client_subtitle = 'Vérifiez vos informations et finalisez votre commande.';
include '../includes/client_header.php';
include '../includes/config.php';

$articles = [
  ['nom'=>'T-shirt Oversize Noir','detail'=>'Taille : L · Quantité : 1','prix'=>25000],
  ['nom'=>'Sneakers Blanches','detail'=>'Pointure : 42 · Quantité : 1','prix'=>65000],
  ['nom'=>'Casquette Noire','detail'=>'Quantité : 2','prix'=>20000],
  ['nom'=>'Sac à dos Urban','detail'=>'Quantité : 1','prix'=>35000],
];
$sous_total = array_sum(array_column($articles,'prix'));
$frais = 2000;
$total = $sous_total + $frais;
?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">
  <!-- FORMULAIRE -->
  <div>
    <!-- 1. MODE DE RETRAIT -->
    <div class="card card-lg" style="margin-bottom:16px;">
      <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:16px;">1. Mode de retrait</h3>
      <label style="display:flex;align-items:center;gap:14px;padding:16px;border:2px solid #0A0A0A;border-radius:6px;margin-bottom:10px;cursor:pointer;">
        <input type="radio" name="mode_retrait" value="livraison" checked style="accent-color:#0A0A0A;width:18px;height:18px;">
        <span style="font-size:1.3rem;">🚚</span>
        <div>
          <div style="font-weight:600;font-size:.9rem;">Livraison</div>
          <div style="font-size:.78rem;color:#5A5A5A;">Faites-vous livrer à l'adresse de votre choix.</div>
        </div>
      </label>
      <label style="display:flex;align-items:center;gap:14px;padding:16px;border:1.5px solid #E5E5E5;border-radius:6px;cursor:pointer;">
        <input type="radio" name="mode_retrait" value="boutique" style="accent-color:#0A0A0A;width:18px;height:18px;">
        <span style="font-size:1.3rem;">🏪</span>
        <div>
          <div style="font-weight:600;font-size:.9rem;">Retrait en boutique</div>
          <div style="font-size:.78rem;color:#5A5A5A;">Retirez votre commande dans l'une de nos boutiques.</div>
        </div>
      </label>
    </div>

    <!-- 2. ADRESSE -->
    <div class="card card-lg" style="margin-bottom:16px;" id="adresse-section">
      <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:16px;">2. Adresse de livraison</h3>
      <div class="form-group">
        <label class="form-label">Adresse de livraison</label>
        <div style="display:flex;gap:8px;">
          <input type="text" class="form-control" value="Rue 123, Tokoin Habitat, Cotonou, Bénin" style="flex:1;">
          <button style="padding:10px 14px;border:1.5px solid #E5E5E5;border-radius:4px;background:#fff;cursor:pointer;font-size:1rem;">📍</button>
        </div>
      </div>
      <button style="width:100%;padding:10px;border:1.5px solid #E5E5E5;border-radius:4px;background:#F8F8F8;font-size:.85rem;color:#5A5A5A;cursor:pointer;margin-bottom:14px;">📍 Se géolocaliser</button>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group">
          <label class="form-label">Nom complet</label>
          <input type="text" class="form-control" value="Adjoua K.">
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="tel" class="form-control" value="+229 90 12 34 56">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Instructions de livraison (facultatif)</label>
        <textarea class="form-control" rows="3" placeholder="Indications spéciales pour la livraison…"></textarea>
      </div>
    </div>

    <!-- 3. PAIEMENT -->
    <div class="card card-lg">
      <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:16px;">3. Paiement</h3>
      <p style="font-size:.82rem;color:#5A5A5A;margin-bottom:14px;">Choisissez votre moyen de paiement</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
        <label style="display:flex;align-items:center;gap:12px;padding:14px;border:2px solid #0A0A0A;border-radius:6px;cursor:pointer;">
          <input type="radio" name="paiement" value="mtn" checked style="accent-color:#0A0A0A;width:16px;height:16px;">
          <span style="font-size:1rem;">📱</span>
          <span style="font-weight:600;font-size:.9rem;">MTN MoMo</span>
        </label>
        <label style="display:flex;align-items:center;gap:12px;padding:14px;border:1.5px solid #E5E5E5;border-radius:6px;cursor:pointer;">
          <input type="radio" name="paiement" value="moov" style="accent-color:#0A0A0A;width:16px;height:16px;">
          <span style="font-size:1rem;">📱</span>
          <span style="font-weight:600;font-size:.9rem;">Moov Money</span>
        </label>
      </div>
      <div class="form-group">
        <label class="form-label">Numéro de téléphone MTN MoMo</label>
        <input type="tel" class="form-control" value="+229 90 12 34 56" id="tel-paiement">
      </div>
    </div>

    <!-- INFOS BAS -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:20px;">
      <?php $bots=[['📦','Besoin d\'aide ?','Consultez notre FAQ'],['🔄','Retours faciles','Sous 7 jours'],['🔒','Paiement sécurisé','100% protégé'],['🚚','Livraison rapide','Partout au Bénin']]; ?>
      <?php foreach($bots as $b): ?>
      <div style="display:flex;gap:8px;align-items:flex-start;">
        <span style="font-size:1.1rem;"><?= $b[0] ?></span>
        <div><div style="font-size:.78rem;font-weight:600;"><?= $b[1] ?></div><div style="font-size:.72rem;color:#9A9A9A;"><?= $b[2] ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RÉSUMÉ COMMANDE -->
  <div class="order-summary">
    <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;margin-bottom:16px;">Récapitulatif de la commande</h3>

    <?php foreach($articles as $a): ?>
    <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #F5F5F5;">
      <div style="width:44px;height:52px;background:#F0F0F0;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#C0C0C0;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      </div>
      <div style="flex:1;">
        <div style="font-size:.82rem;font-weight:500;"><?= htmlspecialchars($a['nom']) ?></div>
        <div style="font-size:.72rem;color:#9A9A9A;"><?= $a['detail'] ?></div>
      </div>
      <div style="font-size:.85rem;font-weight:500;white-space:nowrap;"><?= number_format($a['prix'],0,',',' ') ?> FCFA</div>
    </div>
    <?php endforeach; ?>

    <div class="summary-line" style="margin-top:10px;">
      <span style="color:#5A5A5A;font-size:.85rem;">Sous-total</span>
      <span style="font-size:.85rem;"><?= number_format($sous_total,0,',',' ') ?> FCFA</span>
    </div>
    <div class="summary-line">
      <span style="color:#5A5A5A;font-size:.85rem;">Frais de livraison</span>
      <span style="font-size:.85rem;"><?= number_format($frais,0,',',' ') ?> FCFA</span>
    </div>
    <div class="summary-total">
      <span class="label">Total</span>
      <span class="amount" style="font-size:1.2rem;"><?= number_format($total,0,',',' ') ?> FCFA</span>
    </div>

    <div style="margin:16px 0;padding:12px;background:#F8F8F8;border-radius:6px;">
      <div style="font-size:.82rem;font-weight:600;margin-bottom:4px;">Paiement</div>
      <div style="font-size:.75rem;color:#5A5A5A;margin-bottom:10px;">Moyen de paiement sélectionné</div>
      <label style="display:flex;align-items:center;gap:8px;padding:10px;border:2px solid #0A0A0A;border-radius:6px;background:#fff;">
        <input type="radio" checked style="accent-color:#0A0A0A;width:14px;height:14px;">
        <span style="font-size:.85rem;font-weight:600;">MTN MoMo</span>
      </label>
      <div style="font-size:.75rem;color:#5A5A5A;margin-top:10px;margin-bottom:4px;">Numéro de téléphone MTN MoMo</div>
      <input type="tel" class="form-control" value="+229 90 12 34 56" style="font-size:.82rem;">
    </div>

    <div style="text-align:center;margin-bottom:12px;">
      <div style="font-size:1.1rem;font-weight:700;"><?= number_format($total,0,',',' ') ?> FCFA</div>
      <div style="font-size:.72rem;color:#9A9A9A;">🔒 Vos paiements sont sécurisés à 100%</div>
    </div>

    <button onclick="confirmerCommande()" style="width:100%;padding:14px;background:#0A0A0A;color:#fff;border:none;border-radius:4px;font-size:.95rem;font-weight:600;cursor:pointer;">Confirmer la commande</button>
  </div>
</div>

<script>
function confirmerCommande(){
  if(confirm('Confirmer votre commande pour <?= number_format($total,0,',',' ') ?> FCFA via MTN MoMo ?')){
    alert('✅ Commande confirmée ! Vous recevrez une notification de paiement sur votre téléphone.');
    window.location.href='commandes.php';
  }
}
document.querySelectorAll('input[name="paiement"]').forEach(r=>{
  r.addEventListener('change',function(){
    document.getElementById('tel-paiement').placeholder = 'Numéro '+this.value.toUpperCase();
  });
});
document.querySelectorAll('input[name="mode_retrait"]').forEach(r=>{
  r.addEventListener('change',function(){
    const sec = document.getElementById('adresse-section');
    sec.style.display = this.value==='livraison'?'block':'none';
  });
});
</script>

<?php include '../includes/client_footer.php'; ?>
