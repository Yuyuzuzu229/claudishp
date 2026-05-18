<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$id = intval($_GET['id'] ?? 0);

// Get livreur from DB
$pdo = getPdo();
$stmt = $pdo->prepare("SELECT * FROM livreur WHERE id = ?");
$stmt->execute([$id]);
$livreur = $stmt->fetch();

if (!$livreur) { $_SESSION['error'] = 'Livreur introuvable.'; redirect(BASE_URL . '/admin/livreurs.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = securiser($_POST['nom'] ?? '');
    $telephone = normaliserTelephone($_POST['telephone'] ?? '');
    $email = securiser($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    $photo = $livreur['photo'];
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $nomFichier = 'livreur_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = UPLOADS_DIR . '/livreurs/' . $nomFichier;
        if (!is_dir(UPLOADS_DIR . '/livreurs')) mkdir(UPLOADS_DIR . '/livreurs', 0777, true);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photo = 'livreurs/' . $nomFichier;
        }
    }

    if (!empty($motDePasse)) {
        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, mot_de_passe=?, photo=? WHERE id=?");
        $ok = $stmt->execute([$nom, $telephone, $email, $hash, $photo, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE livreur SET nom=?, telephone=?, email=?, photo=? WHERE id=?");
        $ok = $stmt->execute([$nom, $telephone, $email, $photo, $id]);
    }

    if ($ok) {
        $_SESSION['success'] = "Livreur « $nom » modifié.";
    } else {
        $_SESSION['error'] = 'Erreur lors de la modification.';
    }
    redirect(BASE_URL . '/admin/livreurs.php');
}

$pageTitle = 'Modifier un livreur';
require_once __DIR__ . '/../../includes/header.php';
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

    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>

    <div class="table-card" style="max-width:600px;">
        <div class="table-card-header"><span class="table-card-title">Informations du livreur</span></div>
        <div style="padding:20px;">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group"><label>Nom complet *</label><input type="text" name="nom" class="form-control" value="<?= securiser($livreur['nom']) ?>" required></div>
                <div class="grid-2" style="gap:12px;margin-top:14px;">
                    <div class="form-group" style="margin-bottom:0;"><label>Téléphone *</label><input type="tel" name="telephone" class="form-control" value="<?= securiser($livreur['telephone']) ?>" required></div>
                    <div class="form-group" style="margin-bottom:0;"><label>Email</label><input type="email" name="email" class="form-control" value="<?= securiser($livreur['email'] ?? '') ?>"></div>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>Nouveau mot de passe <span class="text-xs text-muted">(laisser vide pour conserver)</span></label>
                    <input type="password" name="mot_de_passe" class="form-control" placeholder="Min. 6 caractères" minlength="6">
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <?php if ($livreur['photo']): ?><div style="margin-top:8px;"><img src="<?= UPLOADS_URL . '/' . securiser($livreur['photo']) ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;"></div><?php endif; ?>
                </div>
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
