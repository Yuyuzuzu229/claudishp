<?php
// Inclusion du fichier de configuration principal (remonte de 2 niveaux jusqu'à la racine)
require_once __DIR__ . '/../../config/config.php';
// Inclusion de la classe Categorie
require_once __DIR__ . '/../../classes/Categorie.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Récupération et conversion de l'identifiant de la catégorie depuis l'URL
$id = intval($_GET['id'] ?? 0);
// Instanciation de l'objet Categorie
$categorieObj = new Categorie();
// Récupération des données de la catégorie par son identifiant
$categorie = $categorieObj->getById($id);

// Si la catégorie n'existe pas, enregistrement d'un message d'erreur et redirection
if (!$categorie) { $_SESSION['error'] = 'Catégorie introuvable.'; redirect(BASE_URL . '/admin/categories.php'); }

// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et sécurisation du nom et de la description
    $nom = securiser($_POST['nom'] ?? '');
    $description = securiser($_POST['description'] ?? '');
    // Si le nom est fourni, modification de la catégorie, sinon message d'erreur
    if ($nom) {
        $categorieObj->modifier($id, $nom, $description);
        $_SESSION['success'] = 'Catégorie modifiée avec succès.';
    } else {
        $_SESSION['error'] = 'Le nom est requis.';
    }
    // Redirection vers la page de gestion des catégories après le traitement
    redirect(BASE_URL . '/admin/categories.php');
}

// Définition du titre de la page
$pageTitle = 'Modifier une catégorie';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'categories';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Gestion</div>
        <h1 class="dash-page-title">Modifier une catégorie</h1>
        <div class="flex gap-2" style="margin-top:4px;">
            <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <div class="table-card" style="max-width:600px;">
        <div class="table-card-header"><span class="table-card-title">Informations de la catégorie</span></div>
        <div style="padding:20px;">
            <form method="POST">
                <div class="form-group"><label>Nom *</label><input type="text" name="nom" class="form-control" value="<?= securiser($categorie['nom']) ?>" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= securiser($categorie['description'] ?? '') ?></textarea></div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-outline-dark">Annuler</a>
                    <button type="submit" class="btn btn-dark">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
