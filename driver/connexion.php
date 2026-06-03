<?php
// Définition du titre de la page
$pageTitle = 'Connexion livreur';
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Vérification : si le livreur est déjà connecté, redirection vers le dashboard
if (isset($_SESSION['driver_id'])) redirect(BASE_URL . '/driver/dashboard.php');
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livreur — CLAUDI SHOP</title>
    <!-- Polices et icônes Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Feuille de style principale -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<!-- Corps centré verticalement -->
<body style="background:var(--gray-50);display:flex;align-items:center;min-height:100vh;">
<div style="max-width:400px;margin:0 auto;padding:20px;width:100%;">
    <!-- Logo et titre de la page de connexion -->
    <div style="text-align:center;margin-bottom:32px;">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--dark);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <!-- Icône moto -->
            <i class="fas fa-motorcycle" style="font-size:28px;color:white;"></i>
        </div>
        <h1 style="font-size:20px;font-weight:700;">Espace livreur</h1>
        <p class="text-muted" style="font-size:14px;">Connectez-vous pour gérer vos livraisons</p>
    </div>

    <!-- Affichage des éventuelles erreurs de connexion -->
    <?php if (isset($_SESSION['driver_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['driver_error']; unset($_SESSION['driver_error']); ?></div>
    <?php endif; ?>

    <!-- Formulaire de connexion pour livreur -->
    <div style="background:white;padding:28px;border:1px solid var(--gray-200);">
        <!-- Envoi vers le script de traitement de connexion livreur -->
        <form method="POST" action="<?= BASE_URL ?>/actions/driver_connexion.php">
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Téléphone</label>
                <!-- Champ téléphone obligatoire -->
                <input type="tel" name="telephone" class="form-control" required placeholder="+229 01 XX XX XX XX ou 0123456789" pattern="[+]?[0-9\s]{8,}" title="Accepte: +229 01 XX XX XX XX, 0123456789, 2290123456789" inputmode="numeric" oninput="var v=this.value.replace(/^\s+/,'');var num=v.replace(/[^0-9]/g,'');if(num.startsWith('229'))num=num.slice(3);num=num.slice(0,10);if(num.length>0&&num!=='01'&&!num.startsWith('01'))num='01'+num.replace(/^0+/,'');if(num.length>2)num=num.slice(0,2)+' '+num.slice(2);if(num.length>5)num=num.slice(0,5)+' '+num.slice(5);if(num.length>8)num=num.slice(0,8)+' '+num.slice(8);if(num.length>11)num=num.slice(0,11)+' '+num.slice(11);this.value=v[0]==='+' && v.indexOf('229')>0 ? '+229 ' + num : num">
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label><i class="fas fa-lock"></i> Mot de passe</label>
                <!-- Champ mot de passe obligatoire -->
                <input type="password" name="mot_de_passe" class="form-control" required>
            </div>
            <!-- Bouton de soumission -->
            <button type="submit" class="btn btn-dark btn-block" style="margin-top:20px;padding:12px;">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>
    </div>
    <!-- Message de sécurité -->
    <p class="text-xs text-muted" style="text-align:center;margin-top:20px;">
        <i class="fas fa-shield-alt"></i> Espace réservé aux livreurs ClaudiShop
    </p>
    <!-- Lien retour vers l'accueil -->
    <p style="text-align:center;margin-top:16px;">
        <a href="<?= BASE_URL ?>/index.php" style="font-size:12px;color:var(--gray-400);text-decoration:none;"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
    </p>
</div>
</body>
</html>
