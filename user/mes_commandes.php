<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Commande pour gérer les commandes
require_once __DIR__ . '/../classes/Commande.php';
// Inclusion de la classe Panier pour gérer le panier
require_once __DIR__ . '/../classes/Panier.php';
// Inclusion de la classe Notification pour gérer les notifications
require_once __DIR__ . '/../classes/Notification.php';

// Vérification : rediriger vers la connexion si l'utilisateur n'est pas connecté
if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }
// Les invités (guest_converted) n'ont pas accès à la liste des commandes
if (!empty($_SESSION['guest_converted'])) { redirect(BASE_URL . '/index.php'); }

// Définition du titre de la page
$pageTitle = 'Mes commandes';
// Instanciation de l'objet Commande
$commandeObj = new Commande();
// Récupération de toutes les commandes de l'utilisateur connecté
$commandes = $commandeObj->getByUtilisateur($_SESSION['user_id']);

// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour la sidebar
$activePage = 'commandes';
?>
<!-- Début du layout du tableau de bord -->
<div class="dashboard-layout">
<?php // Inclusion de la barre latérale utilisateur ?>
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php // Inclusion de la barre supérieure du tableau de bord ?>
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <!-- En-tête de page -->
    <div class="dash-page-header">
        <div class="dash-page-label">Mes commandes</div>
        <h1 class="dash-page-title">Mes commandes</h1>
        <p class="dash-page-sub">Retrouvez ici la liste de toutes vos commandes passées.</p>
    </div>

    <!-- Carte contenant le tableau des commandes -->
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Toutes vos commandes (<?= count($commandes) ?>)</span>
            <select class="sort-select" onchange="filtrerCommandes(this.value)">
                <option value="">Tous les statuts</option>
                <option>Livrée</option>
                <option>Confirmée</option>
            </select>
        </div>
        <?php // Vérification si l'utilisateur a des commandes ?>
        <?php if (empty($commandes)): ?>
        <!-- Message si aucune commande -->
        <div style="padding:48px;text-align:center;">
            <i class="fas fa-receipt" style="font-size:40px;color:var(--gray-200);margin-bottom:16px;display:block;"></i>
            <p class="text-muted">Aucune commande pour le moment.</p>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark" style="margin-top:16px;">Découvrir nos produits</a>
        </div>
        <?php else: ?>
        <!-- Tableau des commandes -->
        <table>
            <thead>
                <tr>
                    <th>ID Commande</th>
                    <th>Date commande</th>
                    <th>Statut</th>
                    <th>Montant total</th>
                    <th>Mode de retrait</th>
                    <th>Mode de paiement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php // Boucle d'affichage des commandes ?>
            <?php foreach ($commandes as $cmd): ?>
            <tr data-statut="<?= $cmd['statut'] ?>">
                <td><strong>#CMD-<?= str_pad($cmd['id'],6,'0',STR_PAD_LEFT) ?></strong></td>
                <td class="text-muted"><?= date('d/m/Y \à H:i', strtotime($cmd['date_commande'])) ?></td>
                <td><?= getStatutBadge($cmd['statut']) ?></td>
                <td><strong><?= formatPrix($cmd['montant_total']) ?></strong></td>
                <td class="text-muted"><?= securiser($cmd['mode_retrait'] ?? 'Livraison') ?></td>
                <td class="text-muted"><?= renderModePaiement($cmd['mode_paiement'] ?? '') ?></td>
                <td>
                    <!-- Lien vers le détail de la commande -->
                    <a href="<?= BASE_URL ?>/user/detail_commande.php?id=<?= $cmd['id'] ?>" style="display:flex;align-items:center;gap:4px;font-size:12px;color:var(--gray-600);"><i class="fas fa-eye"></i> Voir</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pied du tableau : pagination (statique) -->
        <div style="padding:14px 16px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--gray-100);">
            <span class="text-xs text-muted">Affichage 1-<?= min(count($commandes),10) ?> sur <?= count($commandes) ?> commandes</span>
            <div class="pagination" style="margin-top:0;">
                <a href="#" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                <a href="#" class="page-btn active">1</a>
                <a href="#" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Section "Why Buy" - avantages clients -->
    <div class="why-buy" style="margin-top:28px;">
        <div class="why-buy-item"><i class="fas fa-box"></i><h4>Besoin d'aide ?</h4><p>Consultez notre FAQ ou contactez notre support</p></div>
        <div class="why-buy-item"><i class="fas fa-undo"></i><h4>Retours faciles</h4><p>Retournez vos articles sous 7 jours</p></div>
        <div class="why-buy-item"><i class="fas fa-shield-alt"></i><h4>Paiement sécurisé</h4><p>Vos paiements sont protégés à 100%</p></div>
        <div class="why-buy-item"><i class="fas fa-truck"></i><h4>Livraison rapide</h4><p>Partout au Bénin</p></div>
    </div>
</div>
<!-- Pied de page -->
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
<script>
function filtrerCommandes(statut) {
    document.querySelectorAll('tbody tr').forEach(function(tr) {
        if (!statut || tr.dataset.statut === statut) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}
</script>
<script>
(function(){
    var currentUrl = window.location.href;
    function actualiser() {
        var selectEl = document.querySelector('.sort-select');
        var currentFilter = selectEl ? selectEl.value : '';
        fetch(currentUrl)
            .then(function(r){ return r.text(); })
            .then(function(html){
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newCard = doc.querySelector('.table-card');
                var oldCard = document.querySelector('.table-card');
                if (newCard && oldCard) {
                    oldCard.innerHTML = newCard.innerHTML;
                }
                if (currentFilter && selectEl) {
                    var newSelect = document.querySelector('.sort-select');
                    if (newSelect) {
                        newSelect.value = currentFilter;
                        filtrerCommandes(currentFilter);
                    }
                }
                setTimeout(actualiser, 5000);
            })
            .catch(function(){ setTimeout(actualiser, 5000); });
    }
    setTimeout(actualiser, 5000);
})();
</script>
</body></html>
