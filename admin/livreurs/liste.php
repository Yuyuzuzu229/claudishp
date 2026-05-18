<?php
$admin_root = '../../';
$page_active = 'livreurs';
$page_title  = 'Livreurs';
$breadcrumb  = 'LOGISTIQUE';
include '../../includes/admin_header.php';
include '../../includes/config.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
  <div style="display:flex;gap:8px;">
    <?php foreach(['Tous','Disponible','En livraison','Inactif'] as $f): ?>
    <button style="padding:6px 14px;border:1.5px solid #E5E5E5;border-radius:20px;background:#fff;font-size:.8rem;cursor:pointer;color:#5A5A5A;transition:all .15s;"
      onmouseover="this.style.borderColor='#0A0A0A';this.style.color='#0A0A0A'"
      onmouseout="this.style.borderColor='#E5E5E5';this.style.color='#5A5A5A'"><?= $f ?></button>
    <?php endforeach; ?>
  </div>
  <a href="ajouter.php" class="btn btn-primary btn-sm">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Ajouter un livreur
  </a>
</div>

<div class="card" style="padding:0;overflow:hidden;">
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Nom</th><th>Téléphone</th><th>Email</th>
          <th>Véhicule</th><th>Zone assignée</th><th>Statut</th><th>Date embauche</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($livreurs as $l):
          $smap = ['Disponible'=>'badge-green','En livraison'=>'badge-blue','Inactif'=>'badge-gray'];
          $cls  = $smap[$l['statut']] ?? 'badge-gray';
          $initiales = strtoupper(substr($l['nom'],0,1).substr(strrchr($l['nom'],' '),1,1));
        ?>
        <tr>
          <td style="font-size:.8rem;color:#9A9A9A;">#L<?= str_pad($l['id'],3,'0',STR_PAD_LEFT) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:32px;height:32px;border-radius:50%;background:#0A0A0A;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:600;flex-shrink:0;"><?= $initiales ?></div>
              <div>
                <div style="font-weight:500;font-size:.88rem;"><?= htmlspecialchars($l['nom']) ?></div>
                <div style="font-size:.72rem;color:#9A9A9A;"><?= $l['est_actif'] ? 'Actif' : 'Inactif' ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:.85rem;"><?= $l['telephone'] ?></td>
          <td style="font-size:.82rem;color:#5A5A5A;"><?= $l['email'] ?></td>
          <td>
            <span style="display:inline-flex;align-items:center;gap:5px;font-size:.82rem;">
              <?= $l['vehicule']==='Moto'?'🏍':'🚗' ?> <?= $l['vehicule'] ?>
            </span>
          </td>
          <td style="font-size:.82rem;color:#5A5A5A;max-width:160px;"><?= htmlspecialchars($l['zone']) ?></td>
          <td><span class="badge <?= $cls ?>"><?= $l['statut'] ?></span></td>
          <td style="font-size:.78rem;color:#9A9A9A;"><?= $l['date_embauche'] ?></td>
          <td>
            <div class="table-actions">
              <button class="btn-icon" title="Voir">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
              <a href="modifier.php?id=<?= $l['id'] ?>" class="btn-icon" title="Modifier">
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
