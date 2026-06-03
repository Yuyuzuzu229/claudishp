<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Inclusion de la classe Utilisateur pour gérer les utilisateurs
require_once __DIR__ . '/../classes/Utilisateur.php';
// Inclusion de la classe Panier pour gérer le panier
require_once __DIR__ . '/../classes/Panier.php';
// Inclusion de la classe Notification pour gérer les notifications
require_once __DIR__ . '/../classes/Notification.php';

// Vérification : rediriger vers la connexion si l'utilisateur n'est pas connecté
if (!isLoggedIn()) { redirect(BASE_URL . '/pages/connexion.php'); }

// Définition du titre de la page
$pageTitle = 'Mon profil';
// Instanciation de l'objet Utilisateur
$utilisateur = new Utilisateur();
// Récupération des données de l'utilisateur connecté
$user = $utilisateur->getById($_SESSION['user_id']);
// Détection : inscription par téléphone (email auto-généré)
$phoneOnly = (strpos($user['email'] ?? '', 'tel-') === 0) && (substr($user['email'] ?? '', -17) === '@claudishop.local');
// Extraction de l'indicatif et du numéro depuis le téléphone stocké (format: +22901XXXXXXXX)
$indicatif_user = '+229';
$numero_user = $user['telephone'] ?? '';
if (preg_match('/^(\+\d{3})\s?(\d[\d\s]*)$/', $user['telephone'] ?? '', $m)) {
    $indicatif_user = $m[1];
    $numero_user = $m[2];
}

