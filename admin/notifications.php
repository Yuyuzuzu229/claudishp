<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
$adminPage = 'notifications';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Communication</div>
        <h1 class="dash-page-title">Notifications</h1>
        <p class="dash-page-sub">Toutes les notifications système</p>
    </div>

    <div class="table-card">
        <div class="table-card-header"><span class="table-card-title">Notifications récentes</span></div>
        <div>
        <?php
        $notifObj2 = new Notification();
        $notifs = $notifObj2->getAll();
        if (empty($notifs)): ?>
        <div style="padding:32px;text-align:center;color:var(--gray-400);">Aucune notification.</div>
        <?php else: foreach (array_slice($notifs,0,30) as $n): ?>
        <div class="notif-item">
            <div class="notif-icon"><i class="fas fa-bell"></i></div>
            <div class="notif-content">
                <div class="notif-title"><?= securiser($n['titre'] ?? 'Notification') ?></div>
                <div class="notif-sub"><?= securiser(mb_substr($n['message'],0,150)) ?></div>
                <div class="notif-time">
                    <?= date('d/m/Y H:i',strtotime($n['date_envoi'] ?? $n['date_creation'])) ?>
                    &middot; <span class="badge badge-<?= $n['canal'] === 'WhatsApp' ? 'success' : ($n['canal'] === 'Email' ? 'info' : 'secondary') ?>"><?= $n['canal'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
        </div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
