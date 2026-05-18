<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }

require_once __DIR__ . '/../classes/Utilisateur.php';
$utilisateurObj = new Utilisateur();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_actif') {
    $userId = intval($_POST['user_id']);
    if ($userId === intval($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Vous ne pouvez pas désactiver votre propre compte.';
    } else {
        $utilisateurObj->toggleActif($userId);
        $_SESSION['success'] = 'Statut de l\'utilisateur mis à jour.';
    }
    redirect(BASE_URL . '/admin/utilisateurs.php');
}

$pageTitle = 'Gestion Utilisateurs';
$utilisateurs = $utilisateurObj->getAll();
$nbUtilisateurs = $utilisateurObj->getNombre();
require_once __DIR__ . '/../includes/header.php';
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
            <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--gray-400);">Aucun utilisateur.</td></tr>
            <?php else: foreach ($utilisateurs as $u): ?>
            <tr>
                <td class="text-xs text-muted">#<?= $u['id'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
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
        <div style="padding:12px 16px;border-top:1px solid var(--gray-100);"><span class="text-xs text-muted">Total : <?= $nbUtilisateurs ?> utilisateurs</span></div>
    </div>

</div>
<div class="dash-footer"><span>v1.0.0 &bull; ClaudiShop Admin</span><span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés</span><span>v1.0.0</span></div>
</div>
</div>
</body></html>
