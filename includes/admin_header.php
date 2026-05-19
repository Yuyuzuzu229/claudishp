<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? 'Admin - ' . securiser($pageTitle) . ' | ' : '' ?>CLAUDISHOP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=3">
    <link rel="icon" type="image/svg+xml" href="<?= ASSETS_URL ?>/images/brand/favicon.svg">
    <script defer src="<?= ASSETS_URL ?>/js/script.js"></script>
    <script>document.addEventListener('keydown',function(e){if(e.key==='Escape'){var s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');if(s&&s.classList.contains('open')){s.classList.remove('open');if(o)o.classList.remove('open');document.body.style.overflow='';}}});
    document.addEventListener('DOMContentLoaded',function(){var t=document.getElementById('dashSidebarToggle'),s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');function cs(){if(s)s.classList.remove('open');if(o)o.classList.remove('open');document.body.style.overflow='';}if(o)o.addEventListener('click',cs);document.addEventListener('keydown',function(e){if(e.key==='Escape'&&s&&s.classList.contains('open')){cs();}});});</script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="<?= BASE_URL ?>/admin/index.php">
            <img src="<?= ASSETS_URL ?>/images/brand/logo.svg" alt="CLAUDISHOP Admin" height="32">
            <span class="ms-2 badge bg-primary">Admin</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'produits.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/produits.php"><i class="bi bi-box me-1"></i>Produits</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'categories.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/categories.php"><i class="bi bi-tags me-1"></i>Catégories</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'commandes.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/commandes.php"><i class="bi bi-receipt me-1"></i>Commandes</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'livraisons.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/livraisons.php"><i class="bi bi-truck me-1"></i>Livraisons</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'paiements.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/paiements.php"><i class="bi bi-credit-card me-1"></i>Paiements</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'avis.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/avis.php"><i class="bi bi-star me-1"></i>Avis</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'zones.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/zones.php"><i class="bi bi-map me-1"></i>Zones</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'utilisateurs.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/utilisateurs.php"><i class="bi bi-people me-1"></i>Utilisateurs</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-house me-1"></i>Site</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="<?= BASE_URL ?>/actions/deconnexion.php"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>
