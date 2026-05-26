<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Commande pour gérer les commandes
require_once __DIR__ . '/../classes/Commande.php';
// Inclusion de la classe Avis pour gérer les avis clients
require_once __DIR__ . '/../classes/Avis.php';
// Inclusion de la classe Panier pour gérer le panier
require_once __DIR__ . '/../classes/Panier.php';
// Inclusion de la classe Notification pour gérer les notifications
require_once __DIR__ . '/../classes/Notification.php';

// Vérification : rediriger vers la page de connexion si l'utilisateur n'est pas connecté
if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }
// Les invités (guest_converted) n'ont pas accès au dashboard client
if (!empty($_SESSION['guest_converted'])) { redirect(BASE_URL . '/index.php'); }

// Définition du titre de la page
$pageTitle = 'Dashboard';
// Instanciation des objets nécessaires
$commandeObj = new Commande();
$avisObj = new Avis();
$panierObj = new Panier();
$notifObj = new Notification();

// Récupération de toutes les commandes de l'utilisateur connecté
$allCommandes = $commandeObj->getByUtilisateur($_SESSION['user_id']);
// Calcul du nombre total de commandes
$nbCommandes = count($allCommandes);
// Extraction des 5 dernières commandes pour l'affichage
$dernieresCommandes = array_slice($allCommandes, 0, 5);
// Compte du nombre d'avis donnés par l'utilisateur
$nbAvis = count($avisObj->getByUtilisateur($_SESSION['user_id']));
// Récupération de l'identifiant du panier actif
$panierId = $panierObj->getPanierActif($_SESSION['user_id']);
// Compte du nombre d'articles dans le panier
$nbPanier = $panierObj->getNombreArticles($panierId);
// Extraction des 4 premières lignes du panier
$lignesPanier = array_slice($panierObj->getLignes($panierId), 0, 4);
// Extraction des 4 dernières notifications
$notifications = array_slice($notifObj->getByUtilisateur($_SESSION['user_id']), 0, 4);
// Compte des notifications non lues
$nbNonLu = $notifObj->getNombreNonLu($_SESSION['user_id']);
// Initialisation des compteurs
$nbLivraisonsEnCours = 0;
$totalDepenses = 0;
// Boucle sur toutes les commandes pour calculer les livraisons en cours et le total des dépenses
foreach ($allCommandes as $cmd) {
    // Si le statut indique que la commande est en cours de livraison, on incrémente
    if (in_array($cmd['statut'], ['En route','En livraison','En préparation'])) $nbLivraisonsEnCours++;
    // Addition du montant total de chaque commande
    $totalDepenses += $cmd['montant_total'];
}

// Calculs par période pour les KPI comparatifs
$debutMois = date('Y-m-01 00:00:00');
$finMois = date('Y-m-t 23:59:59');
$debutMoisPrec = date('Y-m-01 00:00:00', strtotime('-1 month'));
$finMoisPrec = date('Y-m-t 23:59:59', strtotime('-1 month'));

$userId = $_SESSION['user_id'];
$commandesMois = $commandeObj->getByUtilisateurByPeriode($userId, $debutMois, $finMois);
$nbCommandesMois = count($commandesMois);
$commandesMoisPrec = $commandeObj->getByUtilisateurByPeriode($userId, $debutMoisPrec, $finMoisPrec);
$nbCommandesMoisPrec = count($commandesMoisPrec);

$depensesMois = $commandeObj->getTotalDepensesByPeriode($userId, $debutMois, $finMois);
$depensesMoisPrec = $commandeObj->getTotalDepensesByPeriode($userId, $debutMoisPrec, $finMoisPrec);
$evolDepenses = $depensesMoisPrec > 0 ? round(($depensesMois - $depensesMoisPrec) / $depensesMoisPrec * 100) : ($depensesMois > 0 ? 100 : 0);

// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour la sidebar
$activePage = 'dashboard';
?>
<!-- Début du layout du tableau de bord -->
<div class="dashboard-layout">
<?php // Inclusion de la barre latérale utilisateur ?>
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php // Inclusion de la barre supérieure du tableau de bord ?>
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <!-- En-tête de page avec le titre -->
    <div class="dash-page-header">
        <div class="dash-page-label">Tableau de bord</div>
        <h1 class="dash-page-title">Vue d'ensemble</h1>
    </div>

    <!-- Grille des indicateurs clés de performance (KPI) -->
    <div class="kpi-grid kpi-grid-5">
        <div class="kpi-card kpi-card--navy"><div><div class="kpi-label">Commandes totales</div><div class="kpi-value"><?= $nbCommandes ?></div><div class="kpi-sub kpi-trend">+<?= $nbCommandesMois ?> ce mois</div></div><i class="fas fa-receipt kpi-icon"></i></div>
        <div class="kpi-card kpi-card--red"><div><div class="kpi-label">Dépenses totales</div><div class="kpi-value kpi-value--sm"><?= formatPrix($totalDepenses) ?></div><div class="kpi-sub kpi-trend"><?= $evolDepenses >= 0 ? '+' : '' ?><?= $evolDepenses ?>% vs mois dernier</div></div><i class="fas fa-dollar-sign kpi-icon"></i></div>
        <div class="kpi-card kpi-card--amber"><div><div class="kpi-label">Livraisons en cours</div><div class="kpi-value"><?= $nbLivraisonsEnCours ?></div><div class="kpi-sub text-muted">En cours de livraison</div></div><i class="fas fa-truck kpi-icon"></i></div>
        <div class="kpi-card kpi-card--green"><div><div class="kpi-label">Avis donnés</div><div class="kpi-value"><?= $nbAvis ?></div><div class="kpi-sub text-muted">Merci pour vos retours !</div></div><i class="fas fa-star kpi-icon"></i></div>
        <div class="kpi-card kpi-card--blue"><div><div class="kpi-label">Notifications</div><div class="kpi-value"><?= $nbNonLu ?></div><div class="kpi-sub text-muted">Non lues</div></div><i class="fas fa-bell kpi-icon"></i></div>
    </div>

    <!-- Section des commandes récentes -->
    <div class="dash-two-col" style="margin-bottom:18px;">
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Mes commandes récentes</span><a href="<?= BASE_URL ?>/user/mes_commandes.php" class="section-link" style="font-size:12px;">Voir tout <i class="fas fa-arrow-right"></i></a></div>
            <?php // Vérification si l'utilisateur a des commandes ?>
            <?php if (empty($dernieresCommandes)): ?>
            <!-- Message affiché si aucune commande n'existe -->
            <div style="padding:28px;text-align:center;color:var(--gray-400);"><p>Aucune commande.</p><a href="<?= BASE_URL ?>/pages/boutique.php" class="btn btn-dark btn-sm" style="margin-top:12px;">Découvrir</a></div>
            <?php else: ?>
            <!-- Tableau des 5 dernières commandes -->
            <table>
                <thead><tr><th>ID Commande</th><th>Montant</th><th>Statut</th><th>Livraison</th></tr></thead>
                <tbody>
                <?php // Boucle d'affichage des commandes récentes ?>
                <?php foreach ($dernieresCommandes as $cmd): ?>
                <tr>
                    <td><strong>#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                    <td><strong><?= formatPrix($cmd['montant_total']) ?></strong></td>
                    <td><?= getStatutBadge($cmd['statut']) ?></td>
                    <td><a href="<?= BASE_URL ?>/user/detail_commande.php?id=<?= $cmd['id'] ?>" style="color:var(--gray-400);font-size:11px;">—</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Lien vers la page complète des commandes -->
            <div style="padding:10px 16px;border-top:1px solid var(--gray-100);"><a href="<?= BASE_URL ?>/user/mes_commandes.php" style="font-size:12px;color:var(--gray-500);">Voir toutes mes commandes →</a></div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Grille à 4 colonnes : panier, paiements, avis, notifications -->
    <div class="dash-four-col">
        <!-- Carte : Mon panier -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Mon panier</span><span style="font-size:12px;color:var(--gray-400);"><?= $nbPanier ?> articles</span></div>
            <div style="padding:0 14px;">
                <?php // Vérification si le panier est vide ?>
                <?php if (empty($lignesPanier)): ?><p class="text-muted text-sm" style="padding:14px 0;">Panier vide.</p>
                <?php // Affichage des articles du panier ?>
                <?php else: foreach ($lignesPanier as $l): ?>
                <div class="panier-mini-item"><div class="panier-mini-img"><i class="fas fa-tshirt"></i></div><div style="flex:1;min-width:0;"><div class="text-sm font-semibold truncate"><?= securiser($l['nom']) ?></div><div class="text-xs text-muted"><?= $l['quantite'] ?> × <?= formatPrix($l['prix_unitaire']) ?></div></div></div>
                <?php endforeach; endif; ?>
            </div>
            <!-- Lien vers la page panier -->
            <div style="padding:10px 14px;border-top:1px solid var(--gray-100);"><a href="<?= BASE_URL ?>/pages/panier.php" style="font-size:12px;color:var(--gray-500);">Voir mon panier →</a></div>
        </div>

        <!-- Carte : Paiements récents -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Paiements récents</span><a href="<?= BASE_URL ?>/user/historique_paiement.php" class="section-link" style="font-size:12px;">Voir tout <i class="fas fa-arrow-right"></i></a></div>
            <div style="padding:0 14px;">
                <?php // Extraction des 3 premières commandes comme paiements récents ?>
                <?php $pmts = array_slice($allCommandes,0,3); if (empty($pmts)): ?><p class="text-muted text-sm" style="padding:14px 0;">Aucun paiement.</p>
                <?php // Affichage des paiements ?>
                <?php else: foreach ($pmts as $p): ?>
                <div class="flex justify-between items-center" style="padding:9px 0;border-bottom:1px solid var(--gray-100);"><div><div class="text-sm font-semibold">Paiement #P<?= $p['id'] ?></div><div class="text-xs text-muted"><?= formatPrix($p['montant_total']) ?></div></div><span class="badge badge-success">Réussi</span></div>
                <?php endforeach; endif; ?>
            </div>
            <!-- Lien vers l'historique des paiements -->
            <div style="padding:10px 14px;border-top:1px solid var(--gray-100);"><a href="<?= BASE_URL ?>/user/historique_paiement.php" style="font-size:12px;color:var(--gray-500);">Historique des paiements →</a></div>
        </div>

        <!-- Carte : Mes avis récents -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Mes avis récents</span><a href="<?= BASE_URL ?>/user/mes_avis.php" class="section-link" style="font-size:12px;">Voir tout <i class="fas fa-arrow-right"></i></a></div>
            <div style="padding:0 14px;">
                <?php // Extraction des 3 derniers avis ?>
                <?php $avr = array_slice($avisObj->getByUtilisateur($_SESSION['user_id']),0,3); if (empty($avr)): ?><p class="text-muted text-sm" style="padding:14px 0;">Aucun avis donné.</p>
                <?php // Affichage des avis ?>
                <?php else: foreach ($avr as $av): ?>
                <div style="padding:9px 0;border-bottom:1px solid var(--gray-100);"><div class="text-sm font-semibold truncate"><?= securiser($av['produit_nom'] ?? 'Produit') ?></div><div style="color:var(--warning);font-size:11px;"><?= str_repeat('★',$av['note']??5) ?></div><p class="text-xs text-muted truncate"><?= securiser(substr($av['commentaire']??'',0,60)) ?></p></div>
                <?php endforeach; endif; ?>
            </div>
            <!-- Lien vers la page des avis -->
            <div style="padding:10px 14px;border-top:1px solid var(--gray-100);"><a href="<?= BASE_URL ?>/user/mes_avis.php" style="font-size:12px;color:var(--gray-500);">Tous mes avis →</a></div>
        </div>

        <!-- Carte : Notifications récentes -->
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Notifications récentes</span><a href="<?= BASE_URL ?>/user/notifications.php" class="section-link" style="font-size:12px;">Voir tout <i class="fas fa-arrow-right"></i></a></div>
            <div>
                <?php // Vérification si l'utilisateur a des notifications ?>
                <?php if (empty($notifications)): ?><p class="text-muted text-sm" style="padding:14px 16px;">Aucune notification.</p>
                <?php // Affichage des notifications ?>
                <?php else: foreach ($notifications as $n): ?>
                <div class="notif-item <?= !$n['lu']?'unread':'' ?>"><div class="notif-icon"><i class="fas fa-bell"></i></div><div class="notif-content"><div class="notif-title text-sm"><?= securiser(substr($n['message'],0,50)) ?></div><div class="notif-time"><?= date('d/m/Y',strtotime($n['date_creation'])) ?></div></div><?php if (!$n['lu']): ?><div class="notif-dot"></div><?php endif; ?></div>
                <?php endforeach; endif; ?>
            </div>
            <!-- Lien vers toutes les notifications -->
            <div style="padding:10px 14px;border-top:1px solid var(--gray-100);"><a href="<?= BASE_URL ?>/user/notifications.php" style="font-size:12px;color:var(--gray-500);">Toutes les notifications →</a></div>
        </div>
    </div>

    <!-- Section Accès rapide -->
    <div class="table-card" style="margin-top:0;">
        <div class="table-card-header"><span class="table-card-title">Accès rapide</span></div>
        <div class="quick-access">
            <a href="<?= BASE_URL ?>/user/profil.php" class="quick-access-item"><i class="fas fa-user"></i><span>Mon profil</span></a>
            <a href="<?= BASE_URL ?>/pages/panier.php" class="quick-access-item"><i class="fas fa-shopping-cart"></i><span>Mon panier</span><small><?= $nbPanier ?></small></a>
            <a href="<?= BASE_URL ?>/user/historique_paiement.php" class="quick-access-item"><i class="fas fa-credit-card"></i><span>Paiement</span></a>
            <a href="<?= BASE_URL ?>/user/mes_commandes.php" class="quick-access-item"><i class="fas fa-receipt"></i><span>Mes commandes</span></a>
            <a href="<?= BASE_URL ?>/user/suivi_livraison.php" class="quick-access-item"><i class="fas fa-truck"></i><span>Suivi livraison</span></a>
            <a href="<?= BASE_URL ?>/user/mes_avis.php" class="quick-access-item"><i class="fas fa-star"></i><span>Mes avis</span></a>
            <a href="<?= BASE_URL ?>/user/notifications.php" class="quick-access-item"><i class="fas fa-bell"></i><span>Notifications</span><small><?= $nbNonLu ?></small></a>
        </div>
    </div>
</div>
<!-- Pied de page du dashboard -->
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
</body></html>
