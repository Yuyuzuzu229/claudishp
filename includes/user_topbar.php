<?php
// includes/user_topbar.php - Barre supérieure de l'espace utilisateur

// Récupération de la date du jour formatée en français
$__today = strftime('%A %d %B %Y') ?: date('l d F Y');
// Initialisation du compteur de notifications non lues
$__nbNotifTopbar = 0;
// Si la classe Notification existe, récupération du nombre de notifications non lues
if (class_exists('Notification')) {
    $__nt = new Notification();
    $__nbNotifTopbar = $__nt->getNombreNonLu($_SESSION['user_id']);
}
// Récupération de l'initiale du prénom pour l'avatar
$__initial2 = strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1));
?>
<!-- Barre supérieure du tableau de bord utilisateur -->
<div class="dash-topbar">
    <!-- Bouton de bascule pour la barre latérale sur mobile -->
    <button class="dash-mobile-toggle" id="dashSidebarToggle" aria-label="Menu" onclick="var s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');if(s){s.classList.toggle('open');}if(o){o.classList.toggle('open');}document.body.style.overflow=s&&s.classList.contains('open')?'hidden':'';">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Barre de recherche dans la topbar -->
    <div class="dash-topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Rechercher...">
    </div>
    <!-- Affichage de la date courante -->
    <div class="dash-topbar-date"><?= date('l d F Y') ?></div>
    <!-- Lien vers les notifications avec icône de cloche -->
    <a href="<?= BASE_URL ?>/user/notifications.php" class="dash-topbar-notif" style="text-decoration:none;color:inherit;">
        <i class="fas fa-bell"></i>
        <!-- Badge du nombre de notifications non lues si > 0 -->
        <?php if ($__nbNotifTopbar > 0): ?><span class="notif-dot"><?= $__nbNotifTopbar ?></span><?php endif; ?>
    </a>
    <!-- Avatar utilisateur avec lien vers le profil -->
    <a href="<?= BASE_URL ?>/user/profil.php" class="dash-topbar-avatar" style="text-decoration:none;"><?= $__initial2 ?></a>
</div>
