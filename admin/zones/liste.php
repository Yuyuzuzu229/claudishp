<?php
$admin_root = '../../';
$page_active = 'zones';
$page_title  = 'Zones de livraison';
$breadcrumb  = 'LOGISTIQUE';
include '../../includes/admin_header.php';
include '../../includes/config.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <p style="font-size:.85rem;color:#9A9A9A;"><?= count($zones) ?> zones configurées</p>
  <a href="ajouter.php" class="btn btn-primary btn-sm">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Ajouter une zone
  </a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Nom de la zone</th><th>Description</th><th>Frais (FCFA)</th><th>Nb commandes</th><th>Statut</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($zones as $z):
          $cls = $z['statut']==1?'badge-green':'badge-gray';
        ?>
        <tr>
          <td style="font-size:.8rem;color:#9A9A9A;">#Z<?= str_pad($z['id'],2,'0',STR_PAD_LEFT) ?></td>
          <td style="font-weight:600;"><?= htmlspecialchars($z['nom']) ?></td>
          <td style="color:#5A5A5A;font-size:.85rem;"><?= htmlspecialchars($z['description']) ?></td>
          <td style="font-weight:600;"><?= $z['frais'] ?></td>
          <td style="font-size:.85rem;"><?= $z['nb_commandes'] ?></td>
          <td><span class="badge <?= $cls ?>"><?= $z['statut']==1?'✓ Actif':'○ Inactif' ?></span></td>
          <td>
            <div class="table-actions">
              <a href="modifier.php?id=<?= $z['id'] ?>" class="btn-icon" title="Modifier">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <button class="btn-icon btn-delete" style="color:#B91C1C;">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$admin_root = '../../'; include '../../includes/admin_footer.php'; ?>
