<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Paiement pour gérer les paiements
require_once __DIR__ . '/../classes/Paiement.php';
// Inclusion de la classe Commande pour gérer les commandes
require_once __DIR__ . '/../classes/Commande.php';
// Inclusion de la classe Panier pour gérer le panier
require_once __DIR__ . '/../classes/Panier.php';
// Inclusion de la classe Notification pour gérer les notifications
require_once __DIR__ . '/../classes/Notification.php';

// Vérification : rediriger vers la connexion si l'utilisateur n'est pas connecté
if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Historique des paiements';
// Instanciation de l'objet Commande
$commandeObj = new Commande();
// Récupération de toutes les commandes de l'utilisateur connecté
$commandes = $commandeObj->getByUtilisateur($_SESSION['user_id']);

// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour la sidebar
$activePage = 'paiement';
?>
<!-- Début du layout du tableau de bord -->
<div class="dashboard-layout">
<?php // Inclusion de la barre latérale utilisateur ?>
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php // Inclusion de la barre supérieure du tableau de bord ?>
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <!-- En-tête de la page -->
    <div class="dash-page-header">
        <div class="dash-page-label">Mon compte</div>
        <h1 class="dash-page-title">Historique des paiements</h1>
        <p class="dash-page-sub">Consultez l'historique de tous vos paiements.</p>
    </div>

    <!-- Carte contenant le tableau des paiements -->
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Tous vos paiements (<?= count($commandes) ?>)</span>
            <select class="sort-select" onchange="filtrerPaiements(this.value)"><option value="">Tous les statuts</option><option>Réussi</option><option>Échoué</option></select>
        </div>
        <?php // Vérification si l'utilisateur a des paiements ?>
        <?php if (empty($commandes)): ?>
        <!-- Message si aucun paiement -->
        <div style="padding:48px;text-align:center;"><i class="fas fa-credit-card" style="font-size:40px;color:var(--gray-200);margin-bottom:16px;display:block;"></i><p class="text-muted">Aucun paiement.</p></div>
        <?php else: ?>
        <!-- Tableau des paiements -->
        <table>
            <thead><tr><th>ID Paiement</th><th>Date</th><th>Statut</th><th>Montant</th><th>Mode paiement</th><th>Commande liée</th></tr></thead>
            <tbody>
            <?php // Boucle d'affichage des commandes (utilisées comme historique de paiements) ?>
            <?php foreach ($commandes as $i => $cmd): ?>
            <tr data-statut="Réussi">
                <td><strong>#P<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                <td class="text-muted text-sm"><?= date('d/m/Y \à H:i', strtotime($cmd['date_commande'])) ?></td>
                <td><span class="badge badge-success">Réussi</span></td>
                <td><strong><?= formatPrix($cmd['montant_total']) ?></strong></td>
                <td class="text-muted"><?= renderModePaiement($cmd['mode_paiement'] ?? '') ?></td>
                <td><a href="<?= BASE_URL ?>/user/detail_commande.php?id=<?= $cmd['id'] ?>" style="color:var(--gray-600);font-size:12px;">#CMD-<?= str_pad($cmd['id'],6,'0',STR_PAD_LEFT) ?></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pied du tableau : pagination (statique) -->
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center;">
            <span class="text-xs text-muted">Affichage 1-<?= count($commandes) ?> sur <?= count($commandes) ?> paiements</span>
            <div class="pagination" style="margin-top:0;"><a href="#" class="page-btn"><i class="fas fa-chevron-left"></i></a><a href="#" class="page-btn active">1</a><a href="#" class="page-btn"><i class="fas fa-chevron-right"></i></a></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- Pied de page -->
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
<script>
function filtrerPaiements(statut) {
    document.querySelectorAll('tbody tr').forEach(function(tr) {
        if (!statut || tr.dataset.statut === statut) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}
</script>
</body></html>
