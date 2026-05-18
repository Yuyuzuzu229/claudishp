<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/ZoneLivraison.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$id = intval($_GET['id'] ?? 0);
$zoneObj = new ZoneLivraison();
$zone = $zoneObj->getById($id);

if (!$zone) { $_SESSION['error'] = 'Zone introuvable.'; redirect(BASE_URL . '/admin/zones.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = securiser($_POST['nom'] ?? '');
    $description = securiser($_POST['description'] ?? '');
    $tarif = floatval($_POST['tarif'] ?? 0);
    if ($nom && $tarif >= 0) {
        $zoneObj->modifier($id, $nom, $description, $tarif);
        $_SESSION['success'] = 'Zone modifiée avec succès.';
    } else {
        $_SESSION['error'] = 'Veuillez remplir tous les champs requis.';
    }
    redirect(BASE_URL . '/admin/zones.php');
}

$pageTitle = 'Modifier une zone';
require_once __DIR__ . '/../../includes/header.php';
$adminPage = 'zones';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Logistique</div>
        <h1 class="dash-page-title">Modifier une zone</h1>
        <div class="flex gap-2" style="margin-top:4px;">
            <a href="<?= BASE_URL ?>/admin/zones.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <div class="table-card" style="max-width:600px;">
        <div class="table-card-header"><span class="table-card-title">Informations de la zone</span></div>
        <div style="padding:20px;">
            <form method="POST">
                <div class="form-group"><label>Nom de la zone *</label><input type="text" name="nom" class="form-control" value="<?= securiser($zone['nom']) ?>" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"><?= securiser($zone['description'] ?? '') ?></textarea></div>
                <div class="form-group"><label>Tarif (FCFA) *</label><input type="number" name="tarif" class="form-control" value="<?= $zone['tarif'] ?>" required></div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <a href="<?= BASE_URL ?>/admin/zones.php" class="btn btn-outline-dark">Annuler</a>
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
