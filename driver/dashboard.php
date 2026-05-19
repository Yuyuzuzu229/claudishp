<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Livreur.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['driver_id'])) redirect(BASE_URL . '/driver/connexion.php');

$livreurObj = new Livreur();
$driver = $livreurObj->getById($_SESSION['driver_id']);
$livraisonsEnCours = $livreurObj->getLivraisonsEnCours($_SESSION['driver_id']);

$pageTitle = 'Tableau de bord livreur';
$pageStyles = [];
require_once __DIR__ . '/../includes/header.php';
?>
    <div style="max-width:1000px;margin:0 auto;padding:20px;">
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <div class="flex justify-between items-center" style="margin-bottom:24px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;">
                <i class="fas fa-motorcycle" style="margin-right:8px;"></i>
                Bonjour, <?= securiser($driver['nom']) ?>
            </h1>
            <p class="text-muted text-sm">
                <?= securiser($driver['telephone']) ?>
                <?php if ($driver['vehicule']): ?> &middot; <?= securiser($driver['vehicule']) ?><?php endif; ?>
            </p>
        </div>
        <div class="flex gap-2">
            <button id="install-pwa-btn" class="btn btn-outline-dark btn-sm" style="display:none;" onclick="installPWA()">
                <i class="fas fa-download"></i> Installer l'app
            </button>
            <a href="<?= BASE_URL ?>/actions/driver_deconnexion.php" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>
    <script>
    var deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
    });
    function installPWA() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function() { deferredPrompt = null; });
        }
    }
    </script>

