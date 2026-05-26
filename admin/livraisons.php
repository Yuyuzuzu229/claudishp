<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Livraison
require_once __DIR__ . '/../classes/Livraison.php';
// Inclusion de la classe Livreur
require_once __DIR__ . '/../classes/Livreur.php';
// Inclusion de la classe Commande
require_once __DIR__ . '/../classes/Commande.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// V�rification que l'utilisateur est connect� et a le r�le administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Instanciation des objets Livraison, Livreur et Commande
$livraisonObj = new Livraison();
$livreurObj = new Livreur();
$commandeObj = new Commande();

// Auto-assigner les livreurs disponibles aux livraisons en attente
$livraisonObj->assignerAutomatique();

// Traitement POST : gestion des actions sur les livraisons
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // R�cup�ration de l'action � effectuer
    $action = $_POST['action'] ?? '';

    // Si l'action est de mettre � jour le statut d'une livraison
    if ($action === 'update_statut') {
        $id = intval($_POST['id']);
        $statut = securiser($_POST['statut']);
        $livraisonObj->updateStatut($id, $statut);
        // Si le statut est "Livr�e", mise � jour du statut de la commande associ�e �galement
        if ($statut === 'Livr�e') {
            $l = $livraisonObj->getById($id);
            if ($l) $commandeObj->updateStatut($l['commande_id_ref'] ?? $l['commande_id'], 'Livr�e');
        }
        $_SESSION['success'] = 'Statut mis � jour.';
        redirect(BASE_URL . '/admin/livraisons.php');
    }

    // Si l'action est de mettre � jour la position du livreur
    if ($action === 'update_position') {
        $id = intval($_POST['id']);
        $lat = floatval($_POST['latitude']);
        $lng = floatval($_POST['longitude']);
        $livraisonObj->updatePosition($id, $lat, $lng);
        $_SESSION['success'] = 'Position mise � jour.';
        redirect(BASE_URL . '/admin/livraisons.php');
    }

    // Si l'action est d'assigner un livreur � une livraison
    if ($action === 'assign_livreur') {
        $id = intval($_POST['id']);
        $livreurId = intval($_POST['livreur_id']);
        $livraisonObj->assignerLivreur($id, $livreurId);
        $livreurObj->changerStatut($livreurId, 'En livraison');

        // Envoyer une notification par email au livreur assign�
        try {
            $driver = $livreurObj->getById($livreurId);
            $tokenAcces = $livraisonObj->getTokenAcces($id);
            $pdo = Database::getInstance()->getConnection();
            // Requ�te pr�par�e pour r�cup�rer les d�tails de la commande associ�e � la livraison
            $stmtCmd = $pdo->prepare("SELECT co.id, co.nom_complet, co.telephone, co.adresse_livraison, co.latitude_client, co.longitude_client, co.montant_total, z.nom as nom_zone, l.frais, l.distance_km FROM livraison l JOIN commande co ON l.commande_id = co.id LEFT JOIN zone_livraison z ON l.zone_id = z.id WHERE l.id = ?");
            $stmtCmd->execute([$id]);
            $commande = $stmtCmd->fetch();
            // Si le livreur, la commande et l'email sont valides, envoi de la notification
            if ($driver && $commande && !empty($driver['email'])) {
                require_once __DIR__ . '/../classes/NotificationService.php';
                $notifSvc = new NotificationService();
                $commandeData = [
                    'id' => $commande['id'],
                    'nom_complet' => $commande['nom_complet'],
                    'telephone' => $commande['telephone'],
                    'adresse_livraison' => $commande['adresse_livraison'],
                    'latitude_client' => $commande['latitude_client'],
                    'longitude_client' => $commande['longitude_client'],
                    'nom_zone' => $commande['nom_zone'],
                    'montant_total' => $commande['montant_total'],
                    'frais' => $commande['frais'],
                    'distance_km' => $commande['distance_km'],
                ];
                $sujetMail = 'Nouvelle livraison ClaudiShop #CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT);
                $messageHtml = $notifSvc->construireEmailLivraisonHtml($commandeData, $driver, $tokenAcces);
                $notifSvc->envoyerEmail($driver['email'], $sujetMail, $messageHtml, true);
            }
        } catch (Exception $e) {
            // Enregistrement de l'erreur dans les logs si l'envoi �choue
            error_log("Notification error for livraison #{$id}: " . $e->getMessage());
        }

        $_SESSION['success'] = 'Livreur assign� et notifi� par email.';
        redirect(BASE_URL . '/admin/livraisons.php');
    }
}

// D�finition du titre de la page
$pageTitle = 'Gestion Livraisons';
// Inclusion de l'en-t�te HTML du site
require_once __DIR__ . '/../includes/header.php';
// D�finition de la page active pour le menu d'administration
$adminPage = 'livraisons';

