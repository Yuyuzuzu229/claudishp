<?php
// includes/user_topbar.php
$__today = strftime('%A %d %B %Y') ?: date('l d F Y');
$__nbNotifTopbar = 0;
if (class_exists('Notification')) {
    $__nt = new Notification();
    $__nbNotifTopbar = $__nt->getNombreNonLu($_SESSION['user_id']);
}
$__initial2 = strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1));
?>
<div class="dash-topbar">
    <button class="dash-mobile-toggle" id="dashSidebarToggle" aria-label="Menu" onclick="var s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');if(s){s.classList.toggle('open');}if(o){o.classList.toggle('open');}document.body.style.overflow=s&&s.classList.contains('open')?'hidden':'';">
        <i class="fas fa-bars"></i>
    </button>
    <div class="dash-topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Rechercher...">
    </div>
    <div class="dash-topbar-date"><?= date('l d F Y') ?></div>
    <a href="<?= BASE_URL ?>/user/notifications.php" class="dash-topbar-notif" style="text-decoration:none;color:inherit;">
        <i class="fas fa-bell"></i>
        <?php if ($__nbNotifTopbar > 0): ?><span class="notif-dot"><?= $__nbNotifTopbar ?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/user/profil.php" class="dash-topbar-avatar" style="text-decoration:none;"><?= $__initial2 ?></a>
</div>
