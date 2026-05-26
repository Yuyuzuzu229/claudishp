<!DOCTYPE html>
<!-- Déclaration du type de document HTML5 -->
<html lang="fr">
<!-- Début du document HTML avec la langue française -->
<head>
    <!-- Métadonnées : encodage UTF-8 et viewport responsive -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titre dynamique de la page : préfixe Admin + titre de la page + nom du site -->
    <title><?= isset($pageTitle) ? 'Admin - ' . securiser($pageTitle) . ' | ' : '' ?>CLAUDISHOP</title>
    <!-- Chargement du CSS Bootstrap via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chargement des icônes Bootstrap via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <!-- Chargement du fichier CSS principal avec cache-busting -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=3">
    <!-- Définition du favicon au format SVG -->
    <link rel="icon" type="image/svg+xml" href="<?= ASSETS_URL ?>/images/brand/favicon.svg">
    <!-- Chargement différé du fichier JavaScript principal -->
    <script defer src="<?= ASSETS_URL ?>/js/script.js"></script>
    <!-- Script en ligne pour la fermeture de la sidebar avec la touche Échap -->
    <script>document.addEventListener('keydown',function(e){if(e.key==='Escape'){var s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');if(s&&s.classList.contains('open')){s.classList.remove('open');if(o)o.classList.remove('open');document.body.style.overflow='';}}});
    // Initialisation au chargement du DOM pour le toggle de la sidebar
    document.addEventListener('DOMContentLoaded',function(){var t=document.getElementById('dashSidebarToggle'),s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');function cs(){if(s)s.classList.remove('open');if(o)o.classList.remove('open');document.body.style.overflow='';}if(o)o.addEventListener('click',cs);document.addEventListener('keydown',function(e){if(e.key==='Escape'&&s&&s.classList.contains('open')){cs();}});});</script>
</head>
<body>

<!-- Barre de navigation principale de l'administration -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container">
        <!-- Logo de la marque avec lien vers le tableau de bord admin -->
        <a class="navbar-brand d-flex align-items-center fw-bold" href="<?= BASE_URL ?>/admin/index.php">
            <img src="<?= ASSETS_URL ?>/images/brand/logo.svg" alt="CLAUDISHOP Admin" height="32">
            <span class="ms-2 badge bg-primary">Admin</span>
        </a>
        <!-- Bouton de bascule pour le menu responsive sur mobile -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Contenu de la navigation qui se replie sur mobile -->
        <div class="collapse navbar-collapse" id="adminNav">
            <!-- Liste des liens de navigation de l'administration -->
            <ul class="navbar-nav me-auto">
                <!-- Lien Dashboard : actif si la page courante est index.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <!-- Lien Produits : actif si la page courante est produits.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'produits.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/produits.php"><i class="bi bi-box me-1"></i>Produits</a></li>
                <!-- Lien Catégories : actif si la page courante est categories.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'categories.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/categories.php"><i class="bi bi-tags me-1"></i>Catégories</a></li>
                <!-- Lien Commandes : actif si la page courante est commandes.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'commandes.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/commandes.php"><i class="bi bi-receipt me-1"></i>Commandes</a></li>
                <!-- Lien Livraisons : actif si la page courante est livraisons.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'livraisons.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/livraisons.php"><i class="bi bi-truck me-1"></i>Livraisons</a></li>
                <!-- Lien Paiements : actif si la page courante est paiements.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'paiements.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/paiements.php"><i class="bi bi-credit-card me-1"></i>Paiements</a></li>
                <!-- Lien Avis : actif si la page courante est avis.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'avis.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/avis.php"><i class="bi bi-star me-1"></i>Avis</a></li>
                <!-- Lien Zones : actif si la page courante est zones.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'zones.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/zones.php"><i class="bi bi-map me-1"></i>Zones</a></li>
                <!-- Lien Utilisateurs : actif si la page courante est utilisateurs.php -->
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'utilisateurs.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/utilisateurs.php"><i class="bi bi-people me-1"></i>Utilisateurs</a></li>
            </ul>
            <!-- Liens de navigation secondaires : site public et déconnexion -->
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-house me-1"></i>Site</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="<?= BASE_URL ?>/actions/deconnexion.php"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>
