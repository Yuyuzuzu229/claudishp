<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Utilisateur pour la gestion des comptes
require_once __DIR__ . '/../classes/Utilisateur.php';

// Redirection vers le tableau de bord si l'utilisateur est déjà connecté
if (isLoggedIn()) { redirect(BASE_URL . '/user/dashboard.php'); }

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

            <div style="margin-bottom:28px;">
                <h2 style="font-size:22px;font-weight:700;margin-bottom:6px;">Créer un compte</h2>
                <p class="text-muted text-sm">Remplissez le formulaire pour vous inscrire.</p>
            </div>

            <?php // Formulaire d'inscription envoyé vers actions/inscription.php ?>
            <form method="POST" action="<?= BASE_URL ?>/actions/inscription.php">
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
                <div class="form-group">
                    <label>Adresse email</label>
                    <div class="input-with-icon">
                        <span class="icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="votre@email.com" value="<?= $presetEmail ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <div class="flex gap-2">
                        <select class="form-control" style="width:90px;flex-shrink:0;"><option>+229</option><option>+228</option><option>+225</option></select>
                        <input type="tel" name="telephone" class="form-control" placeholder="01 XX XX XX XX" pattern="01[0-9\s]{8,}" inputmode="numeric" title="Format: 01 XX XX XX XX" oninput="this.value=this.value.replace(/[^0-9\s]/g,'');if(this.value.length>0&&!this.value.startsWith('01'))this.value='01'+this.value.replace(/^0+/,'')">
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
