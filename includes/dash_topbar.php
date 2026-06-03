<?php
// Barre supérieure (topbar) du tableau de bord

$__nbNonLu2 = 0;
if (isLoggedIn() && class_exists('Notification')) {
    $__n2 = new Notification(); $__nbNonLu2 = $__n2->getNombreNonLu($_SESSION['user_id']);
}
$userInitial2 = strtoupper(substr($_SESSION['user_prenom'] ?? 'U', 0, 1));
$userName2 = securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
$userEmail2 = $_SESSION['user_email'] ?? '';
$userPhone2 = $_SESSION['user_telephone'] ?? '';
$isPhoneOnly2 = (strpos($userEmail2, 'tel-') === 0) && (substr($userEmail2, -17) === '@claudishop.local');
$userContact2 = $isPhoneOnly2 ? securiser($userPhone2) : securiser($userEmail2);
?>
<div class="dash-topbar">
    <button class="dash-mobile-toggle" id="dashSidebarToggle" aria-label="Menu" onclick="var s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');if(s){s.classList.toggle('open');}if(o){o.classList.toggle('open');}document.body.style.overflow=s&&s.classList.contains('open')?'hidden':'';"><i class="fas fa-bars"></i></button>
    <div class="dash-topbar-date"><?= date('l d F Y', time()) ?></div>
    <a href="<?= BASE_URL ?>/<?= isAdmin() ? 'admin/notifications.php' : 'user/notifications.php' ?>" class="dash-topbar-notif" style="text-decoration:none;color:inherit;">
        <i class="fas fa-bell"></i>
        <?php if ($__nbNonLu2 > 0): ?><span class="notif-dot"><?= $__nbNonLu2 ?></span><?php endif; ?>
    </a>
    <div id="avatar-dropdown-trigger" style="cursor:pointer;position:relative;">
        <div class="dash-topbar-avatar"><?= $userInitial2 ?></div>
        <div id="avatar-dropdown-menu" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:white;border:1px solid var(--gray-200);border-radius:6px;box-shadow:0 10px 25px rgba(0,0,0,0.1);min-width:200px;z-index:1000;overflow:hidden;">
            <div style="padding:12px 14px;border-bottom:1px solid var(--gray-100);">
                <div class="text-sm font-semibold"><?= $userName2 ?></div>
                <div class="text-xs text-muted"><?= $userContact2 ?></div>
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
</div>
