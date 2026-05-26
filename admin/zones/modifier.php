<?php
// Inclusion du fichier de configuration principal (remonte de 2 niveaux jusqu'à la racine)
require_once __DIR__ . '/../../config/config.php';
// Inclusion de la classe ZoneLivraison
require_once __DIR__ . '/../../classes/ZoneLivraison.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Récupération et conversion de l'identifiant de la zone depuis l'URL
$id = intval($_GET['id'] ?? 0);
// Instanciation de l'objet ZoneLivraison
$zoneObj = new ZoneLivraison();
// Récupération des données de la zone par son identifiant
$zone = $zoneObj->getById($id);

// Si la zone n'existe pas, enregistrement d'un message d'erreur et redirection
if (!$zone) { $_SESSION['error'] = 'Zone introuvable.'; redirect(BASE_URL . '/admin/zones.php'); }

// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et sécurisation du nom, de la description et du tarif
    $nom = securiser($_POST['nom'] ?? '');
    $description = securiser($_POST['description'] ?? '');
    $tarif = floatval($_POST['tarif'] ?? 0);
    // Si le nom et le tarif sont valides, modification de la zone, sinon message d'erreur
    if ($nom && $tarif >= 0) {
        $zoneObj->modifier($id, $nom, $description, $tarif);
        $_SESSION['success'] = 'Zone modifiée avec succès.';
    } else {
        $_SESSION['error'] = 'Veuillez remplir tous les champs requis.';
    }
    redirect(BASE_URL . '/admin/zones.php');
}

// Définition du titre de la page
$pageTitle = 'Modifier une zone';
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../../includes/header.php';
// Définition de la page active pour le menu d'administration
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
