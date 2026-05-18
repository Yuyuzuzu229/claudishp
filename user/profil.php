<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Notification.php';

if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

$pageTitle = 'Mon profil';
$utilisateur = new Utilisateur();
$user = $utilisateur->getById($_SESSION['user_id']);

require_once __DIR__ . '/../includes/header.php';
$activePage = 'profil';
?>
<div class="dashboard-layout">
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <div class="dash-page-header">
        <div class="dash-page-label">Mon compte</div>
        <h1 class="dash-page-title">Mon profil</h1>
        <p class="dash-page-sub">Gérez vos informations personnelles.</p>
    </div>

    <?php if (isset($_GET['invite'])): ?>
    <div style="background:linear-gradient(135deg,#F0FDF4,#ECFDF5);border:1px solid #86EFAC;border-radius:10px;padding:24px;margin-bottom:24px;display:flex;align-items:flex-start;gap:16px;">
        <div style="width:52px;height:52px;border-radius:50%;background:var(--success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-key" style="color:white;font-size:22px;"></i>
        </div>
        <div style="flex:1;">
            <h3 style="font-size:17px;font-weight:800;color:#166534;margin-bottom:6px;">Sécurisez votre compte</h3>
            <p style="font-size:13px;color:#374151;line-height:1.7;">
                Remplissez le champ <strong>Mot de passe</strong> ci-dessous pour sécuriser votre compte et pouvoir vous reconnecter plus tard.
            </p>
        </div>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- PROFIL CARD -->
    <div class="table-card" style="margin-bottom:20px;">
        <div style="padding:24px;display:flex;align-items:center;gap:24px;">
            <div style="width:80px;height:80px;border-radius:50%;background:var(--gray-200);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:var(--dark);flex-shrink:0;">
                <?= strtoupper(substr($user['prenom'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <h2 style="font-size:20px;font-weight:700;"><?= securiser($user['prenom']) ?> <?= securiser($user['nom']) ?></h2>
                <div style="display:inline-block;border:1px solid var(--gray-200);padding:2px 10px;font-size:11px;color:var(--gray-500);border-radius:20px;margin:6px 0;">Membre depuis <?= date('F Y', strtotime($user['date_inscription'])) ?></div>
                <div class="flex gap-3" style="margin-top:4px;">
                    <span class="text-sm text-muted"><i class="fas fa-envelope" style="margin-right:5px;"></i><?= securiser($user['email']) ?></span>
                    <span class="text-sm text-muted"><i class="fas fa-phone" style="margin-right:5px;"></i><?= securiser($user['telephone'] ?? '') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- INFOS PERSONNELLES -->
    <div class="table-card" style="margin-bottom:20px;">
        <div class="table-card-header"><span class="table-card-title">Informations personnelles</span></div>
        <div style="padding:24px;">
            <form method="POST" action="<?= BASE_URL ?>/actions/update_profil.php">
                <div class="grid-2" style="gap:16px;margin-bottom:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label><i class="fas fa-user" style="margin-right:6px;color:var(--gray-400);"></i>Nom</label>
                        <input type="text" name="nom" class="form-control" value="<?= securiser($user['nom']) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label><i class="fas fa-user" style="margin-right:6px;color:var(--gray-400);"></i>Prénom</label>
                        <input type="text" name="prenom" class="form-control" value="<?= securiser($user['prenom']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope" style="margin-right:6px;color:var(--gray-400);"></i>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= securiser($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock" style="margin-right:6px;color:var(--gray-400);"></i>Mot de passe</label>
                    <input type="password" name="nouveau_mdp" class="form-control" placeholder="Laisser vide pour ne pas modifier">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label><i class="fas fa-phone" style="margin-right:6px;color:var(--gray-400);"></i>Téléphone</label>
                    <div class="flex gap-2">
                        <select class="form-control" style="width:100px;flex-shrink:0;">
                            <option>+229</option>
                            <option>+228</option>
                            <option>+225</option>
                        </select>
                        <input type="tel" name="telephone" class="form-control" value="<?= securiser($user['telephone'] ?? '') ?>">
                    </div>
                </div>
                <div style="text-align:right;margin-top:24px;">
                    <button type="submit" class="btn btn-dark">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
</body></html>
