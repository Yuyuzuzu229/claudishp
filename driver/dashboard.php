<?php
// Inclusion de la configuration, des classes et de la base de données
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Livreur.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../config/database.php';

// Vérification : si le livreur n'est pas connecté, redirection vers la connexion
if (!isset($_SESSION['driver_id'])) redirect(BASE_URL . '/driver/connexion.php');

// Instanciation des objets Livreur et récupération des données du livreur connecté
$livreurObj = new Livreur();
$driver = $livreurObj->getById($_SESSION['driver_id']);
// Récupération des livraisons en cours assignées à ce livreur
$livraisonsEnCours = $livreurObj->getLivraisonsEnCours($_SESSION['driver_id']);

// Titre de la page
$pageTitle = 'Tableau de bord livreur';
$pageStyles = [];
// Inclusion de l'en-tête
require_once __DIR__ . '/../includes/header.php';
?>
    <div style="max-width:1000px;margin:0 auto;padding:20px;">
    <!-- Affichage des messages de succès -->
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <!-- Affichage des messages d'erreur -->
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <!-- En-tête avec le nom du livreur, téléphone, véhicule, et boutons -->
    <div class="flex justify-between items-center" style="margin-bottom:24px;">
        <div>
            <h1 style="font-size:22px;font-weight:700;">
                <i class="fas fa-motorcycle" style="margin-right:8px;"></i>
                Bonjour, <?= securiser($driver['nom']) ?>
            </h1>
            <p class="text-muted text-sm">
                <?= securiser($driver['telephone']) ?>
                <!-- Affichage conditionnel du véhicule -->
                <?php if ($driver['vehicule']): ?> &middot; <?= securiser($driver['vehicule']) ?><?php endif; ?>
            </p>
        </div>
        <div class="flex gap-2">
            <!-- Bouton d'installation de l'application PWA (caché par défaut) -->
            <button id="install-pwa-btn" class="btn btn-outline-dark btn-sm" style="display:none;" onclick="installPWA()">
                <i class="fas fa-download"></i> Installer l'app
            </button>
            <!-- Lien de déconnexion -->
            <a href="<?= BASE_URL ?>/actions/driver_deconnexion.php" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>
    <!-- Script pour la gestion de l'installation PWA (Progressive Web App) -->
    <script>
    var deferredPrompt = null;
    // Écoute l'événement beforeinstallprompt pour proposer l'installation de l'app
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
    });
    // Fonction pour déclencher l'installation PWA
    function installPWA() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function() { deferredPrompt = null; });
        }
    }
    </script>

