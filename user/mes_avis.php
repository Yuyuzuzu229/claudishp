<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Avis.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Mes avis';
$avisObj = new Avis();
$avis = $avisObj->getByUtilisateur($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
$activePage = 'avis';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Avis &amp; Communication</div>
        <h1 class="dash-page-title">Mes avis</h1>
        <p class="dash-page-sub">Retrouvez ici tous les avis que vous avez laissés.</p>
    </div>

    <div class="dash-page-actions" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div></div>
        <a href="<?= BASE_URL ?>/user/nouvel_avis.php" class="btn btn-dark" style="padding:8px 18px;font-size:13px;">
            <i class="fas fa-plus" style="margin-right:6px;"></i>Nouvel avis
        </a>
    </div>
    <div class="table-card">
        <div class="table-card-header"><span class="table-card-title">Tous vos avis (<?= count($avis) ?>)</span></div>
        <?php if (empty($avis)): ?>
        <div style="padding:48px;text-align:center;">
            <i class="fas fa-star" style="font-size:40px;color:var(--gray-200);margin-bottom:16px;display:block;"></i>
            <p class="text-muted">Vous n'avez pas encore donné d'avis.</p>
            <a href="<?= BASE_URL ?>/user/nouvel_avis.php" class="btn btn-dark" style="margin-top:16px;">Évaluer un produit acheté</a>
        </div>
        <?php else: ?>
        <table>
            <thead><tr><th>Produit</th><th>Note</th><th>Avis</th><th>Date</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($avis as $av): ?>
            <tr>
                <td>
                    <div class="flex gap-2 items-center">
                        <div class="panier-table-img" style="width:40px;height:40px;font-size:14px;"><i class="fas fa-tshirt"></i></div>
                        <span class="text-sm font-semibold"><?= securiser($av['produit_nom'] ?? 'Produit') ?></span>
                    </div>
                </td>
                <td><span style="color:var(--warning);"><?= str_repeat('★', $av['note'] ?? 5) ?><?= str_repeat('☆', 5 - ($av['note'] ?? 5)) ?></span></td>
                <td class="text-muted text-sm"><?= securiser(substr($av['commentaire'] ?? '', 0, 80)) ?>...</td>
                <td class="text-xs text-muted"><?= date('d/m/Y', strtotime($av['date_avis'] ?? 'now')) ?></td>
                <td><?= getStatutBadge($av['statut'] ?? 'Publié') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
