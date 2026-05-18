<?php
// includes/admin_sidebar.php
require_once __DIR__ . '/../classes/Utilisateur.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Avis.php';

$currentAdminPage = $adminPage ?? '';
$userInitialAdmin = strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1));
$adminName = securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$adminEmail = securiser($_SESSION['user_email'] ?? 'admin@claudishop.bj');

$_u = new Utilisateur(); $_nbUsers = $_u->getNombre();
$_p = new Produit(); $_nbProduits = $_p->getNombre();
$_c = new Commande(); $_nbCmdEnCours = $_c->getEnCours();
$_a = new Avis(); $_nbAvisModeration = $_a->getNombre() - count($_a->getPublies(999999));
?>
<aside class="dash-sidebar" style="background:#0a0a0a;">
    <div class="dash-sidebar-logo">
        <a href="<?= BASE_URL ?>/index.php" style="text-decoration:none;color:inherit;">
            <div class="dash-logo-text">CLAUDI<span style="font-weight:400;">SHOP</span></div>
        </a>
        <div class="dash-logo-label">Espace Admin</div>
    </div>
    <div class="dash-sidebar-user">
        <div class="dash-user-avatar"><?= $userInitialAdmin ?></div>
        <div>
            <div class="dash-user-name"><?= $adminName ?></div>
            <div class="dash-user-email"><?= $adminEmail ?></div>
        </div>
    </div>
    <nav class="dash-nav">
        <a href="<?= BASE_URL ?>/admin/index.php" class="dash-nav-item <?= $currentAdminPage==='dashboard'?'active':'' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <div class="dash-nav-label">Gestion</div>
        <a href="<?= BASE_URL ?>/admin/utilisateurs.php" class="dash-nav-item <?= $currentAdminPage==='utilisateurs'?'active':'' ?>">
            <i class="fas fa-users"></i> Utilisateurs
            <?php if ($_nbUsers > 0): ?><span class="dash-nav-badge"><?= $_nbUsers ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/produits.php" class="dash-nav-item <?= $currentAdminPage==='produits'?'active':'' ?>">
            <i class="fas fa-box"></i> Produits
            <?php if ($_nbProduits > 0): ?><span class="dash-nav-badge"><?= $_nbProduits ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="dash-nav-item <?= $currentAdminPage==='categories'?'active':'' ?>">
            <i class="fas fa-tag"></i> Catégories
        </a>
        <a href="<?= BASE_URL ?>/admin/commandes.php" class="dash-nav-item <?= $currentAdminPage==='commandes'?'active':'' ?>">
            <i class="fas fa-receipt"></i> Commandes
            <?php if ($_nbCmdEnCours > 0): ?><span class="dash-nav-badge"><?= $_nbCmdEnCours ?></span><?php endif; ?>
        </a>
        <div class="dash-nav-label">Logistique</div>
        <a href="<?= BASE_URL ?>/admin/livraisons.php" class="dash-nav-item <?= $currentAdminPage==='livraisons'?'active':'' ?>">
            <i class="fas fa-truck"></i> Livraisons
        </a>
        <a href="<?= BASE_URL ?>/admin/zones.php" class="dash-nav-item <?= $currentAdminPage==='zones'?'active':'' ?>">
            <i class="fas fa-map-marker-alt"></i> Zones de livraison
        </a>
        <a href="<?= BASE_URL ?>/admin/livreurs.php" class="dash-nav-item <?= $currentAdminPage==='livreurs'?'active':'' ?>">
            <i class="fas fa-motorcycle"></i> Livreurs
        </a>
        <div class="dash-nav-label">Finance &amp; Comm.</div>
        <a href="<?= BASE_URL ?>/admin/paiements.php" class="dash-nav-item <?= $currentAdminPage==='paiements'?'active':'' ?>">
            <i class="fas fa-credit-card"></i> Paiements
        </a>
        <a href="<?= BASE_URL ?>/admin/avis.php" class="dash-nav-item <?= $currentAdminPage==='avis'?'active':'' ?>">
            <i class="fas fa-star"></i> Avis clients
            <?php if ($_nbAvisModeration > 0): ?><span class="dash-nav-badge"><?= $_nbAvisModeration ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/notifications.php" class="dash-nav-item <?= $currentAdminPage==='notifications'?'active':'' ?>">
            <i class="fas fa-bell"></i> Notifications
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
