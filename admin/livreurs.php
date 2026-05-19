<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Livreur.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

$livreurObj = new Livreur();

// ─── TRAITEMENT POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $nom = securiser($_POST['nom'] ?? '');
        $telephone = normaliserTelephone($_POST['telephone'] ?? '');
        $email = securiser($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';

        if (empty($motDePasse)) {
            $_SESSION['error'] = 'Le mot de passe est requis.';
            redirect(BASE_URL . '/admin/livreurs.php');
        }

        // Upload photo
        $photo = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $nomFichier = 'livreur_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = UPLOADS_DIR . '/livreurs/' . $nomFichier;
            if (!is_dir(UPLOADS_DIR . '/livreurs')) mkdir(UPLOADS_DIR . '/livreurs', 0777, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photo = 'livreurs/' . $nomFichier;
            }
        }

        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
        $pdo = getPdo();
        $stmt = $pdo->prepare("INSERT INTO livreur (nom, telephone, email, mot_de_passe, photo, statut, est_actif) VALUES (?, ?, ?, ?, ?, 'Disponible', 1)");
        $ok = $stmt->execute([$nom, $telephone, $email, $hash, $photo]);

        if ($ok) {
            $_SESSION['success'] = "Livreur « $nom » ajouté.";
        } else {
            $_SESSION['error'] = 'Erreur lors de l\'ajout.';
        }
        redirect(BASE_URL . '/admin/livreurs.php');
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id']);
        $livreurObj->toggleActif($id);
        redirect(BASE_URL . '/admin/livreurs.php');
    }

    if ($action === 'supprimer') {
        $id = intval($_POST['id']);
        $livreurObj->supprimer($id);
        $_SESSION['success'] = 'Livreur supprimé.';
        redirect(BASE_URL . '/admin/livreurs.php');
    }
}

$pageTitle = 'Gestion Livreurs';
$search = isset($_GET['q']) ? securiser($_GET['q']) : '';
if ($search) {
    $livreurs = $livreurObj->search($search);
} else {
    $livreurs = $livreurObj->getAll();
}
require_once __DIR__ . '/../includes/header.php';
$adminPage = 'livreurs';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Logistique</div>
        <h1 class="dash-page-title">Livreurs</h1>
        <p class="dash-page-sub">Gérez votre équipe de livreurs</p>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Livreurs (<?= count($livreurs) ?>)</span>
            <button class="btn btn-dark btn-sm" onclick="document.getElementById('modalLivreur').style.display='flex'"><i class="fas fa-plus"></i> Ajouter un livreur</button>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Livreur</th><th>Téléphone</th><th>Email</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($livreurs)): ?>
            <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun livreur enregistré.</td></tr>
            <?php else: foreach ($livreurs as $l): ?>
            <tr>
                <td class="text-xs text-muted">#<?= $l['id'] ?></td>
                <td>
                    <div class="flex items-center gap-2">
                        <?php if (!empty($l['photo'])): ?>
                        <img src="<?= UPLOADS_URL . '/' . securiser($l['photo']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--gray-200);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;"><?= strtoupper(substr($l['nom']??'?',0,2)) ?></div>
                        <?php endif; ?>
                        <span class="text-sm font-semibold"><?= securiser($l['nom']) ?></span>
                    </div>
                </td>
                <td class="text-sm"><?= securiser($l['telephone'] ?? '—') ?></td>
                <td class="text-sm text-muted"><?= securiser($l['email'] ?? '—') ?></td>
                <td>
                    <?php if ($l['statut'] === 'Disponible'): ?>
                    <span class="badge badge-success">Disponible</span>
                    <?php elseif ($l['statut'] === 'En livraison'): ?>
                    <span class="badge badge-warning">En livraison</span>
                    <?php else: ?>
                    <span class="badge badge-dark"><?= securiser($l['statut']) ?></span>
                    <?php endif; ?>
                    <?php if (!$l['est_actif']): ?>
                    <span class="badge badge-danger">Inactif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="flex gap-1">
                        <a href="<?= BASE_URL ?>/admin/livreurs/modifier.php?id=<?= $l['id'] ?>" class="action-btn" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button type="submit" class="action-btn" title="<?= $l['est_actif'] ? 'Désactiver' : 'Activer' ?>">
                                <i class="fas <?= $l['est_actif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce livreur ?')">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button type="submit" class="action-btn danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- MODAL AJOUT -->
    <div id="modalLivreur" class="modal-overlay" style="display:none;" onclick="if(event.target===this)this.style.display='none'">
        <div class="modal-box">
            <button class="modal-close" onclick="document.getElementById('modalLivreur').style.display='none'">✕</button>
            <h2 class="modal-title">Ajouter un livreur</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ajouter">
                <div class="form-group">
                    <label>Nom complet *</label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                <div class="grid-2" style="gap:12px;margin-top:14px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Téléphone *</label>
                        <input type="tel" name="telephone" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>Mot de passe *</label>
                    <input type="password" name="mot_de_passe" class="form-control" required placeholder="Min. 6 caractères" minlength="6">
                </div>
                <div class="form-group" style="margin-top:14px;">
                    <label>Photo</label>
                    <div>
                        <label for="photoLivreurInput" class="btn btn-dark" style="cursor:pointer;margin-bottom:0;">Choisir un fichier</label>
                        <span id="photoLivreurFileName" style="margin-left:10px;font-size:.8rem;color:#888;">Aucune image</span>
                    </div>
                    <input type="file" name="photo" id="photoLivreurInput" accept="image/*" style="position:absolute;left:-9999px;opacity:0;width:1px;height:1px;">
                </div>
                <script>
                document.getElementById('photoLivreurInput').addEventListener('change', function(e) {
                    var span = document.getElementById('photoLivreurFileName');
                    if (span && this.files && this.files[0]) span.textContent = this.files[0].name;
                });
                </script>
                <div class="flex gap-2 justify-between" style="margin-top:20px;">
                    <button type="button" class="btn btn-outline-dark" onclick="document.getElementById('modalLivreur').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-dark">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body>
</html>
