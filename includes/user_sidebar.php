<?php
// includes/user_sidebar.php - Barre latérale de l'espace utilisateur
// Usage : require_once with $activePage set to current page name

// Récupération de la page active avec valeur par défaut vide
$currentActivePage = $activePage ?? '';
// Initialisation des compteurs de notifications et d'articles du panier
$__nbNonLu = 0;
$__nbPanier = 0;
// Vérification si l'utilisateur est connecté
if (isLoggedIn()) {
    // Récupération du nombre de notifications non lues si la classe existe
    if (class_exists('Notification')) { $__n = new Notification(); $__nbNonLu = $__n->getNombreNonLu($_SESSION['user_id']); }
    // Récupération du nombre d'articles dans le panier actif si la classe existe
    if (class_exists('Panier')) { $__p = new Panier(); $__pid = $__p->getPanierActif($_SESSION['user_id']); $__nbPanier = $__p->getNombreArticles($__pid); }
}
// Récupération de l'initiale du prénom pour l'avatar
$userInitial = strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1));
// Sécurisation du nom complet de l'utilisateur
$userName = securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
// Sécurisation du contact (email si vrai email, téléphone si phone-only)
$userEmailSidebar = $_SESSION['user_email'] ?? '';
$userPhoneSidebar = $_SESSION['user_telephone'] ?? '';
$isPhoneOnlySidebar = (strpos($userEmailSidebar, 'tel-') === 0) && (substr($userEmailSidebar, -17) === '@claudishop.local');
$userContact = $isPhoneOnlySidebar ? securiser($userPhoneSidebar) : securiser($userEmailSidebar);
?>
<!-- Overlay pour la barre latérale sur mobile -->
<div class="dash-sidebar-overlay" id="dashSidebarOverlay"></div>
<!-- Barre latérale du tableau de bord utilisateur -->
<aside class="dash-sidebar" id="dashSidebar" style="background:#0a0a0a;">
    <!-- Logo de la marque -->
    <div class="dash-sidebar-logo">
        <a href="<?= BASE_URL ?>/index.php" style="text-decoration:none;color:inherit;">
            <div class="dash-logo-text">CLAUDI<span style="font-weight:400;">SHOP</span></div>
        </a>
        <div class="dash-logo-label">Espace Client</div>
    </div>
    <!-- Informations de l'utilisateur connecté -->
    <div class="dash-sidebar-user">
        <!-- Avatar avec l'initiale de l'utilisateur -->
        <div class="dash-user-avatar"><?= $userInitial ?></div>
        <div>
            <div class="dash-user-name"><?= $userName ?></div>
            <div class="dash-user-email"><?= $userContact ?></div>
        </div>
    </div>
    <!-- Navigation de la barre latérale -->
    <nav class="dash-nav">
        <!-- Lien vers le tableau de bord -->
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="dash-nav-item <?= $currentActivePage==='dashboard'?'active':'' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <div class="dash-nav-label">Mon compte</div>
        <!-- Lien vers le profil -->
        <a href="<?= BASE_URL ?>/user/profil.php" class="dash-nav-item <?= $currentActivePage==='profil'?'active':'' ?>">
            <i class="fas fa-user"></i> Mon profil
        </a>
        <!-- Lien vers le panier avec badge du nombre d'articles -->
        <a href="<?= BASE_URL ?>/pages/panier.php" class="dash-nav-item <?= $currentActivePage==='panier'?'active':'' ?>">
            <i class="fas fa-shopping-cart"></i> Mon panier
            <?php if ($__nbPanier > 0): ?><span class="dash-nav-badge"><?= $__nbPanier ?></span><?php endif; ?>
        </a>
        <!-- Lien vers l'historique des paiements -->
        <a href="<?= BASE_URL ?>/user/historique_paiement.php" class="dash-nav-item <?= $currentActivePage==='paiement'?'active':'' ?>">
            <i class="fas fa-credit-card"></i> Paiement
        </a>

        <div class="dash-nav-label">Mes commandes</div>
        <!-- Lien vers les commandes -->
        <a href="<?= BASE_URL ?>/user/mes_commandes.php" class="dash-nav-item <?= $currentActivePage==='commandes'?'active':'' ?>">
            <i class="fas fa-receipt"></i> Mes commandes
        </a>
        <!-- Lien vers le suivi de livraison -->
        <a href="<?= BASE_URL ?>/user/suivi_livraison.php" class="dash-nav-item <?= $currentActivePage==='suivi'?'active':'' ?>">
            <i class="fas fa-truck"></i> Suivi livraison
        </a>
        <div class="dash-nav-label">Avis &amp; Communication</div>
        <!-- Lien vers les avis -->
        <a href="<?= BASE_URL ?>/user/mes_avis.php" class="dash-nav-item <?= $currentActivePage==='avis'?'active':'' ?>">
            <i class="fas fa-star"></i> Mes avis
        </a>
        <!-- Lien vers les notifications avec badge du nombre non lu -->
        <a href="<?= BASE_URL ?>/user/notifications.php" class="dash-nav-item <?= $currentActivePage==='notifications'?'active':'' ?>">
            <i class="fas fa-bell"></i> Mes notifications
            <?php if ($__nbNonLu > 0): ?><span class="dash-nav-badge"><?= $__nbNonLu ?></span><?php endif; ?>
        </a>
    </nav>
    <!-- Liens de navigation en bas de la sidebar -->
    <div class="dash-nav-logout">
        <!-- Retour au site public -->
        <a href="<?= BASE_URL ?>/index.php">
            <i class="fas fa-store"></i> Retourner au site
        </a>
        <!-- Déconnexion -->
        <a href="<?= BASE_URL ?>/actions/deconnexion.php">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>
