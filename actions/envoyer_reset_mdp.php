<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/NotificationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$email = trim($_POST['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email invalide.';
    redirect(BASE_URL . '/pages/mot_de_passe_oublie.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT id, nom, prenom FROM utilisateur WHERE email = ? AND est_actif = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['success'] = 'Si cet email existe, un lien de réinitialisation vous a été envoyé.';
    redirect(BASE_URL . '/pages/mot_de_passe_oublie.php');
}

$token = bin2hex(random_bytes(32));
$expire = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = $pdo->prepare("UPDATE utilisateur SET reset_token = ?, reset_expire = ? WHERE id = ?");
$stmt->execute([$token, $expire, $user['id']]);

$resetLink = BASE_URL . '/pages/reset_mdp.php?token=' . $token;
$nom = securiser($user['prenom'] . ' ' . $user['nom']);
$subject = 'Réinitialisation de votre mot de passe ClaudiShop';

$message = "
<!DOCTYPE html>
<html>
<head><meta charset='utf-8'></head>
<body style='font-family:Arial,sans-serif;background:#f3f4f6;padding:20px;'>
<div style='max-width:500px;margin:0 auto;background:white;border-radius:8px;padding:30px;'>
    <h2 style='color:#1f2937;'>Bonjour $nom,</h2>
    <p style='color:#4b5563;'>Vous avez demandé la réinitialisation de votre mot de passe.</p>
    <p style='color:#4b5563;'>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe (lien valable 30 minutes) :</p>
    <div style='text-align:center;margin:30px 0;'>
        <a href='$resetLink' style='display:inline-block;padding:14px 32px;background:#1f2937;color:white;text-decoration:none;border-radius:6px;font-weight:bold;'>Réinitialiser mon mot de passe</a>
    </div>
    <p style='color:#9ca3af;font-size:12px;'>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
</div>
</body>
</html>";

$notifSvc = new NotificationService();
$notifSvc->envoyerEmail($email, $subject, $message, true);

$_SESSION['success'] = 'Si cet email existe, un lien de réinitialisation vous a été envoyé.';
redirect(BASE_URL . '/pages/mot_de_passe_oublie.php');
