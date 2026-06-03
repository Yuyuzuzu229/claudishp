<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$identifiant = trim(securiser($_POST['identifiant'] ?? ''));
$motDePasse = $_POST['mot_de_passe'];

if (empty($identifiant) || empty($motDePasse)) {
    $_SESSION['error'] = 'Veuillez remplir tous les champs.';
    redirect(BASE_URL . '/pages/connexion.php');
}

$utilisateur = new Utilisateur();
$resultat = $utilisateur->connecter($identifiant, $motDePasse);

if ($resultat['success']) {
    if ($resultat['role'] === 'admin') {
        redirect(BASE_URL . '/admin/index.php');
    } else {
        redirect(BASE_URL . '/index.php');
    }
} else {
    $_SESSION['error'] = $resultat['message'];
    redirect(BASE_URL . '/pages/connexion.php');
}
