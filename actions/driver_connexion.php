<?php
// Inclusion des fichiers de configuration et de base de données
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifie si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/driver/connexion.php');
}

// Récupère et normalise le téléphone et le mot de passe
$telephone = normaliserTelephone($_POST['telephone'] ?? '');
$password = $_POST['mot_de_passe'] ?? '';

// Vérifie que les champs ne sont pas vides
if (empty($telephone) || empty($password)) {
    $_SESSION['driver_error'] = 'Veuillez remplir tous les champs.';
    redirect(BASE_URL . '/driver/connexion.php');
}

// Récupère la connexion PDO et cherche le livreur par téléphone
$pdo = getPdo();
$stmt = $pdo->prepare("SELECT * FROM livreur WHERE telephone = ? AND est_actif = 1 ORDER BY id ASC LIMIT 1");
$stmt->execute([$telephone]);
$driver = $stmt->fetch();

// Si le livreur n'existe pas ou le mot de passe est incorrect, redirige avec erreur
if (!$driver || !password_verify($password, $driver['mot_de_passe'])) {
    $_SESSION['driver_error'] = 'Téléphone ou mot de passe incorrect.';
    redirect(BASE_URL . '/driver/connexion.php');
}

// Définit les variables de session pour le livreur connecté
$_SESSION['driver_id'] = $driver['id'];
$_SESSION['driver_nom'] = $driver['nom'];
$_SESSION['driver_telephone'] = $driver['telephone'];

// Redirige vers le tableau de bord du livreur
redirect(BASE_URL . '/driver/dashboard.php');
