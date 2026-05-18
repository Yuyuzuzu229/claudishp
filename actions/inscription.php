<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/inscription.php');
}

$nom = securiser($_POST['nom']);
$prenom = securiser($_POST['prenom']);
$email = securiser($_POST['email']);
$telephone = securiser($_POST['telephone'] ?? '');
$motDePasse = $_POST['mot_de_passe'];
$motDePasseConfirm = $_POST['mot_de_passe_confirm'] ?? '';

if (empty($nom) || empty($prenom) || empty($email) || empty($motDePasse)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tous les champs obligatoires doivent être remplis.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    redirect(BASE_URL . '/user/dashboard.php');
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => $resultat['message']];
    redirect(BASE_URL . '/pages/inscription.php');
}
