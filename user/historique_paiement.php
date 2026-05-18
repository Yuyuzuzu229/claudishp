<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Paiement.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Historique des paiements';
$commandeObj = new Commande();
$commandes = $commandeObj->getByUtilisateur($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
$activePage = 'paiement';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Mon compte</div>
        <h1 class="dash-page-title">Historique des paiements</h1>
        <p class="dash-page-sub">Consultez l'historique de tous vos paiements.</p>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Tous vos paiements (<?= count($commandes) ?>)</span>
            <select class="sort-select"><option>Filtrer par statut ▼</option><option>Réussi</option><option>Échoué</option></select>
        </div>
        <?php if (empty($commandes)): ?>
        <div style="padding:48px;text-align:center;"><i class="fas fa-credit-card" style="font-size:40px;color:var(--gray-200);margin-bottom:16px;display:block;"></i><p class="text-muted">Aucun paiement.</p></div>
        <?php else: ?>
        <table>
            <thead><tr><th>ID Paiement</th><th>Date</th><th>Statut</th><th>Montant</th><th>Mode paiement</th><th>Commande liée</th></tr></thead>
            <tbody>
            <?php foreach ($commandes as $i => $cmd): ?>
            <tr>
                <td><strong>#P<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                <td class="text-muted text-sm"><?= date('d/m/Y \à H:i', strtotime($cmd['date_commande'])) ?></td>
                <td><span class="badge badge-success">Réussi</span></td>
                <td><strong><?= formatPrix($cmd['montant_total']) ?></strong></td>
                <td class="text-muted"><?= securiser($cmd['mode_paiement'] ?? 'MTN MoMo') ?></td>
                <td><a href="<?= BASE_URL ?>/user/detail_commande.php?id=<?= $cmd['id'] ?>" style="color:var(--gray-600);font-size:12px;">#CMD-<?= str_pad($cmd['id'],6,'0',STR_PAD_LEFT) ?></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center;">
            <span class="text-xs text-muted">Affichage 1-<?= count($commandes) ?> sur <?= count($commandes) ?> paiements</span>
            <div class="pagination" style="margin-top:0;"><a href="#" class="page-btn"><i class="fas fa-chevron-left"></i></a><a href="#" class="page-btn active">1</a><a href="#" class="page-btn"><i class="fas fa-chevron-right"></i></a></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
