<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/inscription.php');
}

$nom = trim(securiser($_POST['nom'] ?? ''));
$prenom = trim(securiser($_POST['prenom'] ?? ''));
$email = trim(securiser($_POST['email'] ?? ''));
$telephone = trim(securiser($_POST['telephone'] ?? ''));
// Nettoie le téléphone : supprime les espaces, tirets, parenthèses
if (!empty($telephone)) {
    $telephone = preg_replace('/[\s\-\/\(\)]/', '', $telephone);
}
$motDePasse = $_POST['mot_de_passe'] ?? '';
$motDePasseConfirm = $_POST['mot_de_passe_confirm'] ?? '';

if (empty($nom) || empty($prenom) || empty($motDePasse)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tous les champs obligatoires doivent être remplis.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

if (empty($email) && empty($telephone)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Veuillez fournir un email ou un numéro de téléphone.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

if (!empty($telephone) && !preg_match('/^\+[1-9][0-9]{7,14}$/', $telephone)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Le numéro de téléphone doit être au format international (ex: +229 01 XX XX XX XX).'];
    redirect(BASE_URL . '/pages/inscription.php');
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Format d\'email invalide.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

if (strlen($motDePasse) < 6) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

if ($motDePasse !== $motDePasseConfirm) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Les mots de passe ne correspondent pas.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

$utilisateur = new Utilisateur();
$resultat = $utilisateur->inscrire($nom, $prenom, $email, $motDePasse, $telephone);

if ($resultat['success']) {
    redirect(BASE_URL . '/index.php');
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => $resultat['message']];
    redirect(BASE_URL . '/pages/inscription.php');
}
