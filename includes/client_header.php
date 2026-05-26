<?php
// includes/client_header.php - En-tête de l'espace client

// Valeurs par défaut pour la page client courante, le titre et le sous-titre
$client_page = $client_page ?? 'dashboard';
$client_title = $client_title ?? 'Espace Client';
$client_subtitle = $client_subtitle ?? '';

// Définition de la navigation client : tableau associatif des pages accessibles
$client_nav = [
    'dashboard' => ['label'=>'Dashboard','href'=>'dashboard.php','icon'=>'grid','section'=>''],
    // MON COMPTE
    'profil'         => ['label'=>'Mon profil','href'=>'profil.php','icon'=>'user','section'=>'MON COMPTE'],
    'panier'         => ['label'=>'Mon panier','href'=>'panier.php','icon'=>'cart','badge'=>'4','section'=>''],
    'paiement'       => ['label'=>'Paiement','href'=>'paiement.php','icon'=>'card','section'=>''],

    // MES COMMANDES
    'commandes'      => ['label'=>'Mes commandes','href'=>'commandes.php','icon'=>'bag','section'=>'MES COMMANDES'],
    // AVIS & COMM
    'avis'           => ['label'=>'Mes avis','href'=>'avis.php','icon'=>'star','section'=>'AVIS & COMMUNICATION'],
    'notifications'  => ['label'=>'Mes notifications','href'=>'notifications.php','icon'=>'bell','badge'=>'3','section'=>''],
    'deconnexion'    => ['label'=>'Déconnexion','href'=>'../index.php','icon'=>'logout','section'=>''],
];

// Fonction de rendu des icônes SVG pour la navigation client
function client_icon($name) {
    // Tableau associatif des icônes SVG
    $icons = [
        'grid'    => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'user'    => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'cart'    => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'card'    => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
        'truck'   => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        'bag'     => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'star'    => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'bell'    => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
        'logout'  => '<svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];
    // Retour de l'icône correspondante ou chaîne vide si le nom n'existe pas
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<!-- Titre de la page avec échappement HTML pour la sécurité -->
<title><?= htmlspecialchars($client_title) ?> — ClaudiShop</title>
<!-- Préconnexion aux polices Google Fonts pour optimiser le chargement -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<!-- Chargement des feuilles de style principales et client -->
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/client.css">
<!-- Chargement différé du JavaScript -->
<script defer src="../assets/js/script.js"></script>
</head>
<body>
<div class="client-layout">

<!-- SIDEBAR OVERLAY POUR MOBILE - fond sombre derrière la sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR CLIENT - barre latérale de navigation -->
<aside class="client-sidebar" id="clientSidebar">
  <!-- En-tête de la marque dans la sidebar -->
  <div class="sidebar-brand">
    <div>
      <div class="brand-name">CLAUDI<span style="font-size:.55rem;letter-spacing:.12em;color:#9A9A9A;display:block;">SHOP</span></div>
    </div>
    <span class="brand-space">ESPACE CLIENT</span>
  </div>

  <!-- Bloc utilisateur dans la sidebar -->
  <div class="sidebar-user" style="padding:14px 20px;border-bottom:1px solid #E5E5E5;display:flex;align-items:center;gap:10px;">
    <!-- Avatar rond avec l'initiale -->
    <div style="width:36px;height:36px;border-radius:50%;background:#0A0A0A;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:600;flex-shrink:0;">A</div>
    <div>
      <div class="user-name">Adjoua K.</div>
      <div class="user-email">adjoua.k@email.com</div>
    </div>
  </div>

  <!-- Navigation de la sidebar -->
  <nav class="sidebar-nav">
    <?php
    // Variable pour suivre la dernière section affichée
    $last_sec = null;
    // Boucle sur chaque élément de navigation
    foreach ($client_nav as $key => $item):
        // Si l'élément a une section différente de la dernière, on affiche un en-tête de section
        if ($item['section'] && $item['section'] !== $last_sec):
            $last_sec = $item['section'];
            echo "<div class='nav-section-label'>{$item['section']}</div>";
        endif;
        // Détermination de la classe active si la page courante correspond
        $act = ($client_page === $key) ? ' active' : '';
    ?>
    <!-- Lien de navigation avec icône, libellé et badge optionnel -->
    <a href="<?= $item['href'] ?>" class="nav-item<?= $act ?>">
      <span style="flex-shrink:0;"><?= client_icon($item['icon']) ?></span>
      <span><?= $item['label'] ?></span>
      <?php if (!empty($item['badge'])): ?>
        <!-- Badge de notification à droite du libellé -->
        <span class="nav-badge"><?= $item['badge'] ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Pied de la sidebar avec la version -->
  <div class="sidebar-footer" style="padding:10px 0;border-top:1px solid #E5E5E5;">
    <div class="sidebar-version">v1.0.0 • ClaudiShop</div>
  </div>
</aside>

<!-- CONTENU PRINCIPAL CLIENT -->
<div class="client-main">
  <!-- Barre supérieure (topbar) du client -->
  <header class="client-topbar">
    <!-- Bouton de bascule pour la sidebar sur mobile -->
    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Menu">
      <span></span>
    </button>
    <!-- Titre et sous-titre de la page -->
    <div class="topbar-title">
      <h1><?= htmlspecialchars($client_title) ?></h1>
      <?php if ($client_subtitle): ?><div class="topbar-subtitle"><?= htmlspecialchars($client_subtitle) ?></div><?php endif; ?>
    </div>

    <div style="flex:1;"></div>

    <!-- Champ de recherche -->
    <div style="position:relative;width:220px;">
      <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9A9A9A;" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder="Rechercher…" style="width:100%;padding:8px 12px 8px 34px;border:1px solid #E5E5E5;border-radius:20px;font-size:.82rem;outline:none;background:#F5F5F5;">
    </div>

    <!-- Affichage de la date courante -->
    <div class="topbar-date" style="font-size:.82rem;color:#5A5A5A;white-space:nowrap;margin-left:16px;">Mercredi 13 mai 2026</div>

    <!-- Icônes de notification et avatar utilisateur -->
    <div style="display:flex;align-items:center;gap:8px;margin-left:14px;">
      <!-- Icône de notification avec badge -->
      <div style="position:relative;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span style="position:absolute;top:4px;right:4px;background:#C9A03D;color:#fff;font-size:.6rem;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px;">3</span>
      </div>
      <!-- Avatar utilisateur -->
      <div style="width:36px;height:36px;border-radius:50%;background:#0A0A0A;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:600;cursor:pointer;">A</div>
    </div>
  </header>

  <main class="client-content">