// R�cup�ration de toutes les livraisons et des livreurs disponibles
$livraisons = $livraisonObj->getAll();
$livreursDispo = $livreurObj->getDisponibles();
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Logistique</div>
        <h1 class="dash-page-title">Livraisons</h1>
        <p class="dash-page-sub">G�rez et suivez les livraisons en cours</p>
    </div>

    <!-- Affichage des indicateurs KPI pour les livraisons -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-card--navy"><div><div class="kpi-label">Total livraisons</div><div class="kpi-value"><?= count($livraisons) ?></div></div><i class="fas fa-truck kpi-icon"></i></div>
        <div class="kpi-card kpi-card--amber"><div><div class="kpi-label">En cours</div><div class="kpi-value"><?= $livraisonObj->getEnCours() ?></div></div><i class="fas fa-clock kpi-icon"></i></div>
        <div class="kpi-card kpi-card--green"><div><div class="kpi-label">Livr�es</div><div class="kpi-value"><?= $livraisonObj->getLivrees() ?></div></div><i class="fas fa-check kpi-icon"></i></div>
        <div class="kpi-card kpi-card--red"><div><div class="kpi-label">Livreurs dispo</div><div class="kpi-value"><?= count($livreursDispo) ?></div><div class="kpi-sub text-muted">pr�ts</div></div><i class="fas fa-motorcycle kpi-icon"></i></div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Toutes les livraisons</span>
        </div>
        <table>
            <thead><tr><th>#</th><th>Commande</th><th>Client</th><th>Livreur</th><th>Adresse</th><th>Position livreur</th><th>Partage</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($livraisons)): ?>
            <!-- Si aucune livraison n'est trouv�e, affichage d'un message par d�faut -->
            <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--gray-400);">Aucune livraison.</td></tr>
            <?php else: foreach ($livraisons as $l):
                // Pr�paration des donn�es pour le lien WhatsApp
                $telClientWA = !empty($l['telephone']) ? formatWhatsApp($l['telephone']) : '';
                $nomClientWA = securiser(($l['prenom'] ?? '') . ' ' . ($l['nom'] ?? ''));
                $msgLivreurWA = rawurlencode("Bonjour {$nomClientWA}, je suis votre livreur ClaudiShop ! Contactez-moi sur WhatsApp, je partagerai ma position en direct pour que vous puissiez me suivre.");
            ?>
            <!-- Boucle d'affichage de chaque livraison dans une ligne du tableau -->
            <tr>
                <td><strong>#<?= str_pad($l['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
                <td class="text-xs text-muted">#CMD-<?= str_pad($l['commande_id'] ?? 0,6,'0',STR_PAD_LEFT) ?></td>
                <td class="text-sm"><?= securiser(($l['prenom'] ?? '') . ' ' . ($l['nom'] ?? '')) ?></td>
                <td class="text-sm"><?= securiser($l['livreur_nom'] ?? '<span class="text-muted">Non assign�</span>') ?></td>
                <td class="text-xs text-muted"><?= securiser(mb_substr($l['adresse'] ?? ($l['nom_zone'] ?? '�'), 0, 35)) ?></td>
                <td class="text-xs text-muted"><?= $l['latitude_livreur'] && $l['longitude_livreur'] ? '? ' . $l['latitude_livreur'] . ', ' . $l['longitude_livreur'] : '�' ?></td>
                <td style="text-align:center;">
                    <?php if (!empty($l['livreur_nom']) && $telClientWA): ?>
                    <!-- Lien WhatsApp pour contacter le client si un livreur est assign� -->
                    <a href="https://wa.me/<?= $telClientWA ?>?text=<?= $msgLivreurWA ?>" target="_blank" class="action-btn" title="Contacter le client via WhatsApp">
                        <i class="fab fa-whatsapp" style="color:#25D366;font-size:16px;"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">�</span>
                    <?php endif; ?>
                </td>
                <td><?= getStatutBadge($l['statut'] ?? 'En attente') ?></td>
                <td>
                    <div class="flex gap-1">
                        <!-- Bouton pour ouvrir la modal de changement de statut -->
                        <button class="action-btn" onclick="ouvrirModalStatut(<?= $l['id'] ?>,'<?= $l['statut'] ?? 'En attente' ?>')" title="Changer statut"><i class="fas fa-flag"></i></button>
                        <!-- Bouton pour ouvrir la modal de mise � jour de la position du livreur -->
                        <button class="action-btn" onclick="ouvrirModalPosition(<?= $l['id'] ?>,<?= json_encode(floatval($l['latitude_livreur'] ?? 0)) ?>,<?= json_encode(floatval($l['longitude_livreur'] ?? 0)) ?>)" title="Position livreur"><i class="fas fa-map-marker-alt"></i></button>
                        <?php if (empty($l['livreur_nom'])): ?>
                        <!-- Bouton pour assigner un livreur si aucun n'est encore assign� -->
                        <button class="action-btn" onclick="ouvrirModalAssigner(<?= $l['id'] ?>)" title="Assigner livreur"><i class="fas fa-user-plus"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <!-- Pied du tableau indiquant le nombre total de livraisons -->
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);"><span class="text-xs text-muted">Total : <?= count($livraisons) ?> livraisons</span></div>
    </div>