<div id="driver-content">
    <div class="kpi-grid kpi-grid-3" style="margin-bottom:24px;">
        <div class="kpi-card">
            <div><div class="kpi-label">Livraisons en cours</div><div class="kpi-value"><?= count($livraisonsEnCours) ?></div></div>
            <i class="fas fa-truck kpi-icon"></i>
        </div>
        <div class="kpi-card">
            <div><div class="kpi-label">Statut</div>
                <div class="kpi-value" style="font-size:16px;">
                    <?php if ($driver['statut'] === 'Disponible'): ?>
                    <span style="color:var(--success);"><i class="fas fa-circle"></i> Disponible</span>
                    <?php else: ?>
                    <span style="color:var(--warning);"><i class="fas fa-circle"></i> <?= securiser($driver['statut']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <i class="fas fa-toggle-on kpi-icon"></i>
        </div>

    </div>

    <?php if (empty($livraisonsEnCours)): ?>
    <div class="table-card" style="padding:48px;text-align:center;">
        <i class="fas fa-check-circle" style="font-size:40px;color:var(--gray-200);margin-bottom:12px;display:block;"></i>
        <p class="text-muted">Aucune livraison en cours.</p>
        <p class="text-xs text-muted">Vous recevrez une notification quand une livraison vous sera assignée.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($livraisonsEnCours as $l):
            $stmt = getPdo()->prepare("SELECT telephone, latitude_client, longitude_client FROM commande WHERE id = ?");
            $stmt->execute([$l['commande_id']]);
            $cli = $stmt->fetch();
            $telClientWA = $cli ? formatWhatsApp($cli['telephone']) : '';
            $nomClientWA = securiser($l['nom_complet'] ?? '');
            $msgWA = rawurlencode("Bonjour {$nomClientWA}, je suis votre livreur ClaudiShop ! Je charge votre commande et arrive dans quelques instants.");
        ?>
        <div class="livraison-card" style="border:1px solid var(--gray-200);padding:16px;">
            <div class="flex justify-between items-center" style="margin-bottom:12px;">
                <div>
                    <strong style="font-size:15px;">Livraison #L<?= str_pad($l['id'],4,'0',STR_PAD_LEFT) ?></strong>
                    <span class="text-xs text-muted" style="margin-left:8px;">Commande #CMD-<?= str_pad($l['commande_id'],6,'0',STR_PAD_LEFT) ?></span>
                </div>
                <?= getStatutBadge($l['statut'] ?? 'Prêt à expédier') ?>
            </div>
            <div class="grid-2" style="gap:12px;">
                <div>
                    <div class="text-xs text-muted">Client</div>
                    <div class="text-sm font-semibold"><?= $nomClientWA ?></div>
                </div>
                <div>
                    <div class="text-xs text-muted">Adresse</div>
                    <div class="text-sm"><?= securiser($l['adresse_livraison'] ?? $l['adresse'] ?? '—') ?></div>
                </div>
            </div>
            <?php
            $statutActuel = $l['statut'] ?? '';
            $prochainStatut = '';
            $btnLabel = '';
            $btnIcon = '';
            if ($statutActuel === 'Prêt à expédier' || $statutActuel === 'En attente') {
                $prochainStatut = 'En route';
                $btnLabel = 'Démarrer la livraison';
                $btnIcon = 'fa-play';
            } elseif ($statutActuel === 'En route') {
                $prochainStatut = 'En cours';
                $btnLabel = 'Arrivé sur place';
                $btnIcon = 'fa-check';
            } elseif ($statutActuel === 'En cours') {
                $prochainStatut = 'Livrée';
                $btnLabel = 'Confirmer livraison';
                $btnIcon = 'fa-gift';
            }
            ?>
            <?php if ($prochainStatut): ?>
            <form method="POST" action="<?= BASE_URL ?>/actions/driver_update_statut.php" style="margin-top:12px;">
                <input type="hidden" name="livraison_id" value="<?= $l['id'] ?>">
                <input type="hidden" name="statut" value="<?= $prochainStatut ?>">
                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('Passer cette livraison en « <?= $prochainStatut ?> » ?')">
                    <i class="fas <?= $btnIcon ?>"></i> <?= $btnLabel ?>
                </button>
            </form>
            <?php endif; ?>
            <div class="flex gap-2" style="margin-top:8px;">
                <?php if ($telClientWA): ?>
                <a href="https://wa.me/<?= $telClientWA ?>?text=<?= $msgWA ?>" target="_blank" class="btn btn-dark btn-sm" style="background:#25D366;border-color:#25D366;">
                    <i class="fab fa-whatsapp"></i> Client WhatsApp
                </a>
                <?php endif; ?>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js').catch(function(){});
}
</script>
<?php
require_once __DIR__ . '/../config/firebase.php';
if (fcmEstConfigure()):
?>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>
<script>
var firebaseConfig = <?= FIREBASE_CONFIG ?>;
firebase.initializeApp(firebaseConfig);
var messaging = firebase.messaging();

Notification.requestPermission().then(function(perm) {
    if (perm === 'granted') {
        messaging.getToken({ vapidKey: '<?= FCM_VAPID_KEY ?>' }).then(function(token) {
            if (token) {
                var fd = new FormData();
                fd.append('fcm_token', token);
                fetch('<?= BASE_URL ?>/api/enregistrer_fcm_token.php', { method: 'POST', body: fd });
            }
        }).catch(function(){});
    }
});

messaging.onMessage(function(payload) {
    if (payload.notification) {
        var n = new Notification(payload.notification.title, {
            body: payload.notification.body,
            icon: '<?= ASSETS_URL ?>/images/brand/favicon.svg'
        });
        setTimeout(function() { n.close(); }, 8000);
    }
});
</script>
<?php endif; ?>
<script>
(function() {
    var driverUrl = window.location.href;
    function pollDriver() {
        fetch(driverUrl)
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newContent = doc.getElementById('driver-content');
                var oldContent = document.getElementById('driver-content');
                if (newContent && oldContent) {
                    oldContent.innerHTML = newContent.innerHTML;
                }
                setTimeout(pollDriver, 5000);
            })
            .catch(function() { setTimeout(pollDriver, 5000); });
    }
    setTimeout(pollDriver, 5000);
})();
</script>
</body>
</html>
