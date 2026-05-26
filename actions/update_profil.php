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
$email = securiser($_POST['email']);
$telephone = securiser($_POST['telephone'] ?? '');
$nouveauMdp = $_POST['nouveau_mdp'] ?? '';

// Instancie Utilisateur
$utilisateur = new Utilisateur();

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
