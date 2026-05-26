<?php
// Inclusion des fichiers de configuration et de la classe Avis
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Avis.php';

// Vérifie si l'utilisateur est connecté et si la requête est de type POST
if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirige vers la page de connexion si les conditions ne sont pas remplies
    redirect(BASE_URL . '/pages/connexion.php');
}

// Récupère et sécurise les données du formulaire
$produitId = intval($_POST['produit_id']);
$note = intval($_POST['note']);
$commentaire = trim($_POST['commentaire'] ?? '');

// Vérifie que la note est comprise entre 1 et 5, sinon la fixe à 5
if ($note < 1 || $note > 5) {
    $note = 5;
}

// Crée une instance de la classe Avis et enregistre l'avis en base
$avis = new Avis();
$avis->ajouter($produitId, $_SESSION['user_id'], $note, $commentaire);

// Message de succès et redirection vers la page des avis de l'utilisateur
$_SESSION['success'] = 'Votre avis a été soumis.';
redirect(BASE_URL . '/user/mes_avis.php');
