<?php
// Barre supérieure (topbar) de l'interface d'administration

// Récupération de la première lettre du prénom pour l'avatar
$userInitialAdmin2 = strtoupper(substr($_SESSION['user_prenom'] ?? 'A', 0, 1));
// Sécurisation du nom complet de l'utilisateur
$adminName2 = securiser(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
// Sécurisation du contact (email pour les vrais emails, téléphone pour les phone-only)
$adminEmail2 = $_SESSION['user_email'] ?? '';
$adminPhone2 = $_SESSION['user_telephone'] ?? '';
$isPhoneOnly2 = (strpos($adminEmail2, 'tel-') === 0) && (substr($adminEmail2, -17) === '@claudishop.local');
$adminContact2 = $isPhoneOnly2 ? securiser($adminPhone2) : securiser($adminEmail2);
?>
<!-- Barre supérieure du tableau de bord -->
<div class="dash-topbar">
    <!-- Bouton de bascule pour la barre latérale sur mobile -->
    <button class="dash-mobile-toggle" id="dashSidebarToggle" aria-label="Menu" onclick="var s=document.getElementById('dashSidebar'),o=document.getElementById('dashSidebarOverlay');if(s){s.classList.toggle('open');}if(o){o.classList.toggle('open');}document.body.style.overflow=s&&s.classList.contains('open')?'hidden':'';">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Affichage de la date courante formatée -->
    <div class="dash-topbar-date"><?= date('l d F Y', time()) ?></div>
    <!-- Lien vers les notifications avec icône de cloche -->
    <a href="<?= BASE_URL ?>/admin/notifications.php" class="dash-topbar-notif" style="text-decoration:none;color:inherit;">
        <i class="fas fa-bell"></i>
    </a>
    <!-- Conteneur du menu déroulant de l'avatar utilisateur -->
    <div id="avatar-dropdown-trigger" style="cursor:pointer;position:relative;">
        <!-- Affichage de l'initiale de l'utilisateur -->
        <div class="dash-topbar-avatar"><?= $userInitialAdmin2 ?></div>
        <!-- Menu déroulant caché par défaut, affiché au clic sur l'avatar -->
        <div id="avatar-dropdown-menu" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:white;border:1px solid var(--gray-200);border-radius:6px;box-shadow:0 10px 25px rgba(0,0,0,0.1);min-width:200px;z-index:1000;overflow:hidden;">
            <!-- En-tête du menu : nom et email de l'utilisateur -->
            <div style="padding:12px 14px;border-bottom:1px solid var(--gray-100);">
                <div class="text-sm font-semibold"><?= $adminName2 ?></div>
                <div class="text-xs text-muted"><?= $adminContact2 ?></div>
            </div>
            <!-- Lien vers l'administration -->
            <a href="<?= BASE_URL ?>/admin/index.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--dark);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                <i class="fas fa-th-large" style="width:16px;text-align:center;"></i> Administration
            </a>
            <!-- Lien vers le profil -->
            <a href="<?= BASE_URL ?>/user/profil.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--dark);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                <i class="fas fa-user" style="width:16px;text-align:center;"></i> Mon profil
            </a>
            <!-- Séparateur avant le lien de déconnexion -->
            <div style="border-top:1px solid var(--gray-100);"></div>
            <!-- Lien de déconnexion en rouge -->
            <a href="<?= BASE_URL ?>/actions/deconnexion.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--danger, #DC2626);text-decoration:none;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--gray-50)'" onmouseout="this.style.background=''">
                <i class="fas fa-sign-out-alt" style="width:16px;text-align:center;"></i> Déconnexion
            </a>
        </div>
    </div>
</div>
