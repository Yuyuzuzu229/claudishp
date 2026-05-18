<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Gestion Commandes';
require_once __DIR__ . '/../includes/header.php';
$adminPage = 'commandes';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Gestion</div>
        <h1 class="dash-page-title">Commandes</h1>
        <p class="dash-page-sub">Gérez et suivez toutes les commandes clients</p>
    </div>
    <?php
    require_once __DIR__ . '/../classes/Commande.php';
    $commandeObj = new Commande();
    $commandes = $commandeObj->getDernieresCommandes(50);
    $nbCommandes = $commandeObj->getNombre();
    ?>
    <div class="kpi-grid kpi-grid-4" style="margin-bottom:20px;">
        <div class="kpi-card"><div><div class="kpi-label">Commandes totales</div><div class="kpi-value"><?= $nbCommandes ?></div></div><i class="fas fa-receipt kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Livrées</div><div class="kpi-value" style="color:var(--success);">—</div></div><i class="fas fa-check kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">En cours</div><div class="kpi-value" style="color:var(--warning);">—</div></div><i class="fas fa-clock kpi-icon"></i></div>
        <div class="kpi-card"><div><div class="kpi-label">Annulées</div><div class="kpi-value" style="color:var(--danger);">—</div></div><i class="fas fa-times kpi-icon"></i></div>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Toutes les commandes</span>
            <div class="flex gap-2">
                <select class="sort-select"><option>Tous les statuts</option><option>Confirmée</option><option>En préparation</option><option>En route</option><option>Livrée</option><option>Annulée</option></select>
            </div>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Client</th><th>Montant</th><th>Statut</th><th>Mode retrait</th><th>Mode paiement</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($commandes)): ?>
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400);">Aucune commande.</td></tr>
            <?php else: foreach ($commandes as $cmd): ?>
            <tr>
                <td><strong>#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;border-radius:50%;background:var(--gray-200);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;"><?= strtoupper(substr($cmd['prenom']??'',0,1).substr($cmd['nom']??'',0,1)) ?></div>
                        <span class="text-sm"><?= securiser(($cmd['prenom']??'').' '.($cmd['nom']??'')) ?></span>
                    </div>
                </td>
                <td class="text-sm font-semibold"><?= formatPrix($cmd['montant_total']) ?></td>
                <td><?= getStatutBadge($cmd['statut']) ?></td>
                <td class="text-sm text-muted"><?= securiser($cmd['mode_retrait']??'Livraison') ?></td>
                <td class="text-sm text-muted"><?= securiser($cmd['mode_paiement']??'MTN MoMo') ?></td>
                <td class="text-xs text-muted"><?= date('d/m/y H:i', strtotime($cmd['date_commande'])) ?></td>
                <td><div class="flex gap-1"><a href="<?= BASE_URL ?>/admin/commandes/detail.php?id=<?= $cmd['id'] ?>" class="action-btn" title="Voir"><i class="fas fa-eye"></i></a></div></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);"><span class="text-xs text-muted">Affichage <?= count($commandes) ?> sur <?= $nbCommandes ?> commandes</span></div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
