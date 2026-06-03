<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Gestion Paiements';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
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
    // Inclusion des classes Paiement et Commande
    require_once __DIR__ . '/../classes/Paiement.php';
    require_once __DIR__ . '/../classes/Commande.php';
    // Instanciation de l'objet Commande
    $commandeObj = new Commande();
    // Récupération des 50 dernières commandes
    $commandes = $commandeObj->getDernieresCommandes(50);
    // Récupération du total des ventes
    $total = $commandeObj->getTotalVentes();
    ?>
    <!-- Affichage des indicateurs KPI pour les paiements -->
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
            <!-- Boucle d'affichage de chaque commande dans l'historique des paiements -->
            <tr>
                <td><strong>#P<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                <td class="text-xs text-muted">#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></td>
                <td class="text-sm"><?= securiser(($cmd['prenom']??'').' '.($cmd['nom']??'')) ?></td>
                <td class="text-sm font-semibold"><?= formatPrix($cmd['montant_total']) ?></td>
                <td class="text-sm text-muted"><?= renderModePaiement($cmd['mode_paiement']??'') ?></td>
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
