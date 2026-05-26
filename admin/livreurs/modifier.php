<?php
// Inclusion du fichier de configuration principal (remonte de 2 niveaux jusqu'à la racine)
require_once __DIR__ . '/../../config/config.php';
// Inclusion du fichier de connexion à la base de données
require_once __DIR__ . '/../../config/database.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Récupération et conversion de l'identifiant du livreur depuis l'URL
$id = intval($_GET['id'] ?? 0);

// Récupération du livreur depuis la base de données
$pdo = getPdo();
// Requête préparée pour sélectionner le livreur par son identifiant
$stmt = $pdo->prepare("SELECT * FROM livreur WHERE id = ?");
$stmt->execute([$id]);
$livreur = $stmt->fetch();

// Si le livreur n'existe pas, enregistrement d'un message d'erreur et redirection
if (!$livreur) { $_SESSION['error'] = 'Livreur introuvable.'; redirect(BASE_URL . '/admin/livreurs.php'); }

// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et sécurisation des champs du formulaire
    $nom = securiser($_POST['nom'] ?? '');
    $telephone = normaliserTelephone($_POST['telephone'] ?? '');
    if (empty($telephone)) { $_SESSION['error'] = 'Numéro de téléphone invalide.'; redirect(BASE_URL . '/admin/livreurs/modifier.php?id=' . $id); }

    // Vérifier que le téléphone n'est pas déjà utilisé par un autre livreur
    $stmtTel = $pdo->prepare("SELECT id FROM livreur WHERE telephone = ? AND id != ?");
    $stmtTel->execute([$telephone, $id]);
    if ($stmtTel->fetch()) {
        $_SESSION['error'] = 'Ce numéro de téléphone est déjà attribué à un autre livreur.';
        redirect(BASE_URL . '/admin/livreurs/modifier.php?id=' . $id);
    }

    $email = securiser($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    // Gestion de la photo : conservation de l'ancienne par défaut
    $photo = $livreur['photo'];
    // Vérification si un nouveau fichier photo a été téléchargé sans erreur
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $nomFichier = 'livreur_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = UPLOADS_DIR . '/livreurs/' . $nomFichier;
        // Création du répertoire de destination s'il n'existe pas
        if (!is_dir(UPLOADS_DIR . '/livreurs')) mkdir(UPLOADS_DIR . '/livreurs', 0777, true);
        // Déplacement du fichier téléchargé vers le répertoire de destination
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photo = 'livreurs/' . $nomFichier;
        }
    }

    // Si un nouveau mot de passe est fourni, mise à jour avec hachage, sinon mise à jour sans mot de passe
    if (!empty($motDePasse)) {
        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, mot_de_passe=?, photo=? WHERE id=?");
        $ok = $stmt->execute([$nom, $telephone, $email, $hash, $photo, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, photo=? WHERE id=?");
        $ok = $stmt->execute([$nom, $telephone, $email, $photo, $id]);
    }

    // Si la mise à jour a réussi, message de confirmation, sinon message d'erreur
    if ($ok) {
        $_SESSION['success'] = "Livreur « $nom » modifié.";
    } else {
        $_SESSION['error'] = 'Erreur lors de la modification.';
    }
    redirect(BASE_URL . '/admin/livreurs.php');
}

// Définition du titre de la page
$pageTitle = 'Modifier un livreur';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'livreurs';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Logistique</div>
        <h1 class="dash-page-title">Modifier un livreur</h1>
        <div class="flex gap-2" style="margin-top:4px;">
            <a href="<?= BASE_URL ?>/admin/livreurs.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <!-- Affichage des messages d'erreur ou de succès éventuels -->
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <div class="table-card" style="max-width:600px;">
        <div class="table-card-header"><span class="table-card-title">Informations du livreur</span></div>
        <div style="padding:20px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group"><label>Nom complet *</label><input type="text" name="nom" class="form-control" value="<?= securiser($livreur['nom']) ?>" required></div>
                <div class="grid-2" style="gap:12px;margin-top:14px;">
                    <div class="form-group" style="margin-bottom:0;"><label>Téléphone *</label><input type="tel" name="telephone" class="form-control" value="<?= securiser(normaliserTelephone($livreur['telephone'])) ?>" required pattern="[+]229 01[0-9\s]{8,}" inputmode="numeric" title="Format: +229 01 XX XX XX XX"></div>
                    <div class="form-group" style="margin-bottom:0;"><label>Email</label><input type="email" name="email" class="form-control" value="<?= securiser($livreur['email'] ?? '') ?>"></div>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>Nouveau mot de passe <span class="text-xs text-muted">(laisser vide pour conserver)</span></label>
                    <input type="password" name="mot_de_passe" class="form-control" placeholder="Min. 6 caractères" minlength="6">
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>Photo</label>
                    <div>
                        <label for="photoLivreurModifInput" class="btn btn-dark" style="cursor:pointer;margin-bottom:0;">Choisir un fichier</label>
                        <span id="photoLivreurModifFileName" style="margin-left:10px;font-size:.8rem;color:#888;"><?= $livreur['photo'] ? basename($livreur['photo']) : 'Aucune image' ?></span>
                    </div>
                    <input type="file" name="photo" id="photoLivreurModifInput" accept="image/*" style="position:absolute;left:-9999px;opacity:0;width:1px;height:1px;">
                    <?php if ($livreur['photo']): ?><div style="margin-top:8px;"><img src="<?= UPLOADS_URL . '/' . securiser($livreur['photo']) ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;"></div><?php endif; ?>
                </div>
                <script>
                <!-- Écouteur d'événement pour afficher le nom du fichier sélectionné pour la photo -->
                document.getElementById('photoLivreurModifInput').addEventListener('change', function(e) {
                    var span = document.getElementById('photoLivreurModifFileName');
                    if (span && this.files && this.files[0]) span.textContent = this.files[0].name;
                });
                </script>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <a href="<?= BASE_URL ?>/admin/livreurs.php" class="btn btn-outline-dark">Annuler</a>
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