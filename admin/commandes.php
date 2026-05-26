<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Gestion Commandes';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
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
    // Inclusion de la classe Commande
    require_once __DIR__ . '/../classes/Commande.php';
    // Instanciation de l'objet Commande
    $commandeObj = new Commande();
    // Récupération du terme de recherche depuis l'URL, ou chaîne vide par défaut
    $search = isset($_GET['q']) ? securiser($_GET['q']) : '';
    // Si un terme de recherche est fourni, recherche des commandes correspondantes, sinon récupération des 50 dernières commandes
    if ($search) {
        $commandes = $commandeObj->search($search);
    } else {
        $commandes = $commandeObj->getDernieresCommandes(50);
    }
    // Récupération du nombre total de commandes
    $nbCommandes = $commandeObj->getNombre();
    ?>
    <!-- Affichage des indicateurs KPI pour les commandes -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-card--navy"><div><div class="kpi-label">Commandes totales</div><div class="kpi-value"><?= $nbCommandes ?></div></div><i class="fas fa-receipt kpi-icon"></i></div>
        <div class="kpi-card kpi-card--green"><div><div class="kpi-label">Livrées</div><div class="kpi-value">—</div></div><i class="fas fa-check kpi-icon"></i></div>
        <div class="kpi-card kpi-card--amber"><div><div class="kpi-label">En cours</div><div class="kpi-value">—</div></div><i class="fas fa-clock kpi-icon"></i></div>
        <div class="kpi-card kpi-card--red"><div><div class="kpi-label">Annulées</div><div class="kpi-value">—</div></div><i class="fas fa-times kpi-icon"></i></div>
    </div>
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Toutes les commandes</span>
            <div class="flex gap-2">
                <select class="sort-select" onchange="filtrerCommandes(this.value)"><option value="">Tous les statuts</option><option>Livrée</option><option>Confirmée</option></select>
            </div>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Client</th><th>Montant</th><th>Statut</th><th>Mode retrait</th><th>Mode paiement</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($commandes)): ?>
            <!-- Si aucune commande n'est trouvée, affichage d'un message par défaut -->
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400);">Aucune commande.</td></tr>
            <?php else: foreach ($commandes as $cmd): ?>
            <!-- Boucle d'affichage de chaque commande dans une ligne du tableau -->
            <tr data-statut="<?= $cmd['statut'] ?>">
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
                <td><div class="flex gap-1">
                    <!-- Lien vers le détail de la commande -->
                    <a href="<?= BASE_URL ?>/admin/commandes/detail.php?id=<?= $cmd['id'] ?>" class="action-btn" title="Voir"><i class="fas fa-eye"></i></a>
                </div></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <!-- Pied du tableau indiquant le nombre de commandes affichées sur le total -->
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);"><span class="text-xs text-muted">Affichage <?= count($commandes) ?> sur <?= $nbCommandes ?> commandes</span></div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
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
