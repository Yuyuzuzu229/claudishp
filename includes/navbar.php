<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$panierCount = 0;
$userInitialNav = 'U';
if (isLoggedIn()) {
    if (class_exists('Panier')) {
        $__panier = new Panier();
        $__panierId = $__panier->getPanierActif($_SESSION['user_id']);
        $panierCount = $__panier->getNombreArticles($__panierId);
    }
    $userInitialNav = strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1));
} else {
    if (class_exists('Panier')) {
        $__panier = new Panier();
        $panierCount = $__panier->guestGetNombreArticles();
    }
}
$nbNotifUnread = 0;
if (isLoggedIn() && class_exists('Notification')) {
    $__notif = new Notification();
    $nbNotifUnread = $__notif->getNombreNonLu($_SESSION['user_id']);
}
if (!isset($categorie) || !($categorie instanceof Categorie)) {
    require_once __DIR__ . '/../classes/Categorie.php';
    $categorie = new Categorie();
}
$categoriesNav = $categorie->getForNav();
?>
<div class="announcement-bar">
    Livraison gratuite dès 500&nbsp;000 FCFA d'achat &bull; Paiement <strong>MTN Momo</strong> &amp; <strong>Moov Money</strong>
</div>
<header class="top-header">
    <div class="header-inner">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
            <span class="logo-claudi">CLAUDI</span>
            <span class="logo-shop">SHOP</span>
        </a>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav">
            <?php foreach ($categoriesNav as $cat): ?>
            <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=<?= $cat['id'] ?>" class="<?= ($currentPage==='boutique.php' && isset($_GET['categorie']) && $_GET['categorie']==$cat['id'])?'active':'' ?>"><?= securiser($cat['nom']) ?></a>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/pages/boutique.php" class="<?= ($currentPage==='boutique.php' && !isset($_GET['categorie']))?'active':'' ?>">Nouveautés</a>
            <a href="<?= BASE_URL ?>/pages/boutique.php?soldes=1" class="nav-soldes">Soldes</a>
        </nav>
        <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
        <div class="mobile-nav" id="mobileNav">
            <div class="mobile-nav-header">
                <span class="logo-claudi">CLAUDI<span class="logo-shop-mobile">SHOP</span></span>
                <button class="mobile-nav-close" id="mobileNavClose" aria-label="Fermer">&times;</button>
            </div>
            <div class="mobile-nav-links">
                <?php foreach ($categoriesNav as $cat): ?>
                <a href="<?= BASE_URL ?>/pages/boutique.php?categorie=<?= $cat['id'] ?>"><?= securiser($cat['nom']) ?></a>
                <?php endforeach; ?>
                <a href="<?= BASE_URL ?>/pages/boutique.php">Nouveautés</a>
                <a href="<?= BASE_URL ?>/pages/boutique.php?soldes=1" class="nav-soldes">Soldes</a>
            </div>
            <div class="mobile-nav-footer">
                <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/user/dashboard.php" class="mobile-nav-user">
                    <i class="fas fa-user"></i> Mon compte
                </a>
                <a href="<?= BASE_URL ?>/actions/deconnexion.php" class="mobile-nav-logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/connexion.php" class="mobile-nav-login">
                    <i class="fas fa-sign-in-alt"></i> Connexion
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-search">
            <form method="GET" action="<?= BASE_URL ?>/pages/boutique.php" style="display:flex;width:100%;">
                <input type="text" name="recherche" placeholder="Rechercher..." value="<?= isset($_GET['recherche']) ? securiser($_GET['recherche']) : '' ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="header-actions">
            <?php if (isLoggedIn()): ?>
            <div class="header-action-item" id="nav-avatar-trigger" style="cursor:pointer;position:relative;">
                <div style="width:30px;height:30px;border-radius:50%;background:var(--dark);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;"><?= $userInitialNav ?></div>
                <div class="nav-avatar-dropdown" id="nav-avatar-menu" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:white;border:1px solid var(--gray-200);border-radius:6px;box-shadow:0 10px 25px rgba(0,0,0,0.1);min-width:200px;z-index:1000;overflow:hidden;">
                    <div style="padding:12px 14px;border-bottom:1px solid var(--gray-100);">
                        <div class="text-sm font-semibold"><?= securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) ?></div>
                        <?php
                        $_navEmail = $_SESSION['user_email'] ?? '';
                        $_navPhone = $_SESSION['user_telephone'] ?? '';
                        $_navPhoneOnly = (strpos($_navEmail, 'tel-') === 0) && (substr($_navEmail, -17) === '@claudishop.local');
                        ?>
                        <div class="text-xs text-muted"><?= securiser($_navPhoneOnly ? $_navPhone : $_navEmail) ?></div>
                    </div>
                    <?php if (isAdmin()): ?>
                    <a href="<?= BASE_URL ?>/admin/index.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--dark);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                        <i class="fas fa-th-large" style="width:16px;text-align:center;"></i> Administration
                    </a>
                    <a href="<?= BASE_URL ?>/user/profil.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--dark);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                        <i class="fas fa-user" style="width:16px;text-align:center;"></i> Mon profil
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/user/dashboard.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--dark);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                        <i class="fas fa-th-large" style="width:16px;text-align:center;"></i> Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>/user/profil.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--dark);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                        <i class="fas fa-user" style="width:16px;text-align:center;"></i> Mon profil
                    </a>
                    <a href="<?= BASE_URL ?>/user/mes_commandes.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--dark);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                        <i class="fas fa-receipt" style="width:16px;text-align:center;"></i> Mes commandes
                    </a>
                    <?php endif; ?>
                    <div style="border-top:1px solid var(--gray-100);"></div>
                    <a href="<?= BASE_URL ?>/actions/deconnexion.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--danger, #DC2626);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                        <i class="fas fa-sign-out-alt" style="width:16px;text-align:center;"></i> Déconnexion
                    </a>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/connexion.php" class="header-action-item">
                <i class="fas fa-user"></i>
                <span>Connexion</span>
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/pages/panier.php" class="header-action-item">
                <i class="fas fa-shopping-bag"></i>
                <span>Panier</span>
                <span class="cart-count" id="cart-count" data-count="<?= $panierCount ?>"><?= $panierCount ?></span>
            </a>
        </div>
    </div>
</header>
