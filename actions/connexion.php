<?php
// Inclusion des fichiers de configuration et de la classe Utilisateur
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Utilisateur.php';

// Vérifie si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupère et sécurise les identifiants de connexion
$email = securiser($_POST['email']);
$motDePasse = $_POST['mot_de_passe'];

// Vérifie que les champs ne sont pas vides
if (empty($email) || empty($motDePasse)) {
    $_SESSION['error'] = 'Veuillez remplir tous les champs.';
    redirect(BASE_URL . '/pages/connexion.php');
}

// Instancie Utilisateur et tente la connexion
$utilisateur = new Utilisateur();
$resultat = $utilisateur->connecter($email, $motDePasse);

// Si la connexion réussit, redirige selon le rôle
if ($resultat['success']) {
    // Redirige les admins vers l'admin, les autres vers le dashboard user
    if ($resultat['role'] === 'admin') {
        redirect(BASE_URL . '/admin/index.php');
    } else {
        redirect(BASE_URL . '/user/dashboard.php');
    }
} else {
    // Sinon, affiche l'erreur et redirige vers la page de connexion
    $_SESSION['error'] = $resultat['message'];
    redirect(BASE_URL . '/pages/connexion.php');
}
