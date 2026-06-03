<?php
// Inclusion des fichiers de configuration et de la classe Utilisateur
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

// Vérifie si l'utilisateur est connecté et si la requête est POST
if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupère et sécurise les données du formulaire de profil
$nom = securiser($_POST['nom']);
$prenom = securiser($_POST['prenom']);
$nouveauMdp = $_POST['nouveau_mdp'] ?? '';

// Instancie Utilisateur
$utilisateur = new Utilisateur();

// Récupère l'utilisateur actuel pour savoir s'il est inscrit par téléphone
$currentUser = $utilisateur->getById($_SESSION['user_id']);
$isPhoneOnly = $currentUser && (strpos($currentUser['email'] ?? '', 'tel-') === 0) && (substr($currentUser['email'] ?? '', -17) === '@claudishop.local');

if ($isPhoneOnly) {
    // Inscription par téléphone : pas de champ email, on garde l'email auto-généré
    $email = $currentUser['email'];
    $indicatif = securiser($_POST['indicatif'] ?? '+229');
    $telephone = $indicatif . preg_replace('/[^0-9]/', '', securiser($_POST['telephone'] ?? ''));
} else {
    // Inscription par email
    $email = securiser($_POST['email'] ?? '');
    $indicatif = securiser($_POST['indicatif'] ?? '+229');
    $telephone = $indicatif . preg_replace('/[^0-9]/', '', securiser($_POST['telephone'] ?? ''));
}

// Si un nouveau mot de passe est fourni, le change
if (!empty($nouveauMdp)) {
    // Vérifie la longueur minimale
    if (strlen($nouveauMdp) < 6) {
        $_SESSION['error'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        redirect(BASE_URL . '/user/profil.php');
    }
    // Change le mot de passe en base
    $utilisateur->changerMotDePasse($_SESSION['user_id'], $nouveauMdp);
    $_SESSION['guest_password_set'] = true;
    unset($_SESSION['guest_converted'], $_SESSION['guest_banner_shown']);
}

// Met à jour les informations du profil en base
$utilisateur->update($_SESSION['user_id'], $nom, $prenom, $email, $telephone);

// Met à jour les variables de session
$_SESSION['user_nom'] = $nom;
$_SESSION['user_prenom'] = $prenom;
$_SESSION['user_email'] = $email;

// Message de succès et redirection vers la page de profil
$_SESSION['success'] = 'Profil mis à jour avec succès.';
redirect(BASE_URL . '/user/profil.php');
