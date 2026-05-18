<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) { redirect(BASE_URL . '/user/dashboard.php'); }

$token = $_GET['token'] ?? '';
if (empty($token)) {
    $_SESSION['error'] = 'Token manquant.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT id, email FROM utilisateur WHERE reset_token = ? AND reset_expire > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'Lien invalide ou expiré.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$pageTitle = 'Nouveau mot de passe';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="connexion-layout">
    <div class="connexion-left">
        <div style="margin-bottom:48px;">
            <div style="font-size:22px;font-weight:900;color:white;margin-bottom:4px;">CLAUDI<span style="font-weight:400;">SHOP</span></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.3);letter-spacing:2px;text-transform:uppercase;">Nouveau mot de passe</div>
        </div>
        <h2>Choisissez un nouveau mot de passe</h2>
        <p style="margin-top:12px;margin-bottom:32px;">Minimum 6 caractères, gardez-le en lieu sûr !</p>
        <div style="margin-top:auto;padding-top:48px;font-size:11px;color:rgba(255,255,255,0.25);">&copy; <?= date('Y') ?> ClaudiShop</div>
    </div>
    <div class="connexion-right">
        <div style="max-width:380px;width:100%;">
            <div style="margin-bottom:32px;">
                <h2 style="font-size:24px;font-weight:700;margin-bottom:6px;">Nouveau mot de passe</h2>
                <p class="text-muted text-sm">Pour <?= securiser($user['email']) ?></p>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/actions/reset_mdp.php">
                <input type="hidden" name="token" value="<?= securiser($token) ?>">
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <div class="input-with-icon">
                        <span class="icon"><i class="fas fa-lock"></i></span>
                        <input type="password" name="mot_de_passe" class="form-control" placeholder="Minimum 6 caractères" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn btn-dark btn-block btn-lg" style="margin-top:8px;">Réinitialiser</button>
            </form>
        </div>
    </div>
</div>
</body></html>
