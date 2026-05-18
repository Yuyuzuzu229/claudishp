<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/driver/connexion.php');
}

$telephone = normaliserTelephone($_POST['telephone'] ?? '');
$password = $_POST['mot_de_passe'] ?? '';

if (empty($telephone) || empty($password)) {
    $_SESSION['driver_error'] = 'Veuillez remplir tous les champs.';
    redirect(BASE_URL . '/driver/connexion.php');
}

$pdo = getPdo();
$stmt = $pdo->prepare("SELECT * FROM livreur WHERE telephone = ? AND est_actif = 1 ORDER BY id ASC LIMIT 1");
$stmt->execute([$telephone]);
$driver = $stmt->fetch();

if (!$driver || !password_verify($password, $driver['mot_de_passe'])) {
    $_SESSION['driver_error'] = 'Téléphone ou mot de passe incorrect.';
    redirect(BASE_URL . '/driver/connexion.php');
}

$_SESSION['driver_id'] = $driver['id'];
$_SESSION['driver_nom'] = $driver['nom'];
$_SESSION['driver_telephone'] = $driver['telephone'];

redirect(BASE_URL . '/driver/dashboard.php');
