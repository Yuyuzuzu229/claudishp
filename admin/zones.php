<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';
// Inclusion de la classe ZoneLivraison
require_once __DIR__ . '/../classes/ZoneLivraison.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Instanciation de l'objet ZoneLivraison
$zoneObj = new ZoneLivraison();

// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'action à effectuer
    $action = $_POST['action'] ?? '';
    // Si l'action est d'ajouter une zone
    if ($action === 'ajouter') {
        $nom = securiser($_POST['nom'] ?? '');
        $description = securiser($_POST['description'] ?? '');
        $tarif = floatval($_POST['tarif'] ?? 0);
        // Si le nom et le tarif sont valides, ajout de la zone, sinon message d'erreur
        if ($nom && $tarif >= 0) {
            $zoneObj->ajouter($nom, $description, $tarif);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Zone ajoutée avec succès.'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Veuillez remplir tous les champs requis.'];
        }
    // Si l'action est de supprimer une zone
    } elseif ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $zoneObj->supprimer($id);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Zone supprimée.'];
        }
    // Si l'action est d'activer/désactiver une zone
    } elseif ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $zoneObj->toggleStatut($id);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Statut mis à jour.'];
        }
    }
    // Redirection vers la page de gestion des zones après le traitement
    redirect(BASE_URL . '/admin/zones.php');
}

// Définition du titre de la page
$pageTitle = 'Zones de livraison';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'zones';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Logistique</div>
        <h1 class="dash-page-title">Zones de livraison</h1>
        <p class="dash-page-sub">Gérez les zones et tarifs de livraison</p>
    </div>
    <!-- Affichage des messages flash (succès ou erreur) -->
    <?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= securiser($_SESSION['flash']['message']) ?></div>
    <?php unset($_SESSION['flash']); endif; ?>
    <?php
    // Récupération de toutes les zones de livraison
    $zones = $zoneObj->getAll();
    ?>
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Zones de livraison (<?= count($zones) ?>)</span>
            <button class="btn btn-dark btn-sm" onclick="document.getElementById('modalZone').style.display='flex'"><i class="fas fa-plus"></i> Ajouter une zone</button>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Zone</th><th>Description</th><th>Tarif (FCFA)</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($zones)): ?>
            <!-- Si aucune zone n'est trouvée, affichage d'un message par défaut -->
            <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-400);">Aucune zone configurée.</td></tr>
            <?php else: foreach ($zones as $z): ?>
            <!-- Boucle d'affichage de chaque zone dans une ligne du tableau -->
            <tr>
                <td class="text-xs text-muted">#<?= $z['id'] ?></td>
                <td class="text-sm font-semibold"><?= securiser($z['nom']) ?></td>
                <td class="text-sm text-muted"><?= securiser(substr($z['description']??'—',0,50)) ?></td>
                <td class="text-sm font-semibold"><?= number_format($z['tarif']??0,0,',',' ') ?> FCFA</td>
                <td><?= $z['statut']??true ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-dark">Inactive</span>' ?></td>
                <td><div class="flex gap-1">
                    <!-- Lien vers la page de modification de la zone -->
                    <a href="<?= BASE_URL ?>/admin/zones/modifier.php?id=<?= $z['id'] ?>" class="action-btn" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
                    <!-- Formulaire d'activation/désactivation de la zone -->
                    <form method="POST" action="<?= BASE_URL ?>/admin/zones.php" style="display:inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $z['id'] ?>">
                        <button type="submit" class="action-btn" title="Activer/Désactiver"><i class="fas fa-<?= $z['statut'] ? 'pause' : 'play' ?>"></i></button>
                    </form>
                    <!-- Formulaire de suppression de la zone avec confirmation -->
                    <form method="POST" action="<?= BASE_URL ?>/admin/zones.php" style="display:inline;" onsubmit="return confirm('Supprimer cette zone ?');">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $z['id'] ?>">
                        <button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Modal d'ajout d'une zone de livraison -->
    <div id="modalZone" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal-box">
            <button class="modal-close" onclick="document.getElementById('modalZone').style.display='none'">✕</button>
            <h2 class="modal-title">Ajouter une zone</h2>
            <form method="POST" action="<?= BASE_URL ?>/admin/zones.php">
                <input type="hidden" name="action" value="ajouter">
                <div class="form-group"><label>Nom de la zone</label><input type="text" name="nom" class="form-control" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label>Tarif (FCFA)</label><input type="number" name="tarif" class="form-control" required></div>
                <div class="flex gap-2 justify-between" style="margin-top:20px;">
                    <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalZone').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-dark">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>