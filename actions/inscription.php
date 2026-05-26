<?php
// Inclusion des fichiers de configuration et de la classe Utilisateur
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

// Vérifie si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/inscription.php');
}

// Récupère et sécurise les données du formulaire d'inscription
$nom = securiser($_POST['nom']);
$prenom = securiser($_POST['prenom']);
$email = securiser($_POST['email']);
$telephone = securiser($_POST['telephone'] ?? '');
$motDePasse = $_POST['mot_de_passe'];
$motDePasseConfirm = $_POST['mot_de_passe_confirm'] ?? '';

// Vérifie que tous les champs obligatoires sont remplis
if (empty($nom) || empty($prenom) || empty($email) || empty($motDePasse)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tous les champs obligatoires doivent être remplis.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

// Vérifie le format de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Format d\'email invalide.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

// Vérifie la longueur minimale du mot de passe
if (strlen($motDePasse) < 6) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

// Vérifie que les mots de passe correspondent
if ($motDePasse !== $motDePasseConfirm) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Les mots de passe ne correspondent pas.'];
    redirect(BASE_URL . '/pages/inscription.php');
}

// Instancie Utilisateur et tente l'inscription
$utilisateur = new Utilisateur();
$resultat = $utilisateur->inscrire($nom, $prenom, $email, $motDePasse, $telephone);

// Si l'inscription réussit, redirige vers le dashboard
if ($resultat['success']) {
    redirect(BASE_URL . '/user/dashboard.php');
} else {
    // Sinon, affiche le message d'erreur
    $_SESSION['flash'] = ['type' => 'danger', 'message' => $resultat['message']];
    redirect(BASE_URL . '/pages/inscription.php');
}
