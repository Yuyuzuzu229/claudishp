<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Utilisateur pour la gestion des comptes
require_once __DIR__ . '/../classes/Utilisateur.php';

// Redirection vers le tableau de bord si l'utilisateur est déjà connecté
if (isLoggedIn()) { redirect(BASE_URL . '/index.php'); }

// Récupération de l'email prérempli depuis l'URL (optionnel)
$presetEmail = isset($_GET['email']) ? securiser($_GET['email']) : '';
// Définition du titre de la page
$pageTitle = 'Inscription';
// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
?>
<!-- Structure de la page d'inscription (deux colonnes) -->
<div class="connexion-layout">
    <!-- Colonne gauche : présentation de la marque -->
    <div class="connexion-left">
        <div style="margin-bottom:48px;">
            <div style="font-size:22px;font-weight:900;color:white;margin-bottom:4px;">CLAUDI<span style="font-weight:400;">SHOP</span></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.3);letter-spacing:2px;text-transform:uppercase;">Espace client</div>
        </div>
        <h2>Rejoignez ClaudiShop !</h2>
        <p style="margin-top:12px;margin-bottom:32px;">Créez votre compte et profitez d'une expérience shopping unique avec livraison partout au Bénin.</p>
        <div class="flex flex-col gap-3" style="gap:14px;">
            <div class="flex gap-3 items-center"><i class="fas fa-check-circle" style="color:var(--gold);font-size:16px;"></i><span style="color:rgba(255,255,255,0.7);font-size:13px;">Commandez en toute sécurité</span></div>
            <div class="flex gap-3 items-center"><i class="fas fa-check-circle" style="color:var(--gold);font-size:16px;"></i><span style="color:rgba(255,255,255,0.7);font-size:13px;">Paiement MTN MoMo &amp; Moov Money</span></div>
            <div class="flex gap-3 items-center"><i class="fas fa-check-circle" style="color:var(--gold);font-size:16px;"></i><span style="color:rgba(255,255,255,0.7);font-size:13px;">Livraison rapide &amp; retours faciles</span></div>
        </div>
        <div style="margin-top:auto;padding-top:48px;font-size:11px;color:rgba(255,255,255,0.25);">&copy; <?= date('Y') ?> ClaudiShop</div>
    </div>
    <!-- Colonne droite : formulaire d'inscription -->
    <div class="connexion-right">
        <div style="max-width:420px;width:100%;">
            <?php // Affichage des messages d'erreur stockés en session ?>
            <?php if (isset($_SESSION['error'])): ?><div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
            <?php if (isset($_SESSION['flash'])): ?><div class="alert alert-<?= $_SESSION['flash']['type'] ?? 'danger' ?>"><?= securiser($_SESSION['flash']['message'] ?? ''); unset($_SESSION['flash']); ?></div><?php endif; ?>

            <div style="margin-bottom:28px;">
                <h2 style="font-size:22px;font-weight:700;margin-bottom:6px;">Créer un compte</h2>
                <p class="text-muted text-sm">Remplissez le formulaire pour vous inscrire.</p>
            </div>

            <?php // Formulaire d'inscription envoyé vers actions/inscription.php ?>
            <form method="POST" action="<?= BASE_URL ?>/actions/inscription.php" onsubmit="var r=document.getElementById('regFieldTel');if(r.style.display!=='none'){document.getElementById('regEmailInput').disabled=true}else{document.getElementById('regTelInput').disabled=true};document.querySelectorAll('#reg-form input').forEach(i=>i.value=i.value.trim())" id="reg-form">
                <div class="grid-2" style="gap:12px;">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label>Nom</label>
                        <input type="text" name="nom" class="form-control" placeholder="Votre nom" required>
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label>Prénom</label>
                        <input type="text" name="prenom" class="form-control" placeholder="Votre prénom" required>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-bottom:12px;">
                    <button type="button" id="regTabEmail" class="btn-tab active" onclick="switchRegTab('email')" style="flex:1;padding:8px;border:1px solid var(--gray-200);border-radius:6px;background:var(--gray-50);cursor:pointer;font-size:13px;font-weight:600;"><i class="fas fa-envelope"></i> Email</button>
                    <button type="button" id="regTabTel" class="btn-tab" onclick="switchRegTab('tel')" style="flex:1;padding:8px;border:1px solid var(--gray-200);border-radius:6px;background:transparent;cursor:pointer;font-size:13px;font-weight:600;color:var(--gray-400);"><i class="fas fa-phone"></i> Téléphone</button>
                </div>
                <div id="regFieldEmail">
                    <div class="form-group">
                        <label>Adresse email</label>
                        <div class="input-with-icon">
                            <span class="icon"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" id="regEmailInput" placeholder="votre@email.com" value="<?= $presetEmail ?>">
                        </div>
                    </div>
                </div>
                <div id="regFieldTel" style="display:none;">
                    <div class="form-group">
                        <label>Numéro de téléphone</label>
                        <div class="input-with-icon">
                            <span class="icon"><i class="fas fa-phone"></i></span>
                            <input type="tel" name="telephone" class="form-control" id="regTelInput" placeholder="+229 01 XX XX XX XX" inputmode="numeric" oninput="var p='',d=this.value.replace(/[^0-9\+]/g,'');if(d[0]==='+'){var m=d.match(/^(\+22[589])/);if(m){p=m[1]+' ';d=d.slice(m[1].length)}else{p='+';d=d.slice(1)}}d=d.replace(/[^0-9]/g,'').slice(0,10);if(d.length>2)d=d.slice(0,2)+' '+d.slice(2);if(d.length>5)d=d.slice(0,5)+' '+d.slice(5);if(d.length>8)d=d.slice(0,8)+' '+d.slice(8);this.value=p+d">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <div class="input-with-icon">
                        <span class="icon"><i class="fas fa-lock"></i></span>
                        <input type="password" name="mot_de_passe" class="form-control" placeholder="Min. 8 caractères" required minlength="8">
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <div class="input-with-icon">
                        <span class="icon"><i class="fas fa-lock"></i></span>
                        <input type="password" name="mot_de_passe_confirm" class="form-control" placeholder="Répétez le mot de passe" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark btn-block btn-lg" style="margin-top:8px;">Créer mon compte</button>
            </form>
            <script>
            function switchRegTab(tab) {
                var emailField = document.getElementById('regFieldEmail');
                var telField = document.getElementById('regFieldTel');
                var tabEmail = document.getElementById('regTabEmail');
                var tabTel = document.getElementById('regTabTel');
                var emailInput = document.getElementById('regEmailInput');
                var telInput = document.getElementById('regTelInput');
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

            <?php // Lien vers la page de connexion ?>
            <div style="text-align:center;margin-top:20px;">
                <span class="text-muted text-sm">Déjà un compte ? </span>
                <a href="<?= BASE_URL ?>/pages/connexion.php" style="font-size:13px;font-weight:600;color:var(--dark);">Se connecter</a>
            </div>
            <?php // Lien retour à la boutique ?>
            <div style="text-align:center;margin-top:12px;">
                <a href="<?= BASE_URL ?>/index.php" style="font-size:12px;color:var(--gray-400);"><i class="fas fa-arrow-left" style="margin-right:4px;"></i>Retour à la boutique</a>
            </div>
        </div>
    </div>
</div>
</body></html>
