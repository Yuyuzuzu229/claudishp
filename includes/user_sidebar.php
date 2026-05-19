<?php
// includes/user_sidebar.php
// Usage: require_once with $activePage set to current page name
$currentActivePage = $activePage ?? '';
$__nbNonLu = 0;
$__nbPanier = 0;
if (isLoggedIn()) {
    if (class_exists('Notification')) { $__n = new Notification(); $__nbNonLu = $__n->getNombreNonLu($_SESSION['user_id']); }
    if (class_exists('Panier')) { $__p = new Panier(); $__pid = $__p->getPanierActif($_SESSION['user_id']); $__nbPanier = $__p->getNombreArticles($__pid); }
}
$userInitial = strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1));
$userName = securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$userEmail = securiser($_SESSION['user_email'] ?? '');
?>
<div class="dash-sidebar-overlay" id="dashSidebarOverlay"></div>
<aside class="dash-sidebar" id="dashSidebar" style="background:#0a0a0a;">
    <div class="dash-sidebar-logo">
        <a href="<?= BASE_URL ?>/index.php" style="text-decoration:none;color:inherit;">
            <div class="dash-logo-text">CLAUDI<span style="font-weight:400;">SHOP</span></div>
        </a>
        <div class="dash-logo-label">Espace Client</div>
    </div>
    <div class="dash-sidebar-user">
        <div class="dash-user-avatar"><?= $userInitial ?></div>
        <div>
            <div class="dash-user-name"><?= $userName ?></div>
            <div class="dash-user-email"><?= $userEmail ?></div>
        </div>
    </div>
    <nav class="dash-nav">
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="dash-nav-item <?= $currentActivePage==='dashboard'?'active':'' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <div class="dash-nav-label">Mon compte</div>
        <a href="<?= BASE_URL ?>/user/profil.php" class="dash-nav-item <?= $currentActivePage==='profil'?'active':'' ?>">
            <i class="fas fa-user"></i> Mon profil
        </a>
        <a href="<?= BASE_URL ?>/pages/panier.php" class="dash-nav-item <?= $currentActivePage==='panier'?'active':'' ?>">
            <i class="fas fa-shopping-cart"></i> Mon panier
            <?php if ($__nbPanier > 0): ?><span class="dash-nav-badge"><?= $__nbPanier ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/user/historique_paiement.php" class="dash-nav-item <?= $currentActivePage==='paiement'?'active':'' ?>">
            <i class="fas fa-credit-card"></i> Paiement
        </a>

        <div class="dash-nav-label">Mes commandes</div>
        <a href="<?= BASE_URL ?>/user/mes_commandes.php" class="dash-nav-item <?= $currentActivePage==='commandes'?'active':'' ?>">
            <i class="fas fa-receipt"></i> Mes commandes
        </a>
        <a href="<?= BASE_URL ?>/user/suivi_livraison.php" class="dash-nav-item <?= $currentActivePage==='suivi'?'active':'' ?>">
            <i class="fas fa-truck"></i> Suivi livraison
        </a>
        <div class="dash-nav-label">Avis &amp; Communication</div>
        <a href="<?= BASE_URL ?>/user/mes_avis.php" class="dash-nav-item <?= $currentActivePage==='avis'?'active':'' ?>">
            <i class="fas fa-star"></i> Mes avis
        </a>
        <a href="<?= BASE_URL ?>/user/notifications.php" class="dash-nav-item <?= $currentActivePage==='notifications'?'active':'' ?>">
            <i class="fas fa-bell"></i> Mes notifications
            <?php if ($__nbNonLu > 0): ?><span class="dash-nav-badge"><?= $__nbNonLu ?></span><?php endif; ?>
        </a>
    </nav>
    <div class="dash-nav-logout">
        <a href="<?= BASE_URL ?>/index.php">
            <i class="fas fa-store"></i> Retourner au site
        </a>
        <a href="<?= BASE_URL ?>/actions/deconnexion.php">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>
