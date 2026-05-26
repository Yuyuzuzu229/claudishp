<?php
// includes/admin_sidebar.php - Barre latérale de l'interface d'administration

// Inclusion des classes nécessaires pour récupérer les statistiques
require_once __DIR__ . '/../classes/Utilisateur.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Avis.php';

// Définition de la page admin courante, avec une valeur par défaut vide
$currentAdminPage = $adminPage ?? '';
// Récupération de la première lettre du prénom de l'utilisateur pour l'avatar
$userInitialAdmin = strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1));
// Sécurisation et concaténation du prénom et du nom de l'utilisateur
$adminName = securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
// Sécurisation de l'email de l'utilisateur, avec valeur par défaut
$adminEmail = securiser($_SESSION['user_email'] ?? 'admin@claudishop.bj');

// Instanciation des classes pour récupérer les données statistiques
$_u = new Utilisateur(); $_nbUsers = $_u->getNombre();
$_p = new Produit(); $_nbProduits = $_p->getNombre();
$_c = new Commande(); $_nbCommandes = $_c->getNombre();
$_a = new Avis(); $_nbAvisModeration = $_a->getNombre() - count($_a->getPublies(999999));
?>
<!-- Overlay de la barre latérale pour mobile : fermeture au clic -->
<div class="dash-sidebar-overlay" id="dashSidebarOverlay" onclick="document.getElementById('dashSidebar').classList.remove('open');this.classList.remove('open');document.body.style.overflow='';"></div>
<!-- Barre latérale (sidebar) de l'administration -->
<aside class="dash-sidebar" id="dashSidebar" style="background:#0a0a0a;">
    <!-- Logo de la marque dans la sidebar -->
    <div class="dash-sidebar-logo">
        <a href="<?= BASE_URL ?>/index.php" style="text-decoration:none;color:inherit;">
            <div class="dash-logo-text">CLAUDI<span style="font-weight:400;">SHOP</span></div>
        </a>
        <div class="dash-logo-label">Espace Admin</div>
    </div>
    <!-- Informations de l'utilisateur connecté dans la sidebar -->
    <div class="dash-sidebar-user">
        <div class="dash-user-avatar"><?= $userInitialAdmin ?></div>
        <div>
            <div class="dash-user-name"><?= $adminName ?></div>
            <div class="dash-user-email"><?= $adminEmail ?></div>
        </div>
    </div>
    <!-- Navigation principale de la sidebar -->
    <nav class="dash-nav">
        <!-- Lien vers le tableau de bord -->
        <a href="<?= BASE_URL ?>/admin/index.php" class="dash-nav-item <?= $currentAdminPage==='dashboard'?'active':'' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <div class="dash-nav-label">Gestion</div>
        <!-- Lien vers la gestion des utilisateurs avec badge du nombre -->
        <a href="<?= BASE_URL ?>/admin/utilisateurs.php" class="dash-nav-item <?= $currentAdminPage==='utilisateurs'?'active':'' ?>">
            <i class="fas fa-users"></i> Utilisateurs
            <?php if ($_nbUsers > 0): ?><span class="dash-nav-badge"><?= $_nbUsers ?></span><?php endif; ?>
        </a>
        <!-- Lien vers la gestion des produits avec badge du nombre -->
        <a href="<?= BASE_URL ?>/admin/produits.php" class="dash-nav-item <?= $currentAdminPage==='produits'?'active':'' ?>">
            <i class="fas fa-box"></i> Produits
            <?php if ($_nbProduits > 0): ?><span class="dash-nav-badge"><?= $_nbProduits ?></span><?php endif; ?>
        </a>
        <!-- Lien vers la gestion des catégories -->
        <a href="<?= BASE_URL ?>/admin/categories.php" class="dash-nav-item <?= $currentAdminPage==='categories'?'active':'' ?>">
            <i class="fas fa-tag"></i> Catégories
        </a>
        <!-- Lien vers la gestion des commandes avec badge du nombre en cours -->
        <a href="<?= BASE_URL ?>/admin/commandes.php" class="dash-nav-item <?= $currentAdminPage==='commandes'?'active':'' ?>">
            <i class="fas fa-receipt"></i> Commandes
            <?php if ($_nbCommandes > 0): ?><span class="dash-nav-badge"><?= $_nbCommandes ?></span><?php endif; ?>
        </a>
        <div class="dash-nav-label">Logistique</div>
        <!-- Lien vers la gestion des livraisons -->
        <a href="<?= BASE_URL ?>/admin/livraisons.php" class="dash-nav-item <?= $currentAdminPage==='livraisons'?'active':'' ?>">
            <i class="fas fa-truck"></i> Livraisons
        </a>
        <!-- Lien vers la gestion des zones de livraison -->
        <a href="<?= BASE_URL ?>/admin/zones.php" class="dash-nav-item <?= $currentAdminPage==='zones'?'active':'' ?>">
            <i class="fas fa-map-marker-alt"></i> Zones de livraison
        </a>
        <!-- Lien vers la gestion des livreurs -->
        <a href="<?= BASE_URL ?>/admin/livreurs.php" class="dash-nav-item <?= $currentAdminPage==='livreurs'?'active':'' ?>">
            <i class="fas fa-motorcycle"></i> Livreurs
        </a>
        <div class="dash-nav-label">Finance &amp; Comm.</div>
        <!-- Lien vers la gestion des paiements -->
        <a href="<?= BASE_URL ?>/admin/paiements.php" class="dash-nav-item <?= $currentAdminPage==='paiements'?'active':'' ?>">
            <i class="fas fa-credit-card"></i> Paiements
        </a>
        <!-- Lien vers la gestion des avis clients avec badge des avis en modération -->
        <a href="<?= BASE_URL ?>/admin/avis.php" class="dash-nav-item <?= $currentAdminPage==='avis'?'active':'' ?>">
            <i class="fas fa-star"></i> Avis clients
            <?php if ($_nbAvisModeration > 0): ?><span class="dash-nav-badge"><?= $_nbAvisModeration ?></span><?php endif; ?>
        </a>
        <!-- Lien vers la gestion des notifications -->
        <a href="<?= BASE_URL ?>/admin/notifications.php" class="dash-nav-item <?= $currentAdminPage==='notifications'?'active':'' ?>">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <div class="dash-nav-label">Configuration</div>
        <!-- Lien vers la localisation de la boutique -->
        <a href="<?= BASE_URL ?>/admin/boutique.php" class="dash-nav-item <?= $currentAdminPage==='boutique'?'active':'' ?>">
            <i class="fas fa-store"></i> Localisation boutique
        </a>
        <!-- Lien vers les collections de la bannière accueil -->
        <a href="<?= BASE_URL ?>/admin/collections.php" class="dash-nav-item <?= $currentAdminPage==='collections'?'active':'' ?>">
            <i class="fas fa-layer-group"></i> Collections accueil
        </a>
    </nav>
    <!-- Liens de navigation en bas de la sidebar -->
    <div class="dash-nav-logout">
        <!-- Mon profil -->
        <a href="<?= BASE_URL ?>/user/profil.php">
            <i class="fas fa-user"></i> Mon profil
        </a>
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
