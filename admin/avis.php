<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Avis Clients';
require_once __DIR__ . '/../includes/header.php';
$adminPage = 'avis';

require_once __DIR__ . '/../classes/Avis.php';
$avisObj = new Avis();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        if ($action === 'approuver') { $avisObj->updateStatut($id, 'Publié'); $_SESSION['success'] = 'Avis approuvé.'; }
        elseif ($action === 'refuser') { $avisObj->updateStatut($id, 'Refusé'); $_SESSION['success'] = 'Avis refusé.'; }
        elseif ($action === 'supprimer') { $avisObj->supprimer($id); $_SESSION['success'] = 'Avis supprimé.'; }
    }
    redirect(BASE_URL . '/admin/avis.php');
}

$avis = $avisObj->getAll();
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Finance &amp; Communication</div>
        <h1 class="dash-page-title">Avis clients</h1>
        <p class="dash-page-sub">Modérez les avis clients</p>
    </div>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Tous les avis (<?= count($avis) ?>)</span>
            <select class="sort-select"><option>Tous les avis</option><option>★★★★★</option><option>★★★★☆</option><option>Non modérés</option></select>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Client</th><th>Produit</th><th>Note</th><th>Commentaire</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($avis)): ?>
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun avis.</td></tr>
            <?php else: foreach ($avis as $av): ?>
            <tr>
                <td class="text-xs text-muted">#<?= $av['id'] ?></td>
                <td class="text-sm"><?= securiser(($av['prenom']??'').' '.($av['nom']??'')) ?></td>
                <td class="text-sm font-semibold"><?= securiser($av['produit_nom']??'—') ?></td>
                <td><span style="color:var(--warning);"><?= str_repeat('★',$av['note']??5) ?></span></td>
                <td class="text-xs text-muted"><?= securiser(substr($av['commentaire']??'',0,50)) ?>...</td>
                <td><?= getStatutBadge($av['statut']??'Publié') ?></td>
                <td class="text-xs text-muted"><?= date('d/m/Y',strtotime($av['date_creation']??'now')) ?></td>
                <td><div class="flex gap-1">
    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="approuver"><input type="hidden" name="id" value="<?= $av['id'] ?>"><button type="submit" class="action-btn" title="Approuver"><i class="fas fa-check"></i></button></form>
    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="refuser"><input type="hidden" name="id" value="<?= $av['id'] ?>"><button type="submit" class="action-btn" title="Refuser"><i class="fas fa-times"></i></button></form>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet avis ?');"><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $av['id'] ?>"><button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button></form>
</div></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
