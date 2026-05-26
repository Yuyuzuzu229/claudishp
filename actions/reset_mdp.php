<?php
// Inclusion des fichiers de configuration et de base de données
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifie si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupère le token et le nouveau mot de passe
$token = $_POST['token'] ?? '';
$password = $_POST['mot_de_passe'] ?? '';

// Vérifie que le token n'est pas vide et que le mot de passe fait au moins 6 caractères
if (empty($token) || strlen($password) < 6) {
    $_SESSION['error'] = 'Données invalides.';
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupère la connexion PDO et vérifie la validité du token
$pdo = getPdo();
$stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE reset_token = ? AND reset_expire > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

// Si le token est invalide ou expiré, redirige avec une erreur
if (!$user) {
    $_SESSION['error'] = 'Lien invalide ou expiré.';
    redirect(BASE_URL . '/pages/connexion.php');
}

// Hache le nouveau mot de passe
$hash = password_hash($password, PASSWORD_DEFAULT);
// Met à jour le mot de passe et efface le token de réinitialisation
$stmt = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_expire = NULL WHERE id = ?");
$stmt->execute([$hash, $user['id']]);

// Message de succès et redirection vers la page de connexion
$_SESSION['success'] = 'Mot de passe réinitialisé avec succès. Connectez-vous !';
redirect(BASE_URL . '/pages/connexion.php');
