<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';
require_once __DIR__ . '/../config/mail.php';

if (isLoggedIn()) { redirect(BASE_URL . '/user/dashboard.php'); }

$pageTitle = 'Connexion';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="connexion-layout">
    <div class="connexion-left">
        <div style="margin-bottom:48px;">
            <div style="font-size:22px;font-weight:900;color:white;margin-bottom:4px;">CLAUDI<span style="font-weight:400;">SHOP</span></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.3);letter-spacing:2px;text-transform:uppercase;">Espace client</div>
        </div>
        <h2>Bon retour parmi nous !</h2>
        <p style="margin-top:12px;margin-bottom:32px;">Connectez-vous pour accéder à votre espace personnel, vos commandes et vos avis.</p>
        <div class="flex flex-col gap-3" style="gap:14px;">
            <div class="flex gap-3 items-center"><i class="fas fa-check-circle" style="color:var(--gold);font-size:16px;"></i><span style="color:rgba(255,255,255,0.7);font-size:13px;">Suivre vos commandes en temps réel</span></div>
            <div class="flex gap-3 items-center"><i class="fas fa-check-circle" style="color:var(--gold);font-size:16px;"></i><span style="color:rgba(255,255,255,0.7);font-size:13px;">Paiement MTN MoMo &amp; Moov Money</span></div>
            <div class="flex gap-3 items-center"><i class="fas fa-check-circle" style="color:var(--gold);font-size:16px;"></i><span style="color:rgba(255,255,255,0.7);font-size:13px;">Livraison rapide partout au Bénin</span></div>
        </div>
        <div style="margin-top:auto;padding-top:48px;font-size:11px;color:rgba(255,255,255,0.25);">
            &copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés
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
                <h2 style="font-size:24px;font-weight:700;margin-bottom:6px;">Se connecter</h2>
                <p class="text-muted text-sm">Bienvenue ! Entrez vos identifiants pour continuer.</p>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/actions/connexion.php">
                <div class="form-group">
                    <label>Adresse email</label>
                    <div class="input-with-icon">
                        <span class="icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="votre@email.com" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <div class="input-with-icon">
                        <span class="icon"><i class="fas fa-lock"></i></span>
                        <input type="password" name="mot_de_passe" class="form-control" placeholder="••••••••••••" required>
                    </div>
                </div>
                <div style="text-align:right;margin-top:4px;">
                    <a href="<?= BASE_URL ?>/pages/mot_de_passe_oublie.php" style="font-size:12px;color:var(--gray-400);">Mot de passe oublié ?</a>
                </div>
                <button type="submit" class="btn btn-dark btn-block btn-lg" style="margin-top:8px;">Se connecter</button>
            </form>

            <div style="margin-top:16px;position:relative;text-align:center;">
                <div style="border-top:1px solid var(--gray-100);"></div>
                <span style="position:relative;top:-10px;background:white;padding:0 12px;font-size:12px;color:var(--gray-400);">ou</span>
            </div>
            <div id="gSignInWrapper" style="text-align:center;margin-top:4px;">
                <div class="g_id_signin"></div>
            </div>

            <div style="text-align:center;margin-top:24px;">
                <span class="text-muted text-sm">Pas encore de compte ? </span>
                <a href="<?= BASE_URL ?>/pages/inscription.php" style="font-size:13px;font-weight:600;color:var(--dark);">Créer un compte</a>
            </div>
            <div style="text-align:center;margin-top:16px;">
                <a href="<?= BASE_URL ?>/index.php" style="font-size:12px;color:var(--gray-400);"><i class="fas fa-arrow-left" style="margin-right:4px;"></i>Retour à la boutique</a>
            </div>

        </div>
    </div>
</div>
<script src="https://accounts.google.com/gsi/client" defer></script>
<script>
var gClientId = '<?= GOOGLE_CLIENT_ID ?>';
function handleCredentialResponse(response) {
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = '<?= BASE_URL ?>/actions/google_login.php';
    var i = document.createElement('input');
    i.type = 'hidden';
    i.name = 'credential';
    i.value = response.credential;
    f.appendChild(i);
    document.body.appendChild(f);
    f.submit();
}
(function pollGoogle(retries) {
    if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
        var el = document.querySelector('.g_id_signin');
        if (!el || !gClientId) return;
        google.accounts.id.initialize({
            client_id: gClientId,
            callback: handleCredentialResponse
        });
        google.accounts.id.renderButton(el, {
            theme: 'outline', size: 'large', type: 'standard',
            shape: 'rectangular', text: 'signin_with', width: 280
        });
    } else if (retries > 0) {
        setTimeout(function(){ pollGoogle(retries - 1); }, 500);
    } else {
        document.getElementById('gSignInWrapper').innerHTML =
            '<p style="font-size:12px;color:var(--gray-400);">Connexion Google indisponible</p>';
    }
})(60);
</script>
</body></html>
