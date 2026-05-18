<?php
$client_page = 'notifications';
$client_title = 'Mes notifications';
$client_subtitle = 'Consultez vos dernières alertes et messages.';
include '../includes/client_header.php';

$notifications = [
  ['type'=>'commande','icon'=>'📦','titre'=>'Commande #CMD-000125 livrée','message'=>'Votre commande a été livrée avec succès. Merci pour votre confiance !','date'=>'13/05/2026 à 14:32','lu'=>false],
  ['type'=>'paiement','icon'=>'💳','titre'=>'Paiement confirmé','message'=>'Votre paiement de 145 000 FCFA via MTN MoMo a été confirmé.','date'=>'13/05/2026 à 10:18','lu'=>false],
  ['type'=>'livraison','icon'=>'🚚','titre'=>'Votre commande est en route','message'=>'Koffi Adé est en chemin avec votre commande #CMD-000125.','date'=>'13/05/2026 à 11:05','lu'=>false],
  ['type'=>'promo','icon'=>'🏷️','titre'=>'Soldes — jusqu\'à -40%','message'=>'Profitez de nos soldes sur une sélection de vêtements Homme, Femme et Enfant.','date'=>'10/05/2026 à 08:00','lu'=>true],
  ['type'=>'commande','icon'=>'📦','titre'=>'Commande #CMD-000118 livrée','message'=>'Votre commande a été livrée à votre boutique préférée.','date'=>'09/05/2026 à 16:45','lu'=>true],
  ['type'=>'avis','icon'=>'⭐','titre'=>'Donnez votre avis','message'=>'Vous avez récemment acheté des Sneakers Blanches. Partagez votre expérience !','date'=>'09/05/2026 à 10:00','lu'=>true],
];

$nb_non_lus = count(array_filter($notifications, fn($n) => !$n['lu']));
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div style="display:flex;align-items:center;gap:10px;">
    <span style="font-size:.85rem;color:#5A5A5A;"><?= $nb_non_lus ?> notification(s) non lue(s)</span>
  </div>
  <button onclick="marquerToutes()" style="padding:7px 16px;background:none;border:1.5px solid #E5E5E5;border-radius:4px;font-size:.8rem;color:#5A5A5A;cursor:pointer;">Tout marquer comme lu</button>
</div>

<div class="card" style="padding:0;overflow:hidden;">
  <?php foreach($notifications as $idx => $notif): ?>
  <div id="notif-<?= $idx ?>" style="display:flex;align-items:flex-start;gap:14px;padding:18px 20px;border-bottom:1px solid #F5F5F5;background:<?= !$notif['lu'] ? '#FAFAF8' : '#fff' ?>;transition:background .3s;cursor:pointer;" onclick="marquerLu(<?= $idx ?>)">
    <!-- Icône -->
    <div style="width:42px;height:42px;border-radius:10px;background:<?= !$notif['lu'] ? '#FFF8E6' : '#F5F5F5' ?>;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
      <?= $notif['icon'] ?>
    </div>

    <!-- Contenu -->
    <div style="flex:1;min-width:0;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <span style="font-size:.88rem;font-weight:<?= !$notif['lu'] ? '600' : '500' ?>;color:#0A0A0A;"><?= htmlspecialchars($notif['titre']) ?></span>
        <?php if(!$notif['lu']): ?>
        <span style="width:8px;height:8px;border-radius:50%;background:#C9A03D;flex-shrink:0;"></span>
        <?php endif; ?>
      </div>
      <div style="font-size:.8rem;color:#5A5A5A;line-height:1.5;"><?= htmlspecialchars($notif['message']) ?></div>
    </div>

    <!-- Date -->
    <div style="font-size:.72rem;color:#9A9A9A;white-space:nowrap;flex-shrink:0;"><?= $notif['date'] ?></div>
  </div>
  <?php endforeach; ?>
</div>

<script>
function marquerLu(idx){
  const el = document.getElementById('notif-'+idx);
  el.style.background = '#fff';
  const dot = el.querySelector('[style*="border-radius:50%"]');
  if(dot) dot.remove();
  const title = el.querySelector('[style*="font-weight"]');
  if(title) title.style.fontWeight = '500';
}
function marquerToutes(){
  document.querySelectorAll('[id^="notif-"]').forEach(el=>{
    el.style.background='#fff';
    const dot=el.querySelector('[style*="border-radius:50%;background:#C9A03D"]');
    if(dot)dot.remove();
  });
  document.querySelector('[onclick="marquerToutes()"]').textContent='✓ Tout lu';
}
</script>

<?php include '../includes/client_footer.php'; ?>