<div id="driver-content">
    <!-- Indicateurs KPI : livraisons en cours et statut -->
    <div class="kpi-grid kpi-grid-3" style="margin-bottom:24px;">
        <div class="kpi-card">
            <div><div class="kpi-label">Livraisons en cours</div><div class="kpi-value"><?= count($livraisonsEnCours) ?></div></div>
            <i class="fas fa-truck kpi-icon"></i>
        </div>
        <div class="kpi-card">
            <div><div class="kpi-label">Statut</div>
                <div class="kpi-value" style="font-size:16px;">
                    <!-- Affichage du statut avec couleur (vert si disponible, orange sinon) -->
                    <?php if ($driver['statut'] === 'Disponible'): ?>
                    <span style="color:var(--success);"><i class="fas fa-circle"></i> Disponible</span>
                    <?php else: ?>
                    <span style="color:var(--warning);"><i class="fas fa-circle"></i> <?= securiser($driver['statut']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <i class="fas fa-clock kpi-icon"></i>
        </div>

    </div>

    <!-- Vérification : si aucune livraison en cours -->
    <?php if (empty($livraisonsEnCours)): ?>
    <!-- Message affiché quand il n'y a pas de livraisons -->
    <div class="table-card" style="padding:48px;text-align:center;">
        <i class="fas fa-check-circle" style="font-size:40px;color:var(--gray-200);margin-bottom:12px;display:block;"></i>
        <p class="text-muted">Aucune livraison en cours.</p>
        <p class="text-xs text-muted">Vous recevrez une notification quand une livraison vous sera assignée.</p>
    </div>
    <!-- Sinon, affichage de la liste des livraisons -->
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px;">
        <!-- Boucle d'affichage de chaque livraison en cours -->
        <?php foreach ($livraisonsEnCours as $l):
            // Récupération des informations du client
            $stmt = getPdo()->prepare("SELECT telephone, latitude_client, longitude_client FROM commande WHERE id = ?");
            $stmt->execute([$l['commande_id']]);
            $cli = $stmt->fetch();
            // Formatage du téléphone pour WhatsApp
            $telClientWA = $cli ? formatWhatsApp($cli['telephone']) : '';
            $nomClientWA = securiser($l['nom_complet'] ?? '');
            // Message WhatsApp pré-rempli
            $msgWA = rawurlencode("Bonjour {$nomClientWA}, je suis votre livreur ClaudiShop ! Je charge votre commande et arrive dans quelques instants.");
        ?>
        <!-- Carte de livraison -->
        <div class="livraison-card" style="border:1px solid var(--gray-200);padding:16px;">
            <div class="flex justify-between items-center" style="margin-bottom:12px;">
                <div>
                    <!-- Identifiants de livraison et de commande -->
                    <strong style="font-size:15px;">Livraison #L<?= str_pad($l['id'],4,'0',STR_PAD_LEFT) ?></strong>
                    <span class="text-xs text-muted" style="margin-left:8px;">Commande #CMD-<?= str_pad($l['commande_id'],6,'0',STR_PAD_LEFT) ?></span>
                </div>
                <!-- Badge de statut de la livraison -->
                <?= getStatutBadge($l['statut'] ?? 'Prêt à expédier') ?>
            </div>
            <!-- Informations client : nom et adresse -->
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
            <!-- Détermination du prochain statut et du libellé du bouton d'action -->
            <?php
            $statutActuel = $l['statut'] ?? '';
            $prochainStatut = '';
            $btnLabel = '';
            $btnIcon = '';
            // Si le statut actuel est "Prêt à expédier" ou "En attente"
            if ($statutActuel === 'Prêt à expédier' || $statutActuel === 'En attente') {
                $prochainStatut = 'En route';
                $btnLabel = 'Démarrer la livraison';
                $btnIcon = 'fa-play';
            // Si le statut actuel est "En route"
            } elseif ($statutActuel === 'En route') {
                $prochainStatut = 'En cours';
                $btnLabel = 'Arrivé sur place';
                $btnIcon = 'fa-check';
            // Si le statut actuel est "En cours"
            } elseif ($statutActuel === 'En cours') {
                $prochainStatut = 'Livrée';
                $btnLabel = 'Confirmer livraison';
                $btnIcon = 'fa-gift';
            }
            ?>
            <!-- Formulaire de mise à jour du statut (affiché s'il y a un prochain statut) -->
            <?php if ($prochainStatut): ?>
            <form method="POST" action="<?= BASE_URL ?>/actions/driver_update_statut.php" style="margin-top:12px;">
                <input type="hidden" name="livraison_id" value="<?= $l['id'] ?>">
                <input type="hidden" name="statut" value="<?= $prochainStatut ?>">
                <!-- Bouton de passage au statut suivant avec confirmation -->
                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('Passer cette livraison en « <?= $prochainStatut ?> » ?')">
                    <i class="fas <?= $btnIcon ?>"></i> <?= $btnLabel ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- Enregistrement du Service Worker pour le mode hors ligne -->
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js').catch(function(){});
}
</script>
<!-- Configuration Firebase Cloud Messaging (FCM) pour les notifications push -->
<?php
require_once __DIR__ . '/../config/firebase.php';
// Vérification si FCM est configuré
if (fcmEstConfigure()):
?>
<!-- Scripts Firebase -->
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>
<script>
// Initialisation de Firebase avec la configuration
var firebaseConfig = <?= FIREBASE_CONFIG ?>;
firebase.initializeApp(firebaseConfig);
var messaging = firebase.messaging();

// Demande de permission de notification et enregistrement du token FCM
Notification.requestPermission().then(function(perm) {
    if (perm === 'granted') {
        // Récupération du token de notification
        messaging.getToken({ vapidKey: '<?= FCM_VAPID_KEY ?>' }).then(function(token) {
            if (token) {
                // Envoi du token au serveur pour l'enregistrer
                var fd = new FormData();
                fd.append('fcm_token', token);
                fetch('<?= BASE_URL ?>/api/enregistrer_fcm_token.php', { method: 'POST', body: fd });
            }
        }).catch(function(){});
    }
});

// Écoute des messages push lorsque l'application est au premier plan
messaging.onMessage(function(payload) {
    if (payload.notification) {
        // Affichage d'une notification système
        var n = new Notification(payload.notification.title, {
            body: payload.notification.body,
            icon: '<?= ASSETS_URL ?>/images/brand/favicon.svg'
        });
        // Fermeture automatique après 8 secondes
        setTimeout(function() { n.close(); }, 8000);
    }
});
</script>
<?php endif; ?>
<!-- Script de "polling" pour rafraîchir automatiquement le contenu du dashboard -->
<script>
(function() {
    var driverUrl = window.location.href;
    // Fonction de polling récursive
    function pollDriver() {
        // Récupération du HTML de la page
        fetch(driverUrl)
            .then(function(r) { return r.text(); })
            .then(function(html) {
                // Extraction et remplacement du contenu du bloc #driver-content
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newContent = doc.getElementById('driver-content');
                var oldContent = document.getElementById('driver-content');
                if (newContent && oldContent) {
                    oldContent.innerHTML = newContent.innerHTML;
                }
                // Rappel après 5 secondes
                setTimeout(pollDriver, 5000);
            })
            .catch(function() { setTimeout(pollDriver, 5000); });
    }
    // Premier appel après 5 secondes
    setTimeout(pollDriver, 5000);
})();
</script>
</body>
</html>
