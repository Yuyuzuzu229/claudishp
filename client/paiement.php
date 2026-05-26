<?php
// Définition de la page active pour le menu client
$client_page = 'paiement';
// Titre de la page
$client_title = 'Historique des paiements';
// Sous-titre affiché dans l'en-tête client
$client_subtitle = 'Consultez tous vos paiements effectués sur ClaudiShop.';
// Inclusion de l'en-tête client
include '../includes/client_header.php';

// Tableau des paiements effectués par le client
$paiements = [
  ['ref'=>'MTN-2026-84921','commande'=>'#CMD-000125','montant'=>'145 000','mode'=>'MTN MoMo','statut'=>'Confirmé','date'=>'13/05/2026 à 10:18'],
  ['ref'=>'MOV-2026-77341','commande'=>'#CMD-000118','montant'=>'89 000','mode'=>'Moov Money','statut'=>'Confirmé','date'=>'08/05/2026 à 14:08'],
  ['ref'=>'MTN-2026-71209','commande'=>'#CMD-000109','montant'=>'62 500','mode'=>'MTN MoMo','statut'=>'En attente','date'=>'02/05/2026 à 09:02'],
  ['ref'=>'MOV-2026-70088','commande'=>'#CMD-000102','montant'=>'215 000','mode'=>'Moov Money','statut'=>'Confirmé','date'=>'27/04/2026 à 16:34'],
  ['ref'=>'MTN-2026-61100','commande'=>'#CMD-000095','montant'=>'39 000','mode'=>'MTN MoMo','statut'=>'Échoué','date'=>'21/04/2026 à 10:47'],
  ['ref'=>'MOV-2026-55022','commande'=>'#CMD-000087','montant'=>'128 000','mode'=>'Moov Money','statut'=>'Remboursé','date'=>'15/04/2026 à 18:12'],
];
?>

<div class="card card-lg">
  <!-- En-tête avec titre, filtre par statut et bouton d'export -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
    <h3 style="font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:600;">Tous vos paiements (<?= count($paiements) ?>)</h3>
    <div style="display:flex;gap:8px;">
      <!-- Menu déroulant pour filtrer par statut -->
      <select class="form-control form-select" style="width:160px;font-size:.8rem;">
        <option>Tous les statuts</option>
        <option>Confirmé</option>
        <option>En attente</option>
        <option>Échoué</option>
        <option>Remboursé</option>
      </select>
      <!-- Bouton d'export -->
      <button style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border:1.5px solid #E5E5E5;border-radius:4px;font-size:.8rem;background:#fff;cursor:pointer;color:#5A5A5A;">
        ⬇ Exporter
      </button>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="table">
      <!-- En-tête du tableau des paiements -->
      <thead>
        <tr>
          <th>Référence</th>
          <th>Commande</th>
          <th>Montant</th>
          <th>Mode de paiement</th>
          <th>Statut</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <!-- Boucle d'affichage de chaque paiement -->
        <?php foreach($paiements as $p):
          // Mappage des statuts vers les classes CSS de badge
          $smap = ['Confirmé'=>'badge-green','En attente'=>'badge-orange','Échoué'=>'badge-red','Remboursé'=>'badge-blue'];
          $cls = $smap[$p['statut']] ?? 'badge-gray';
        ?>
        <tr>
          <td style="font-weight:500;font-size:.82rem;color:#5A5A5A;"><?= $p['ref'] ?></td>
          <td><a href="commandes.php" style="color:#0A0A0A;font-weight:600;font-size:.85rem;text-decoration:none;"><?= $p['commande'] ?></a></td>
          <td style="font-weight:600;"><?= $p['montant'] ?> FCFA</td>
          <td>
            <!-- Icône selon le mode de paiement (MTN MoMo ou Moov Money) -->
            <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;">
              <span><?= $p['mode']==='MTN MoMo'?'📱':'📲' ?></span>
              <?= $p['mode'] ?>
            </div>
          </td>
          <!-- Badge de statut avec couleur -->
          <td><span class="badge <?= $cls ?>"><?= $p['statut'] ?></span></td>
          <td style="color:#5A5A5A;font-size:.82rem;"><?= $p['date'] ?></td>
          <td>
            <!-- Bouton pour voir le reçu de paiement -->
            <button onclick="voirRecu('<?= $p['ref'] ?>')" style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:1px solid #E5E5E5;border-radius:4px;background:#fff;font-size:.75rem;cursor:pointer;color:#5A5A5A;">
              🧾 Reçu
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="pagination" style="margin-top:16px;">
    <button class="page-btn">‹</button>
    <button class="page-btn active">1</button>
    <button class="page-btn">›</button>
  </div>
</div>

<script>
// Fonction pour afficher le reçu de paiement
function voirRecu(ref){
  alert('🧾 Reçu de paiement\nRéférence : '+ref+'\n\nTéléchargement du reçu en cours…');
}
</script>

<?php include '../includes/client_footer.php'; ?>
