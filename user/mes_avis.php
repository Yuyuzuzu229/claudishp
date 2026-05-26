<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Avis pour gérer les avis clients
require_once __DIR__ . '/../classes/Avis.php';
// Inclusion de la classe Panier pour gérer le panier
require_once __DIR__ . '/../classes/Panier.php';
// Inclusion de la classe Notification pour gérer les notifications
require_once __DIR__ . '/../classes/Notification.php';

// Vérification : rediriger vers la connexion si l'utilisateur n'est pas connecté
if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Mes avis';
// Instanciation de l'objet Avis
$avisObj = new Avis();
// Récupération de tous les avis de l'utilisateur connecté
$avis = $avisObj->getByUtilisateur($_SESSION['user_id']);

// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour la sidebar
$activePage = 'avis';
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
        <div class="dash-page-label">Avis &amp; Communication</div>
        <h1 class="dash-page-title">Mes avis</h1>
        <p class="dash-page-sub">Retrouvez ici tous les avis que vous avez laissés.</p>
    </div>

    <!-- Barre d'actions avec bouton Nouvel avis -->
    <div class="dash-page-actions" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div></div>
        <a href="<?= BASE_URL ?>/user/nouvel_avis.php" class="btn btn-dark" style="padding:8px 18px;font-size:13px;">
            <i class="fas fa-plus" style="margin-right:6px;"></i>Nouvel avis
        </a>
    </div>
    <!-- Carte contenant la liste des avis -->
    <div class="table-card">
        <div class="table-card-header"><span class="table-card-title">Tous vos avis (<?= count($avis) ?>)</span></div>
        <?php // Vérification si l'utilisateur a des avis ?>
        <?php if (empty($avis)): ?>
        <!-- Message si aucun avis -->
        <div style="padding:48px;text-align:center;">
            <i class="fas fa-star" style="font-size:40px;color:var(--gray-200);margin-bottom:16px;display:block;"></i>
            <p class="text-muted">Vous n'avez pas encore donné d'avis.</p>
            <a href="<?= BASE_URL ?>/user/nouvel_avis.php" class="btn btn-dark" style="margin-top:16px;">Évaluer un produit acheté</a>
        </div>
        <?php else: ?>
        <!-- Tableau des avis -->
        <table>
            <thead><tr><th>Produit</th><th>Note</th><th>Avis</th><th>Date</th><th>Statut</th></tr></thead>
            <tbody>
            <?php // Boucle d'affichage des avis ?>
            <?php foreach ($avis as $av): ?>
            <tr>
                <td>
                    <div class="flex gap-2 items-center">
                        <div class="panier-table-img" style="width:40px;height:40px;font-size:14px;"><i class="fas fa-tshirt"></i></div>
                        <span class="text-sm font-semibold"><?= securiser($av['produit_nom'] ?? 'Produit') ?></span>
                    </div>
                </td>
                <!-- Affichage des étoiles de notation -->
                <td><span style="color:var(--warning);"><?= str_repeat('★', $av['note'] ?? 5) ?><?= str_repeat('☆', 5 - ($av['note'] ?? 5)) ?></span></td>
                <!-- Aperçu du commentaire tronqué à 80 caractères -->
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
<!-- Pied de page -->
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
