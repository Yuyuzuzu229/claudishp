<?php
// Inclusion du fichier de configuration principal (remonte de 2 niveaux jusqu'à la racine)
require_once __DIR__ . '/../../config/config.php';
// Inclusion de la classe Produit
require_once __DIR__ . '/../../classes/Produit.php';
// Inclusion de la classe Categorie
require_once __DIR__ . '/../../classes/Categorie.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Récupération et conversion de l'identifiant du produit depuis l'URL
$id = intval($_GET['id'] ?? 0);
// Instanciation des objets Produit et Categorie
$produitObj = new Produit();
$categorieObj = new Categorie();
// Récupération des données du produit par son identifiant
$produit = $produitObj->getById($id);
// Récupération de toutes les catégories
$categories = $categorieObj->getAll();

// Si le produit n'existe pas, enregistrement d'un message d'erreur et redirection
if (!$produit) { $_SESSION['error'] = 'Produit introuvable.'; redirect(BASE_URL . '/admin/produits.php'); }

// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et sécurisation des champs du formulaire
    $nom = securiser($_POST['nom'] ?? '');
    $prix = floatval($_POST['prix'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categorieId = intval($_POST['categorie_id'] ?? 0);
    $description = securiser($_POST['description'] ?? '');
    $taille = securiser($_POST['taille'] ?? '');
    $soldePrix = !empty($_POST['solde_prix']) ? floatval($_POST['solde_prix']) : null;

    // Upload de la photo du produit
    $photo = null;
    // Vérification si un fichier a été téléchargé sans erreur
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $nomFichier = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = UPLOADS_DIR . '/produits/' . $nomFichier;
        // Création du répertoire de destination s'il n'existe pas
        if (!is_dir(UPLOADS_DIR . '/produits')) mkdir(UPLOADS_DIR . '/produits', 0777, true);
        // Déplacement du fichier téléchargé vers le répertoire de destination
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photo = 'produits/' . $nomFichier;
        }
    }

    // Si le nom et le prix sont valides, modification du produit, sinon message d'erreur
    if ($nom && $prix > 0) {
        $produitObj->modifier($id, $nom, $description, $prix, $stock, $categorieId, $photo, $taille, null, null, $soldePrix);
        $_SESSION['success'] = 'Produit modifié avec succès.';
    } else {
        $_SESSION['error'] = 'Veuillez remplir tous les champs requis.';
    }
    redirect(BASE_URL . '/admin/produits.php');
}

// Définition du titre de la page
$pageTitle = 'Modifier un produit';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'produits';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Gestion</div>
        <h1 class="dash-page-title">Modifier un produit</h1>
        <div class="flex gap-2" style="margin-top:4px;">
            <a href="<?= BASE_URL ?>/admin/produits.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <!-- Affichage des messages d'erreur ou de succès éventuels -->
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <div class="table-card" style="max-width:600px;">
        <div class="table-card-header"><span class="table-card-title">Informations du produit</span></div>
        <div style="padding:20px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group"><label>Nom du produit *</label><input type="text" name="nom" class="form-control" value="<?= securiser($produit['nom']) ?>" required></div>
                <div class="grid-2" style="gap:12px;">
                    <div class="form-group" style="margin-bottom:0;"><label>Prix (FCFA) *</label><input type="number" name="prix" class="form-control" value="<?= $produit['prix'] ?>" required></div>
                    <div class="form-group" style="margin-bottom:0;"><label>Stock *</label><input type="number" name="stock" class="form-control" value="<?= $produit['stock'] ?>" required></div>
                </div>
                <div class="form-group" style="margin-top:14px;"><label>Prix soldé (FCFA) <span class="text-muted">(laisser vide si pas de solde)</span></label><input type="number" name="solde_prix" class="form-control" value="<?= $produit['solde_prix'] ?? '' ?>" placeholder="Ex: 15000"></div>
                <div class="form-group" style="margin-top:14px;"><label>Catégorie</label><select name="categorie_id" class="form-control"><option value="">Sélectionner...</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>" <?= $cat['id'] == $produit['categorie_id'] ? 'selected' : '' ?>><?= securiser($cat['nom']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Taille(s) disponibles <span class="text-muted">(optionnel)</span></label><input type="text" name="taille" class="form-control" value="<?= securiser($produit['taille_disponible'] ?? '') ?>" placeholder="Ex: XS, S, M, L, XL — laisser vide pour les accessoires"></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3"><?= securiser($produit['description'] ?? '') ?></textarea></div>
                <div class="form-group">
                    <label>Photo</label>
                    <div>
                        <label for="photoProduitModifInput" class="btn btn-dark" style="cursor:pointer;margin-bottom:0;">Choisir un fichier</label>
                        <span id="photoProduitModifFileName" style="margin-left:10px;font-size:.8rem;color:#888;"><?= $produit['photo'] ? basename($produit['photo']) : 'Aucune image' ?></span>
                    </div>
                    <input type="file" name="photo" id="photoProduitModifInput" accept="image/*" style="position:absolute;left:-9999px;opacity:0;width:1px;height:1px;">
                    <?php if ($produit['photo']): ?><div style="margin-top:8px;"><img src="<?= UPLOADS_URL . '/' . securiser($produit['photo']) ?>" alt="" style="width:60px;height:60px;border-radius:6px;object-fit:cover;"></div><?php endif; ?>
                </div>
                <script>
                <!-- Écouteur d'événement pour afficher le nom du fichier sélectionné pour la photo -->
                document.getElementById('photoProduitModifInput').addEventListener('change', function(e) {
                    var span = document.getElementById('photoProduitModifFileName');
                    if (span && this.files && this.files[0]) span.textContent = this.files[0].name;
                });
                </script>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <a href="<?= BASE_URL ?>/admin/produits.php" class="btn btn-outline-dark">Annuler</a>
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