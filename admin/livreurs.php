<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Livreur
require_once __DIR__ . '/../classes/Livreur.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Instanciation de l'objet Livreur
$livreurObj = new Livreur();

// ─── TRAITEMENT POST ──────────────────────────────────────────────
// Vérification si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'action à effectuer
    $action = $_POST['action'] ?? '';

    // Si l'action est d'ajouter un livreur
    if ($action === 'ajouter') {
        // Récupération et sécurisation des champs du formulaire
        $nom = securiser($_POST['nom'] ?? '');
        $telephone = normaliserTelephone($_POST['telephone'] ?? '');
        $email = securiser($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';

        // Vérification que le mot de passe est fourni
        if (empty($motDePasse)) {
            $_SESSION['error'] = 'Le mot de passe est requis.';
            redirect(BASE_URL . '/admin/livreurs.php');
        }

        // Vérifier que le téléphone n'est pas déjà utilisé
        $pdo = getPdo();
        $stmtTel = $pdo->prepare("SELECT id FROM livreur WHERE telephone = ?");
        $stmtTel->execute([$telephone]);
        if ($stmtTel->fetch()) {
            $_SESSION['error'] = 'Ce numéro de téléphone est déjà attribué à un autre livreur.';
            redirect(BASE_URL . '/admin/livreurs.php');
        }

        // Upload de la photo du livreur
        $photo = null;
        // Vérification si un fichier a été téléchargé sans erreur
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            // Récupération de l'extension du fichier
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            // Génération d'un nom de fichier unique avec timestamp et bytes aléatoires
            $nomFichier = 'livreur_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = UPLOADS_DIR . '/livreurs/' . $nomFichier;
            // Création du répertoire de destination s'il n'existe pas
            if (!is_dir(UPLOADS_DIR . '/livreurs')) mkdir(UPLOADS_DIR . '/livreurs', 0777, true);
            // Déplacement du fichier téléchargé vers le répertoire de destination
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photo = 'livreurs/' . $nomFichier;
            }
        }

        // Hachage du mot de passe pour le stockage sécurisé en base de données
        $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
        // Connexion à la base de données
        $pdo = getPdo();
        // Requête préparée d'insertion d'un nouveau livreur
        $stmt = $pdo->prepare("INSERT INTO livreur (nom, telephone, email, mot_de_passe, photo, statut, est_actif) VALUES (?, ?, ?, ?, ?, 'Disponible', 1)");
        $ok = $stmt->execute([$nom, $telephone, $email, $hash, $photo]);

        // Si l'insertion a réussi, message de confirmation, sinon message d'erreur
        if ($ok) {
            $_SESSION['success'] = "Livreur « $nom » ajouté.";
        } else {
            $_SESSION['error'] = 'Erreur lors de l\'ajout.';
        }
        redirect(BASE_URL . '/admin/livreurs.php');
    }

    // Si l'action est d'activer/désactiver un livreur
    if ($action === 'toggle') {
        $id = intval($_POST['id']);
        $livreurObj->toggleActif($id);
        redirect(BASE_URL . '/admin/livreurs.php');
    }

    // Si l'action est de supprimer un livreur
    if ($action === 'supprimer') {
        $id = intval($_POST['id']);
        $livreurObj->supprimer($id);
        $_SESSION['success'] = 'Livreur supprimé.';
        redirect(BASE_URL . '/admin/livreurs.php');
    }
}

// Définition du titre de la page
$pageTitle = 'Gestion Livreurs';
// Récupération du terme de recherche depuis l'URL, ou chaîne vide par défaut
$search = isset($_GET['q']) ? securiser($_GET['q']) : '';
// Si un terme de recherche est fourni, recherche des livreurs, sinon récupération de tous les livreurs
if ($search) {
    $livreurs = $livreurObj->search($search);
} else {
    $livreurs = $livreurObj->getAll();
}
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
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
            <!-- Si aucun livreur n'est trouvé, affichage d'un message par défaut -->
            <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun livreur enregistré.</td></tr>
            <?php else: foreach ($livreurs as $l): ?>
            <!-- Boucle d'affichage de chaque livreur dans une ligne du tableau -->
            <tr>
                <td class="text-xs text-muted">#<?= $l['id'] ?></td>
                <td>
                    <div class="flex items-center gap-2">
                        <?php if (!empty($l['photo'])): ?>
                        <!-- Affichage de la photo du livreur si elle existe -->
                        <img src="<?= UPLOADS_URL . '/' . securiser($l['photo']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                        <!-- Affichage des initiales si aucune photo n'est disponible -->
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--gray-200);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;"><?= strtoupper(substr($l['nom']??'?',0,2)) ?></div>
                        <?php endif; ?>
                        <span class="text-sm font-semibold"><?= securiser($l['nom']) ?></span>
                    </div>
                </td>
                <td class="text-sm"><?= securiser($l['telephone'] ?? '—') ?></td>
                <td class="text-sm text-muted"><?= securiser($l['email'] ?? '—') ?></td>
                <td>
                    <!-- Affichage du statut du livreur avec le badge approprié -->
                    <?php if ($l['statut'] === 'Disponible'): ?>
                    <span class="badge badge-success">Disponible</span>
                    <?php elseif ($l['statut'] === 'En livraison'): ?>
                    <span class="badge badge-warning">En livraison</span>
                    <?php else: ?>
                    <span class="badge badge-dark"><?= securiser($l['statut']) ?></span>
                    <?php endif; ?>
                    <?php if (!$l['est_actif']): ?>
                    <!-- Affichage d'un badge Inactif si le livreur est désactivé -->
                    <span class="badge badge-danger">Inactif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="flex gap-1">
                        <!-- Lien vers la page de modification du livreur -->
                        <a href="<?= BASE_URL ?>/admin/livreurs/modifier.php?id=<?= $l['id'] ?>" class="action-btn" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
                        <!-- Formulaire d'activation/désactivation du livreur -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button type="submit" class="action-btn" title="<?= $l['est_actif'] ? 'Désactiver' : 'Activer' ?>">
                                <i class="fas <?= $l['est_actif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                            </button>
                        </form>
                        <!-- Formulaire de suppression du livreur avec confirmation -->
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

    <!-- MODAL AJOUT : Fenêtre modale pour l'ajout d'un nouveau livreur -->
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
                        <input type="tel" name="telephone" class="form-control" required pattern="[+]229 01[0-9\s]{8,}" inputmode="numeric" title="Format: +229 01 XX XX XX XX">
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
                <!-- Écouteur d'événement pour afficher le nom du fichier sélectionné pour la photo du livreur -->
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