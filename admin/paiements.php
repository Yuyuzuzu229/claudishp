<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Gestion Paiements';
require_once __DIR__ . '/../includes/header.php';
$adminPage = 'paiements';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Finance &amp; Communication</div>
        <h1 class="dash-page-title">Paiements</h1>
        <p class="dash-page-sub">Suivez tous les paiements reçus</p>
    </div>
    <?php
    require_once __DIR__ . '/../classes/Paiement.php';
    require_once __DIR__ . '/../classes/Commande.php';
    $commandeObj = new Commande();
    $commandes = $commandeObj->getDernieresCommandes(50);
    $total = $commandeObj->getTotalVentes();
    ?>
    <div class="kpi-grid">
        <div class="kpi-card kpi-card--navy"><div><div class="kpi-label">Total encaissé</div><div class="kpi-value kpi-value--sm"><?= formatPrix($total) ?></div></div><i class="fas fa-dollar-sign kpi-icon"></i></div>
        <div class="kpi-card kpi-card--green"><div><div class="kpi-label">Paiements réussis</div><div class="kpi-value"><?= count($commandes) ?></div></div><i class="fas fa-check kpi-icon"></i></div>
        <div class="kpi-card kpi-card--blue"><div><div class="kpi-label">MTN MoMo</div><div class="kpi-value">—</div></div><i class="fas fa-mobile-alt kpi-icon"></i></div>
        <div class="kpi-card kpi-card--amber"><div><div class="kpi-label">Moov Money</div><div class="kpi-value">—</div></div><i class="fas fa-mobile-alt kpi-icon"></i></div>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Historique des paiements</span>
        </div>
        <table>
            <thead><tr><th>ID Paiement</th><th>Commande</th><th>Client</th><th>Montant</th><th>Mode paiement</th><th>Statut</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($commandes as $cmd): ?>
            <tr>
                <td><strong>#P<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                <td class="text-xs text-muted">#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></td>
                <td class="text-sm"><?= securiser(($cmd['prenom']??'').' '.($cmd['nom']??'')) ?></td>
                <td class="text-sm font-semibold"><?= formatPrix($cmd['montant_total']) ?></td>
                <td class="text-sm text-muted"><?= securiser($cmd['mode_paiement']??'MTN MoMo') ?></td>
                <td><span class="badge badge-success">Réussi</span></td>
                <td class="text-xs text-muted"><?= date('d/m/y H:i', strtotime($cmd['date_commande'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
