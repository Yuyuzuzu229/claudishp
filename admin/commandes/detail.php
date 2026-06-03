<?php
// Inclusion du fichier de configuration principal (remonte de 2 niveaux jusqu'à la racine)
require_once __DIR__ . '/../../config/config.php';
// Inclusion de la classe Commande
require_once __DIR__ . '/../../classes/Commande.php';
// Inclusion de la classe Livraison
require_once __DIR__ . '/../../classes/Livraison.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Récupération et conversion de l'identifiant de la commande depuis l'URL
$id = intval($_GET['id'] ?? 0);
// Instanciation de l'objet Commande
$commandeObj = new Commande();
// Récupération des données de la commande par son identifiant
$cmd = $commandeObj->getById($id);

// Instanciation de l'objet Livraison
$livraisonObj = new Livraison();
// Récupération des informations de suivi de livraison pour cette commande
$suivi = $livraisonObj->getByCommande($id);

// Si la commande n'existe pas, enregistrement d'un message d'erreur et redirection
if (!$cmd) { $_SESSION['error'] = 'Commande introuvable.'; redirect(BASE_URL . '/admin/commandes.php'); }

// Définition du titre de la page avec l'identifiant formaté de la commande
$pageTitle = 'Commande #' . str_pad($id, 4, '0', STR_PAD_LEFT);
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../../includes/header.php';
// Définition de la page active pour le menu d'administration
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

    <!-- Affichage des messages d'erreur ou de succès éventuels -->
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <!-- Affichage en deux colonnes des informations de la commande et du client -->
    <div class="dash-two-col" style="margin-bottom:18px;">
        <div class="table-card">
            <div class="table-card-header"><span class="table-card-title">Informations commande</span></div>
            <div style="padding:16px;">
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">ID</span><span class="text-sm font-semibold">#<?= str_pad($cmd['id'],4,'0',STR_PAD_LEFT) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Statut</span><span><?= getStatutBadge($cmd['statut']) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Montant total</span><span class="text-sm font-semibold"><?= formatPrix($cmd['montant_total']) ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Mode retrait</span><span class="text-sm"><?= securiser($cmd['mode_retrait'] ?? 'Livraison') ?></span></div>
                <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Mode paiement</span><span class="text-sm"><?= renderModePaiement($cmd['mode_paiement'] ?? '') ?></span></div>
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

    <?php if ($cmd['mode_retrait'] === 'livraison' && $suivi): ?>
    <div class="table-card" style="margin-bottom:18px;">
        <div class="table-card-header"><span class="table-card-title">Livraison</span></div>
        <div style="padding:16px;">
            <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Livreur</span><span class="text-sm font-semibold"><?= securiser($suivi['livreur_nom'] ?? 'Non assigné') ?></span></div>
            <?php if (!empty($suivi['livreur_telephone'])): ?>
            <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Téléphone</span><span class="text-sm"><?= securiser($suivi['livreur_telephone']) ?></span></div>
            <?php endif; ?>
            <div class="flex justify-between" style="padding:8px 0;border-bottom:1px solid var(--gray-100);"><span class="text-sm text-muted">Statut livraison</span><span><?= getStatutBadge($suivi['statut'] ?? 'En attente') ?></span></div>
            <div class="flex justify-between" style="padding:8px 0;"><span class="text-sm text-muted">Adresse</span><span class="text-sm" style="text-align:right;"><?= securiser($suivi['adresse'] ?? '—') ?></span></div>
        </div>
    </div>
    <?php endif; ?>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>