// Inclusion de l'en-tête HTML
require_once __DIR__ . '/../includes/header.php';
// Définition de la page active pour la sidebar
$activePage = 'profil';
$adminPage = 'profil';
?>
<!-- Début du layout du tableau de bord -->
<div class="dashboard-layout">
<?php if (isAdmin()): ?>
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<?php else: ?>
<?php require_once __DIR__ . '/../includes/user_sidebar.php'; ?>
<?php endif; ?>
<div class="dash-main">
<?php require_once __DIR__ . '/../includes/dash_topbar.php'; ?>
<div class="dash-content">
    <!-- En-tête de page -->
    <div class="dash-page-header">
        <div class="dash-page-label">Mon compte</div>
        <h1 class="dash-page-title">Mon profil</h1>
        <p class="dash-page-sub">Gérez vos informations personnelles.</p>
    </div>

    <?php // Affichage de la bannière invité si l'utilisateur vient d'être converti ?>
    <?php if (isset($_GET['invite'])): ?>
    <!-- Bannière pour inviter l'utilisateur à définir un mot de passe -->
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
    <?php // Affichage d'un message de succès s'il existe ?>
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= securiser($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php // Affichage d'un message d'erreur s'il existe ?>
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= securiser($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Carte de profil (avatar et informations résumées) -->
    <div class="table-card" style="margin-bottom:20px;">
        <div style="padding:24px;display:flex;align-items:center;gap:24px;">
            <!-- Initiale de l'utilisateur comme avatar -->
            <div style="width:80px;height:80px;border-radius:50%;background:var(--gray-200);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:var(--dark);flex-shrink:0;">
                <?= strtoupper(substr($user['prenom'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <!-- Nom complet -->
                <h2 style="font-size:20px;font-weight:700;"><?= securiser($user['prenom']) ?> <?= securiser($user['nom']) ?></h2>
                <!-- Badge "Membre depuis" -->
                <div style="display:inline-block;border:1px solid var(--gray-200);padding:2px 10px;font-size:11px;color:var(--gray-500);border-radius:20px;margin:6px 0;">Membre depuis <?= date('F Y', strtotime($user['date_inscription'])) ?></div>
                <!-- Email et téléphone -->
                <div class="flex gap-3" style="margin-top:4px;">
                    <?php if ($phoneOnly): ?>
                    <span class="text-sm text-muted"><i class="fas fa-phone" style="margin-right:5px;"></i><?= securiser($indicatif_user . ' ' . $numero_user) ?></span>
                    <?php else: ?>
                    <span class="text-sm text-muted"><i class="fas fa-envelope" style="margin-right:5px;"></i><?= securiser($user['email']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire de modification des informations personnelles -->
    <div class="table-card" style="margin-bottom:20px;">
        <div class="table-card-header"><span class="table-card-title">Informations personnelles</span></div>
        <div style="padding:24px;">
            <!-- Le formulaire envoie vers la page de traitement update_profil.php -->
            <form method="POST" action="<?= BASE_URL ?>/actions/update_profil.php">
                <!-- Champs nom et prénom côte à côte -->
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
                <!-- Champ mot de passe (optionnel) -->
                <div class="form-group">
                    <label><i class="fas fa-lock" style="margin-right:6px;color:var(--gray-400);"></i>Mot de passe</label>
                    <input type="password" name="nouveau_mdp" class="form-control" placeholder="Laisser vide pour ne pas modifier">
                </div>
                <?php if ($phoneOnly): ?>
                <!-- Champ téléphone unique (inscription par téléphone) -->
                <div class="form-group" style="margin-bottom:0;">
                    <label><i class="fas fa-phone" style="margin-right:6px;color:var(--gray-400);"></i>Téléphone</label>
                    <div class="flex gap-2">
                        <?php $indicatifs = ['+229', '+228', '+225']; ?>
                        <select name="indicatif" class="form-control" style="width:100px;flex-shrink:0;">
                            <?php foreach ($indicatifs as $ind): ?>
                            <option value="<?= $ind ?>" <?= $indicatif_user === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" name="telephone" class="form-control" value="<?= securiser($numero_user) ?>" pattern="01[0-9\s]{8,}" inputmode="numeric" title="Format: 01 XX XX XX XX" oninput="var v=this.value.replace(/[^0-9]/g,'').slice(0,10);if(v.length>0&&v!=='01'&&!v.startsWith('01'))v='01'+v.replace(/^0+/,'');if(v.length>2)v=v.slice(0,2)+' '+v.slice(2);if(v.length>5)v=v.slice(0,5)+' '+v.slice(5);if(v.length>8)v=v.slice(0,8)+' '+v.slice(8);if(v.length>11)v=v.slice(0,11)+' '+v.slice(11);this.value=v">
                    </div>
                </div>
                <?php else: ?>
                <!-- Champ email -->
                <div class="form-group">
                    <label><i class="fas fa-envelope" style="margin-right:6px;color:var(--gray-400);"></i>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= securiser($user['email']) ?>" required>
                </div>
                <!-- Champ téléphone avec indicatif -->
                <div class="form-group" style="margin-bottom:0;">
                    <label><i class="fas fa-phone" style="margin-right:6px;color:var(--gray-400);"></i>Téléphone</label>
                    <div class="flex gap-2">
                        <?php $indicatifs = ['+229', '+228', '+225']; ?>
                        <select name="indicatif" class="form-control" style="width:100px;flex-shrink:0;">
                            <?php foreach ($indicatifs as $ind): ?>
                            <option value="<?= $ind ?>" <?= $indicatif_user === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="tel" name="telephone" class="form-control" value="<?= securiser($numero_user) ?>" pattern="01[0-9\s]{8,}" inputmode="numeric" title="Format: 01 XX XX XX XX" oninput="var v=this.value.replace(/[^0-9]/g,'').slice(0,10);if(v.length>0&&v!=='01'&&!v.startsWith('01'))v='01'+v.replace(/^0+/,'');if(v.length>2)v=v.slice(0,2)+' '+v.slice(2);if(v.length>5)v=v.slice(0,5)+' '+v.slice(5);if(v.length>8)v=v.slice(0,8)+' '+v.slice(8);if(v.length>11)v=v.slice(0,11)+' '+v.slice(11);this.value=v">
                    </div>
                </div>
                <?php endif; ?>
                <!-- Bouton de soumission -->
                <div style="text-align:right;margin-top:24px;">
                    <button type="submit" class="btn btn-dark">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if (isset($_GET['invite']) && !empty($_SESSION['guest_converted']) && empty($_SESSION['guest_password_set'])): ?>
<script>
// Supprime le compte invité immédiatement si l'utilisateur quitte la page sans définir de mot de passe
(function () {
    var passwordSet = false;
    // Écoute la soumission du formulaire profil pour détecter si un mot de passe est saisi
    var form = document.querySelector('form[action*="update_profil"]');
    if (form) {
        form.addEventListener('submit', function () {
            var mdp = form.querySelector('input[name="nouveau_mdp"]');
            if (mdp && mdp.value.trim().length >= 6) {
                passwordSet = true;
            }
        });
    }
    // Supprime le compte dès que l'utilisateur quitte la page (navigation, fermeture, rafraîchissement)
    window.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden' && !passwordSet) {
            navigator.sendBeacon('<?= BASE_URL ?>/actions/supprimer_compte_invite.php');
        }
    });
    window.addEventListener('pagehide', function () {
        if (!passwordSet) {
            navigator.sendBeacon('<?= BASE_URL ?>/actions/supprimer_compte_invite.php');
        }
    });
})();
</script>
<?php endif; ?>

<!-- Pied de page -->
<div class="dash-footer">
    <span>v1.0.0 &bull; ClaudiShop</span>
    <span>&copy; <?= date('Y') ?> ClaudiShop &ndash; Tous droits réservés &middot; Paiement MTN MoMo &amp; Moov Money</span>
    <span>v1.0.0</span>
</div>
</div>
</div>
</body></html>
