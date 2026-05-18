<?php
$pageTitle = 'Connexion livreur';
require_once __DIR__ . '/../config/config.php';
if (isset($_SESSION['driver_id'])) redirect(BASE_URL . '/driver/dashboard.php');
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livreur — CLAUDI SHOP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
</head>
<body style="background:var(--gray-50);display:flex;align-items:center;min-height:100vh;">
<div style="max-width:400px;margin:0 auto;padding:20px;width:100%;">
    <div style="text-align:center;margin-bottom:32px;">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--dark);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-motorcycle" style="font-size:28px;color:white;"></i>
        </div>
        <h1 style="font-size:20px;font-weight:700;">Espace livreur</h1>
        <p class="text-muted" style="font-size:14px;">Connectez-vous pour gérer vos livraisons</p>
    </div>

    <?php if (isset($_SESSION['driver_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['driver_error']; unset($_SESSION['driver_error']); ?></div>
    <?php endif; ?>

    <div style="background:white;padding:28px;border:1px solid var(--gray-200);">
        <form method="POST" action="<?= BASE_URL ?>/actions/driver_connexion.php">
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Téléphone</label>
                <input type="tel" name="telephone" class="form-control" required placeholder="Ex: +229 01 XX XX XX XX">
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label><i class="fas fa-lock"></i> Mot de passe</label>
                <input type="password" name="mot_de_passe" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark btn-block" style="margin-top:20px;padding:12px;">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>
    </div>
    <p class="text-xs text-muted" style="text-align:center;margin-top:20px;">
        <i class="fas fa-shield-alt"></i> Espace réservé aux livreurs ClaudiShop
    </p>
    <p style="text-align:center;margin-top:16px;">
        <a href="<?= BASE_URL ?>/index.php" style="font-size:12px;color:var(--gray-400);text-decoration:none;"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
    </p>
</div>
</body>
</html>
