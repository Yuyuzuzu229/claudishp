<?php
$client_page = 'commandes';
$client_title = 'Mes commandes';
$client_subtitle = 'Retrouvez ici la liste de toutes vos commandes passées.';
include '../includes/client_header.php';
include '../includes/config.php';

$mes_commandes = [
  ['id'=>'#CMD-000125','date'=>'13/05/2026 à 10:15','statut'=>'Livrée','montant'=>'145 000','mode_retrait'=>'Livraison','paiement'=>'MTN MoMo'],
  ['id'=>'#CMD-000118','date'=>'08/05/2026 à 14:05','statut'=>'Livrée','montant'=>'89 000','mode_retrait'=>'Retrait en boutique','paiement'=>'Moov Money'],
  ['id'=>'#CMD-000109','date'=>'02/05/2026 à 09:00','statut'=>'En route','montant'=>'62 500','mode_retrait'=>'Livraison','paiement'=>'MTN MoMo'],
  ['id'=>'#CMD-000102','date'=>'27/04/2026 à 16:30','statut'=>'En préparation','montant'=>'215 000','mode_retrait'=>'Livraison','paiement'=>'Moov Money'],
  ['id'=>'#CMD-000095','date'=>'21/04/2026 à 10:45','statut'=>'Confirmée','montant'=>'39 000','mode_retrait'=>'Retrait en boutique','paiement'=>'MTN MoMo'],
  ['id'=>'#CMD-000087','date'=>'15/04/2026 à 18:10','statut'=>'Annulée','montant'=>'128 000','mode_retrait'=>'Retrait en boutique','paiement'=>'Moov Money'],
];
?>

<div class="card card-lg">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;">Toutes vos commandes (<?= count($mes_commandes) ?>)</h3>
    <select class="form-select form-control" style="width:180px;font-size:.82rem;">
      <option>Filtrer par statut</option>
      <option>Livrée</option>
      <option>En route</option>
      <option>En préparation</option>
      <option>Confirmée</option>
      <option>Annulée</option>
    </select>
  </div>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>ID Commande</th>
          <th>Date commande</th>
          <th>Statut</th>
          <th>Montant total</th>
          <th>Mode de retrait</th>
          <th>Mode de paiement</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($mes_commandes as $cmd): ?>
        <tr>
          <td style="font-weight:600;color:#0A0A0A;"><?= $cmd['id'] ?></td>
          <td style="color:#5A5A5A;"><?= $cmd['date'] ?></td>
          <td>
            <?php
            $smap = [
              'Livrée'         => 'badge-green',
              'En route'       => 'badge-blue',
              'En préparation' => 'badge-orange',
              'Confirmée'      => 'badge-blue',
              'Annulée'        => 'badge-gray',
            ];
            $cls = $smap[$cmd['statut']] ?? 'badge-gray';
            $dot = ['Livrée'=>'●','En route'=>'●','En préparation'=>'●','Confirmée'=>'●','Annulée'=>'●'];
            echo "<span class='badge $cls'>{$dot[$cmd['statut']]} {$cmd['statut']}</span>";
            ?>
          </td>
          <td style="font-weight:600;"><?= $cmd['montant'] ?> FCFA</td>
          <td style="color:#5A5A5A;"><?= $cmd['mode_retrait'] ?></td>
          <td style="color:#5A5A5A;"><?= $cmd['paiement'] ?></td>
          <td></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <div class="pagination" style="margin-top:16px;">
    <button class="page-btn">‹</button>
    <button class="page-btn active">1</button>
    <button class="page-btn">›</button>
  </div>
</div>

<?php include '../includes/client_footer.php'; ?>
