<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Panier.php';

if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Mes notifications';
$notifObj = new Notification();

$notifObj->marquerToutesLues($_SESSION['user_id']);
$notifications = $notifObj->getByUtilisateur($_SESSION['user_id']);
$nbNonLu = $notifObj->getNombreNonLu($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
$activePage = 'notifications';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Avis &amp; Communication</div>
        <h1 class="dash-page-title">Mes notifications</h1>
        <p class="dash-page-sub">Consultez toutes les notifications que vous avez reçues.</p>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Toutes les notifications (<?= count($notifications) ?>)</span>
            <?php if ($nbNonLu > 0): ?>
            <span style="font-size:12px;color:var(--gray-500);display:flex;align-items:center;gap:6px;"><i class="fas fa-check-circle"></i> Lues automatiquement</span>
            <?php endif; ?>
        </div>
        <?php if (empty($notifications)): ?>
        <div style="padding:48px;text-align:center;">
            <i class="fas fa-bell" style="font-size:40px;color:var(--gray-200);margin-bottom:16px;display:block;"></i>
            <p class="text-muted">Aucune notification.</p>
        </div>
        <?php else: ?>
        <?php
        $notifIcons = [
            'commande' => 'fa-truck',
            'livraison' => 'fa-truck',
            'paiement' => 'fa-credit-card',
            'avis' => 'fa-star',
            'promo' => 'fa-tag',
            'annulation' => 'fa-times-circle',
            'bienvenue' => 'fa-gift',
        ];
        foreach ($notifications as $n):
            $icon = 'fa-bell';
            $msg = strtolower($n['message']);
            if (strpos($msg,'route')!==false || strpos($msg,'livr')!==false) $icon = 'fa-truck';
            elseif (strpos($msg,'préparation')!==false || strpos($msg,'expédi')!==false) $icon = 'fa-box';
            elseif (strpos($msg,'livrée')!==false || strpos($msg,'livré')!==false) $icon = 'fa-check-circle';
            elseif (strpos($msg,'annul')!==false) $icon = 'fa-times-circle';
            elseif (strpos($msg,'offre')!==false || strpos($msg,'promo')!==false || strpos($msg,'remise')!==false) $icon = 'fa-tag';
            elseif (strpos($msg,'paiement')!==false || strpos($msg,'confirmé')!==false) $icon = 'fa-shield-alt';
            elseif (strpos($msg,'bienvenu')!==false || strpos($msg,'inscrit')!==false) $icon = 'fa-gift';
            $timeAgo = '';
            $ts = strtotime($n['date_creation']);
            $diff = time() - $ts;
            if ($diff < 3600) $timeAgo = 'Il y a '.round($diff/60).' min';
            elseif ($diff < 86400) $timeAgo = 'Il y a '.round($diff/3600).' heures';
            else $timeAgo = date('d/m/Y', $ts);
        ?>
        <div class="notif-item <?= !$n['lu'] ? 'unread' : '' ?>" style="gap:14px;padding:16px 20px;">
            <div class="notif-icon"><i class="fas <?= $icon ?>"></i></div>
            <div class="notif-content" style="flex:1;">
                <div class="notif-title"><?= securiser($n['message']) ?></div>
                <div class="notif-sub" style="margin-top:2px;">
                    <?php if (strpos(strtolower($n['message']),'route')!==false): ?>Votre commande est en route et sera livrée bientôt.<?php
                    elseif (strpos(strtolower($n['message']),'préparation')!==false): ?>Nous préparons actuellement votre commande.<?php
                    else: ?>Notification de votre compte ClaudiShop.<?php endif; ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                <span class="notif-time"><?= $timeAgo ?></span>
                <?php if (!$n['lu']): ?><div class="notif-dot"></div><?php else: ?><div style="width:7px;height:7px;border-radius:50%;background:var(--gray-200);"></div><?php endif; ?>
                <button style="border:none;background:none;color:var(--gray-400);cursor:pointer;font-size:14px;"><i class="fas fa-ellipsis-v"></i></button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="why-buy" style="margin-top:28px;">
        <div class="why-buy-item"><i class="fas fa-box"></i><h4>Besoin d'aide ?</h4><p>Consultez notre FAQ ou contactez notre support</p></div>
        <div class="why-buy-item"><i class="fas fa-undo"></i><h4>Retours faciles</h4><p>Retournez vos articles sous 7 jours</p></div>
        <div class="why-buy-item"><i class="fas fa-shield-alt"></i><h4>Paiement sécurisé</h4><p>Vos paiements sont protégés à 100%</p></div>
        <div class="why-buy-item"><i class="fas fa-truck"></i><h4>Livraison rapide</h4><p>Partout au Bénin</p></div>
    </div>
</div>
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
</body></html>
