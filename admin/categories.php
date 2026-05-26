<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Gestion Catégories';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'categories';

// Inclusion de la classe Categorie
require_once __DIR__ . '/../classes/Categorie.php';
// Instanciation de l'objet Categorie
$categorieObj = new Categorie();

// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'action à effectuer
    $action = $_POST['action'] ?? '';
    // Si l'action est de supprimer une catégorie
    if ($action === 'supprimer') {
        // Récupération et conversion de l'identifiant de la catégorie
        $id = intval($_POST['id'] ?? 0);
        // Si l'identifiant est valide, suppression et message de confirmation
        if ($id) { $categorieObj->supprimer($id); $_SESSION['success'] = 'Catégorie supprimée.'; }
    // Si l'action est d'ajouter une catégorie
    } elseif ($action === 'ajouter') {
        // Récupération et sécurisation du nom et de la description
        $nom = securiser($_POST['nom'] ?? '');
        $description = securiser($_POST['description'] ?? '');
        // Si le nom est fourni, ajout et message de confirmation
        if ($nom) { $categorieObj->ajouter($nom, $description); $_SESSION['success'] = 'Catégorie ajoutée.'; }
    }
    // Redirection vers la page de gestion des catégories après le traitement
    redirect(BASE_URL . '/admin/categories.php');
}
// Récupération de toutes les catégories avec le comptage de produits associés
$categories = $categorieObj->getWithProduitCount();
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Gestion</div>
        <h1 class="dash-page-title">Catégories</h1>
        <p class="dash-page-sub">Gérez les catégories de produits</p>
    </div>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Toutes les catégories (<?= count($categories) ?>)</span>
            <button class="btn btn-dark btn-sm" onclick="document.getElementById('modalCat').style.display='flex'"><i class="fas fa-plus"></i> Ajouter une catégorie</button>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Nom</th><th>Description</th><th>Nb produits</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
            <!-- Boucle d'affichage de chaque catégorie dans une ligne du tableau -->
            <tr>
                <td class="text-xs text-muted">#<?= $cat['id'] ?></td>
                <td class="text-sm font-semibold"><?= securiser($cat['nom']) ?></td>
                <td class="text-sm text-muted"><?= securiser(substr($cat['description']??'—',0,60)) ?></td>
                <td><span class="badge badge-dark"><?= $cat['nb_produits'] ?? 0 ?> produits</span></td>
                <td><div class="flex gap-1">
                    <!-- Lien vers la page de modification de la catégorie -->
                    <a href="<?= BASE_URL ?>/admin/categories/modifier.php?id=<?= $cat['id'] ?>" class="action-btn"><i class="fas fa-edit"></i></a>
                    <!-- Formulaire de suppression de la catégorie avec confirmation -->
                    <form method="POST" action="<?= BASE_URL ?>/admin/categories.php" style="display:inline;" onsubmit="return confirm('Supprimer cette catégorie ?');"><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $cat['id'] ?>"><button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button></form>
                </div></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Modal d'ajout d'une catégorie -->
    <div id="modalCat" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal-box">
            <button class="modal-close" onclick="document.getElementById('modalCat').style.display='none'">✕</button>
            <h2 class="modal-title">Ajouter une catégorie</h2>
            <form method="POST" action="<?= BASE_URL ?>/admin/categories.php">
                <input type="hidden" name="action" value="ajouter">
                <div class="form-group"><label>Nom</label><input type="text" name="nom" class="form-control" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                <div class="flex gap-2 justify-between" style="margin-top:20px;">
                    <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalCat').style.display='none'">Annuler</button>
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
