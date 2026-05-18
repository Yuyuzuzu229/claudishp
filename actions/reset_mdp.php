<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$token = $_POST['token'] ?? '';
$password = $_POST['mot_de_passe'] ?? '';

if (empty($token) || strlen($password) < 6) {
    $_SESSION['error'] = 'Données invalides.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE reset_token = ? AND reset_expire > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'Lien invalide ou expiré.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?");
$stmt->execute([$hash, $user['id']]);

$_SESSION['success'] = 'Mot de passe réinitialisé avec succès. Connectez-vous !';
redirect(BASE_URL . '/pages/connexion.php');
