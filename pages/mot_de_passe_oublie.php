<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

if (isLoggedIn()) { redirect(BASE_URL . '/user/dashboard.php'); }

$pageTitle = 'Mot de passe oublié';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="connexion-layout">
    <div class="connexion-left">
        <div style="margin-bottom:48px;">
            <div style="font-size:22px;font-weight:900;color:white;margin-bottom:4px;">CLAUDI<span style="font-weight:400;">SHOP</span></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.3);letter-spacing:2px;text-transform:uppercase;">Mot de passe oublié</div>
        </div>
        <h2>Pas de panique !</h2>
        <p style="margin-top:12px;margin-bottom:32px;">Saisissez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
        <div style="margin-top:auto;padding-top:48px;font-size:11px;color:rgba(255,255,255,0.25);">
            &copy; <?= date('Y') ?> ClaudiShop
        </div>
    </div>
    <div class="connexion-right">
        <div style="max-width:380px;width:100%;">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <div style="margin-bottom:32px;">
                <h2 style="font-size:24px;font-weight:700;margin-bottom:6px;">Réinitialisation</h2>
                <p class="text-muted text-sm">Entrez votre email pour recevoir un lien.</p>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/actions/envoyer_reset_mdp.php">
                <div class="form-group">
                    <label>Adresse email</label>
                    <div class="input-with-icon">
                        <span class="icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="votre@email.com" required autofocus>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark btn-block btn-lg" style="margin-top:8px;">Envoyer le lien</button>
            </form>
            <div style="text-align:center;margin-top:24px;">
                <a href="<?= BASE_URL ?>/pages/connexion.php" style="font-size:12px;color:var(--gray-400);"><i class="fas fa-arrow-left" style="margin-right:4px;"></i>Retour à la connexion</a>
            </div>
        </div>
    </div>
</div>
</body></html>
