<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Commande.php';
require_once __DIR__ . '/../../classes/Livraison.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$id = intval($_GET['id'] ?? 0);
$commandeObj = new Commande();
$cmd = $commandeObj->getById($id);

$livraisonObj = new Livraison();
$suivi = $livraisonObj->getByCommande($id);

if (!$cmd) { $_SESSION['error'] = 'Commande introuvable.'; redirect(BASE_URL . '/admin/commandes.php'); }

$pageTitle = 'Commande #' . str_pad($id, 4, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../../includes/header.php';
$adminPage = 'commandes';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Gestion</div>
        <h1 class="dash-page-title">Commande #<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?></h1>
        <div class="flex gap-2" style="margin-top:4px;">
            <a href="<?= BASE_URL ?>/admin/commandes.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <div class="dash-two-col" style="margin-bottom:18px;">
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Informations commande</span></div>
            <div style="padding:16px;">
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">ID</span><span class="text-sm font-semibold">#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Statut</span><span><?= getStatutBadge($cmd['statut']) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Montant total</span><span class="text-sm font-semibold"><?= formatPrix($cmd['montant_total']) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Mode retrait</span><span class="text-sm"><?= securiser($cmd['mode_retrait'] ?? 'Livraison') ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Mode paiement</span><span class="text-sm"><?= securiser($cmd['mode_paiement'] ?? 'MTN MoMo') ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Date</span><span class="text-sm"><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;"><span class="text-sm text-muted">Adresse livraison</span><span class="text-sm" style="text-align:right;"><?= securiser($cmd['adresse_livraison'] ?? '—') ?></span></div>
            </div>
        </div>
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Client</span></div>
            <div style="padding:16px;">
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Nom</span><span class="text-sm font-semibold"><?= securiser(($cmd['prenom'] ?? '') . ' ' . ($cmd['nom'] ?? '')) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Email</span><span class="text-sm"><?= securiser($cmd['email'] ?? '—') ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;"><span class="text-sm text-muted">Téléphone</span><span class="text-sm"><?= securiser($cmd['telephone'] ?? '—') ?></span></div>
            </div>
        </div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