</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits r�serv�s</span><span>v1.0.0</span></div>
</div>
</div>

<!-- MODAL Statut : Fen�tre modale pour changer le statut d'une livraison -->
<div id="modalStatut" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box" style="max-width:400px;">
        <button class="modal-close" onclick="document.getElementById('modalStatut').style.display='none'">?</button>
        <h2 class="modal-title">Mettre � jour le statut</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_statut">
            <input type="hidden" name="id" id="statut-id">
            <div class="form-group">
                <label>Statut</label>
                <select name="statut" class="form-control" id="statut-select">
                    <option value="En attente">En attente</option>
                    <option value="Pr�t � exp�dier">Pr�t � exp�dier</option>
                    <option value="En route">En route</option>
                    <option value="En cours">En cours</option>
                    <option value="Livr�e">Livr�e</option>
                    <option value="Annul�e">Annul�e</option>
                    <option value="�chou�e">�chou�e</option>
                </select>
            </div>
            <div class="flex gap-2 justify-between" style="margin-top:20px;">
                <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalStatut').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-dark">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL Position : Fen�tre modale pour mettre � jour la position GPS du livreur -->
<div id="modalPosition" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box" style="max-width:400px;">
        <button class="modal-close" onclick="document.getElementById('modalPosition').style.display='none'">?</button>
        <h2 class="modal-title">Position du livreur</h2>
        <p class="text-sm text-muted" style="margin-bottom:16px;">Saisissez les coordonn�es GPS actuelles du livreur.</p>
        <form method="POST">
            <input type="hidden" name="action" value="update_position">
            <input type="hidden" name="id" id="pos-id">
            <div class="form-group">
                <label>Latitude</label>
                <input type="number" step="0.0000001" name="latitude" id="pos-lat" class="form-control" required placeholder="ex: 6.3572000">
            </div>
            <div class="form-group" style="margin-top:10px;">
                <label>Longitude</label>
                <input type="number" step="0.0000001" name="longitude" id="pos-lng" class="form-control" required placeholder="ex: 2.4269000">
            </div>
            <div class="flex gap-2 justify-between" style="margin-top:20px;">
                <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalPosition').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-dark">Mettre � jour</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL Assigner livreur : Fen�tre modale pour assigner un livreur � une livraison -->
<div id="modalAssigner" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box" style="max-width:400px;">
        <button class="modal-close" onclick="document.getElementById('modalAssigner').style.display='none'">?</button>
        <h2 class="modal-title">Assigner un livreur</h2>
        <form method="POST">
            <input type="hidden" name="action" value="assign_livreur">
            <input type="hidden" name="id" id="assign-id">
            <div class="form-group">
                <label>Livreur disponible</label>
                <select name="livreur_id" class="form-control" required>
                    <option value="">S�lectionner...</option>
                    <?php foreach ($livreursDispo as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= securiser($d['nom']) ?> � <?= securiser($d['telephone']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2 justify-between" style="margin-top:20px;">
                <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalAssigner').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-dark">Assigner</button>
            </div>
        </form>
    </div>
</div>

<script>
<!-- Fonction d'ouverture de la modal de changement de statut avec pr�-remplissage des champs -->
function ouvrirModalStatut(id, statut) {
    document.getElementById('statut-id').value = id;
    document.getElementById('statut-select').value = statut;
    document.getElementById('modalStatut').style.display = 'flex';
}
<!-- Fonction d'ouverture de la modal de mise � jour de position avec pr�-remplissage des champs -->
function ouvrirModalPosition(id, lat, lng) {
    document.getElementById('pos-id').value = id;
    document.getElementById('pos-lat').value = lat || '';
    document.getElementById('pos-lng').value = lng || '';
    document.getElementById('modalPosition').style.display = 'flex';
}
<!-- Fonction d'ouverture de la modal d'assignation de livreur -->
function ouvrirModalAssigner(id) {
    document.getElementById('assign-id').value = id;
    document.getElementById('modalAssigner').style.display = 'flex';
}
</script>
<script>
(function(){
    var currentUrl = window.location.href;
    var modalIds = ['modalStatut', 'modalPosition', 'modalAssigner'];
    function actualiser() {
        var modalOuverte = false;
        for (var i = 0; i < modalIds.length; i++) {
            var m = document.getElementById(modalIds[i]);
            if (m && m.style.display !== 'none' && m.style.display !== '') {
                modalOuverte = true;
                break;
            }
        }
        if (modalOuverte) {
            setTimeout(actualiser, 5000);
            return;
        }
        fetch(currentUrl)
            .then(function(r){ return r.text(); })
            .then(function(html){
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newCard = doc.querySelector('.table-card');
                var oldCard = document.querySelector('.table-card');
                if (newCard && oldCard) {
                    oldCard.innerHTML = newCard.innerHTML;
                }
                setTimeout(actualiser, 5000);
            })
            .catch(function(){ setTimeout(actualiser, 5000); });
    }
    setTimeout(actualiser, 5000);
})();
</script>
</body>
</html>