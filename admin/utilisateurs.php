<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Notification
require_once __DIR__ . '/../classes/Notification.php';

// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Inclusion de la classe Utilisateur
require_once __DIR__ . '/../classes/Utilisateur.php';
// Instanciation de l'objet Utilisateur
$utilisateurObj = new Utilisateur();

// Vérification si le formulaire a été soumis en méthode POST avec l'action toggle_actif
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_actif') {
    $userId = intval($_POST['user_id']);
    // Empêche l'administrateur de désactiver son propre compte
    if ($userId === intval($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Vous ne pouvez pas désactiver votre propre compte.';
    } else {
        // Basculement du statut actif/inactif de l'utilisateur
        $utilisateurObj->toggleActif($userId);
        $_SESSION['success'] = 'Statut de l\'utilisateur mis à jour.';
    }
    redirect(BASE_URL . '/admin/utilisateurs.php');
}

// Définition du titre de la page
$pageTitle = 'Gestion Utilisateurs';
// Récupération du terme de recherche depuis l'URL, ou chaîne vide par défaut
$search = isset($_GET['q']) ? securiser($_GET['q']) : '';
// Si un terme de recherche est fourni, recherche des utilisateurs, sinon récupération de tous les utilisateurs
if ($search) {
    $utilisateurs = $utilisateurObj->search($search);
    $nbUtilisateurs = count($utilisateurs);
} else {
    $utilisateurs = $utilisateurObj->getAll();
    $nbUtilisateurs = $utilisateurObj->getNombre();
}
// Inclusion de l'en-tête HTML du site
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour le menu d'administration
$adminPage = 'utilisateurs';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
<div class="dash-content">

    <div class="dash-page-header">
        <div class="dash-page-label">Gestion</div>
        <h1 class="dash-page-title">Utilisateurs</h1>
        <p class="dash-page-sub">Gérez les comptes clients</p>
    </div>

    <!-- Affichage des messages de succès ou d'erreur éventuels -->
    <?php if (isset($_SESSION['success'])): ?><div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <div class="table-card">
        <div class="table-card-header">
            <span class="table-card-title">Tous les utilisateurs (<?= $nbUtilisateurs ?>)</span>
        </div>
        <table>
            <thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Téléphone</th><th>Rôle</th><th>Statut</th><th>Inscrit le</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($utilisateurs)): ?>
            <!-- Si aucun utilisateur n'est trouvé, affichage d'un message par défaut -->
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun utilisateur.</td></tr>
            <?php else: foreach ($utilisateurs as $u): ?>
            <!-- Boucle d'affichage de chaque utilisateur dans une ligne du tableau -->
            <tr>
                <td class="text-xs text-muted">#<?= $u['id'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <!-- Affichage des initiales de l'utilisateur dans un cercle -->
                        <div style="width:28px;height:28px;border-radius:50%;background:var(--gray-200);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;"><?= strtoupper(substr($u['prenom']??'',0,1).substr($u['nom']??'',0,1)) ?></div>
                        <span class="text-sm font-semibold"><?= securiser($u['prenom'].' '.$u['nom']) ?></span>
                    </div>
                </td>
                <td class="text-sm"><?= securiser($u['email']) ?></td>
                <td class="text-sm text-muted"><?= securiser($u['telephone']??'—') ?></td>
                <td><?= $u['role']==='admin'?'<span class="badge badge-primary">Admin</span>':'<span class="badge badge-dark">'.$u['role'].'</span>' ?></td>
                <td><?= $u['est_actif'] ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-dark">Inactif</span>' ?></td>
                <td class="text-xs text-muted"><?= date('d/m/Y', strtotime($u['date_inscription'])) ?></td>
                <td>
                    <?php if (intval($u['id']) !== intval($_SESSION['user_id'])): ?>
                    <!-- Formulaire d'activation/désactivation (caché pour l'utilisateur connecté) -->
                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $u['est_actif'] ? 'Désactiver' : 'Activer' ?> cet utilisateur ?')">
                        <input type="hidden" name="action" value="toggle_actif">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit" class="action-btn <?= $u['est_actif'] ? 'danger' : '' ?>" title="<?= $u['est_actif'] ? 'Désactiver' : 'Activer' ?>">
                            <i class="fas <?= $u['est_actif'] ? 'fa-ban' : 'fa-check-circle' ?>"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <!-- Pied du tableau indiquant le nombre total d'utilisateurs -->
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);"><span class="text-xs text-muted">Total : <?= $nbUtilisateurs ?> utilisateurs</span></div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>