<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Utilisateur pour la gestion des comptes
require_once __DIR__ . '/../classes/Utilisateur.php';
// Inclusion du fichier de configuration mail
require_once __DIR__ . '/../config/mail.php';

// Redirection vers le tableau de bord si l'utilisateur est déjà connecté
if (isLoggedIn()) { redirect(BASE_URL . '/index.php'); }

// Définition du titre de la page
$pageTitle = 'Connexion';
// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
?>
<!-- Structure de la page de connexion (deux colonnes) -->
<div class="connexion-layout">
    <!-- Colonne gauche : présentation de la marque -->
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
    <!-- Colonne droite : formulaire de connexion -->
    <div class="connexion-right">
        <div style="max-width:380px;width:100%;">
            <?php // Affichage des messages d'erreur stockés en session ?>
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php // Affichage des messages de succès stockés en session ?>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <div style="margin-bottom:32px;">
                <h2 style="font-size:24px;font-weight:700;margin-bottom:6px;">Se connecter</h2>
                <p class="text-muted text-sm">Bienvenue ! Entrez vos identifiants pour continuer.</p>
            </div>

            <?php // Formulaire de connexion envoyé vers actions/connexion.php ?>
            <form method="POST" action="<?= BASE_URL ?>/actions/connexion.php" onsubmit="var l=document.getElementById('loginFieldTel');if(l.style.display!=='none'){document.getElementById('emailInput').disabled=true}else{document.getElementById('telInput').disabled=true};document.querySelectorAll('input').forEach(i=>i.value=i.value.trim())">
                <div class="form-group">
                    <div style="display:flex;gap:8px;margin-bottom:8px;">
                        <button type="button" id="tabEmail" class="btn-tab active" onclick="switchLoginTab('email')" style="flex:1;padding:8px;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50);cursor:pointer;font-size:13px;font-weight:600;"><i class="fas fa-envelope"></i> Email</button>
                        <button type="button" id="tabTel" class="btn-tab" onclick="switchLoginTab('tel')" style="flex:1;padding:8px;border:1px solid var(--gray-200);border-radius:6px;background:transparent;cursor:pointer;font-size:13px;font-weight:600;color:var(--gray-400);"><i class="fas fa-phone"></i> Téléphone</button>
                    </div>
                    <div id="loginFieldEmail">
                        <div class="input-with-icon">
                            <span class="icon"><i class="fas fa-envelope"></i></span>
                            <input type="text" name="identifiant" class="form-control" id="emailInput" placeholder="votre@email.com" autofocus>
                        </div>
                    </div>
                    <div id="loginFieldTel" style="display:none;">
                        <div class="input-with-icon">
                            <span class="icon"><i class="fas fa-phone"></i></span>
                            <input type="text" name="identifiant" class="form-control" id="telInput" placeholder="+229 01 XX XX XX XX" inputmode="numeric" oninput="var p='',d=this.value.replace(/[^0-9\+]/g,'');if(d[0]==='+'){var m=d.match(/^(\+22[589])/);if(m){p=m[1]+' ';d=d.slice(m[1].length)}else{p='+';d=d.slice(1)}}d=d.replace(/[^0-9]/g,'').slice(0,10);if(d.length>2)d=d.slice(0,2)+' '+d.slice(2);if(d.length>5)d=d.slice(0,5)+' '+d.slice(5);if(d.length>8)d=d.slice(0,8)+' '+d.slice(8);if(d.length>11)d=d.slice(0,11)+' '+d.slice(11);this.value=p+d">
                        </div>
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
            <script>
            function switchLoginTab(tab) {
                var emailField = document.getElementById('loginFieldEmail');
                var telField = document.getElementById('loginFieldTel');
                var tabEmail = document.getElementById('tabEmail');
                var tabTel = document.getElementById('tabTel');
                var emailInput = document.getElementById('emailInput');
                var telInput = document.getElementById('telInput');
                if (tab === 'email') {
                    emailField.style.display = ''; telField.style.display = 'none';
                    tabEmail.style.background = 'var(--gray-50)'; tabEmail.style.color = 'inherit';
                    tabTel.style.background = 'transparent'; tabTel.style.color = 'var(--gray-400)';
                    emailInput.required = true; telInput.required = false; emailInput.focus();
                } else {
                    emailField.style.display = 'none'; telField.style.display = '';
                    tabTel.style.background = 'var(--gray-50)'; tabTel.style.color = 'inherit';
                    tabEmail.style.background = 'transparent'; tabEmail.style.color = 'var(--gray-400)';
                    telInput.required = true; emailInput.required = false; telInput.focus();
                }
            }
            </script>

            <!-- Séparateur "ou" -->
            <div style="margin-top:16px;position:relative;text-align:center;">
                <div style="border-top:1px solid var(--gray-100);"></div>
                <span style="position:relative;top:-10px;background:white;padding:0 12px;font-size:12px;color:var(--gray-400);">ou</span>
            </div>
            <?php // Conteneur pour le bouton Google Sign-In ?>
            <div id="gSignInWrapper" style="text-align:center;margin-top:4px;">
                <div class="g_id_signin"></div>
            </div>

            <?php // Lien vers la page d'inscription ?>
            <div style="text-align:center;margin-top:24px;">
                <span class="text-muted text-sm">Pas encore de compte ? </span>
                <a href="<?= BASE_URL ?>/pages/inscription.php" style="font-size:13px;font-weight:600;color:var(--dark);">Créer un compte</a>
            </div>
            <?php // Lien retour à la boutique ?>
            <div style="text-align:center;margin-top:16px;">
                <a href="<?= BASE_URL ?>/index.php" style="font-size:12px;color:var(--gray-400);"><i class="fas fa-arrow-left" style="margin-right:4px;"></i>Retour à la boutique</a>
            </div>

        </div>
    </div>
</div>
<?php // Chargement asynchrone de la bibliothèque Google Sign-In ?>
<script src="https://accounts.google.com/gsi/client" defer></script>
<script>
<?php // Récupération de l'ID client Google depuis la constante PHP ?>
var gClientId = '<?= GOOGLE_CLIENT_ID ?>';
<?php // Fonction de callback appelée après authentification Google ?>
function handleCredentialResponse(response) {
    <?php // Création d'un formulaire caché pour envoyer le token au serveur ?>
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
<?php // Fonction de polling pour attendre le chargement de la bibliothèque Google ?>
(function pollGoogle(retries) {
    <?php // Vérification si l'API Google est disponible ?>
    if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
        var el = document.querySelector('.g_id_signin');
        <?php // Initialisation et rendu du bouton Google ?>
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
        <?php // Nouvelle tentative après 500ms si le SDK n'est pas encore chargé ?>
        setTimeout(function(){ pollGoogle(retries - 1); }, 500);
    } else {
        <?php // Affichage d'un message si le SDK ne se charge pas après 60 tentatives ?>
        document.getElementById('gSignInWrapper').innerHTML =
            '<p style="font-size:12px;color:var(--gray-400);">Connexion Google indisponible</p>';
    }
})(60);
</script>
</body></html>
