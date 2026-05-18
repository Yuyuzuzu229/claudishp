<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

$nom = securiser($_POST['nom']);
$prenom = securiser($_POST['prenom']);
$email = securiser($_POST['email']);
$telephone = securiser($_POST['telephone'] ?? '');
$nouveauMdp = $_POST['nouveau_mdp'] ?? '';

$utilisateur = new Utilisateur();

if (!empty($nouveauMdp)) {
    if (strlen($nouveauMdp) < 6) {
        $_SESSION['error'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        redirect(BASE_URL . '/user/profil.php');
    }
    $utilisateur->changerMotDePasse($_SESSION['user_id'], $nouveauMdp);
    $_SESSION['guest_password_set'] = true;
    unset($_SESSION['guest_converted'], $_SESSION['guest_banner_shown']);
}

$utilisateur->update($_SESSION['user_id'], $nom, $prenom, $email, $telephone);

$_SESSION['user_nom'] = $nom;
$_SESSION['user_prenom'] = $prenom;
$_SESSION['user_email'] = $email;

$_SESSION['success'] = 'Profil mis à jour avec succès.';
redirect(BASE_URL . '/user/profil.php');
