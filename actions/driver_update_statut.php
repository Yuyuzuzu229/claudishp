<?php
// Inclusion des fichiers de configuration et des classes nécessaires
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Livreur.php';

// Vérifie si le livreur est connecté
if (!isset($_SESSION['driver_id'])) {
    redirect(BASE_URL . '/driver/connexion.php');
}

// Vérifie si la requête est POST et que les paramètres requis sont présents
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['livraison_id']) || !isset($_POST['statut'])) {
    redirect(BASE_URL . '/driver/dashboard.php');
}

// Récupère et sécurise les données du formulaire
$livraisonId = intval($_POST['livraison_id']);
$nouveauStatut = $_POST['statut'];
$driverId = intval($_SESSION['driver_id']);

// Instancie Livraison et récupère les informations de la livraison
$livraisonObj = new Livraison();
$livraison = $livraisonObj->getById($livraisonId);

// Vérifie que la livraison existe et qu'elle est bien assignée à ce livreur
if (!$livraison || intval($livraison['livreur_id']) !== $driverId) {
    $_SESSION['error'] = 'Livraison introuvable ou non assignée à vous.';
    redirect(BASE_URL . '/driver/dashboard.php');
}

// Liste des statuts autorisés pour les livreurs
$statutsAutorises = ['En route', 'En cours', 'Livrée'];

// Vérifie que le nouveau statut est dans la liste autorisée
if (!in_array($nouveauStatut, $statutsAutorises)) {
    $_SESSION['error'] = 'Statut non autorisé.';
    redirect(BASE_URL . '/driver/dashboard.php');
}

// Met à jour le statut de la livraison
$livraisonObj->updateStatut($livraisonId, $nouveauStatut);

// Si la livraison est marquée comme livrée, effectue les actions de finalisation
if ($nouveauStatut === 'Livrée') {
    // Confirme la réception de la livraison
    $livraisonObj->confirmerReception($livraisonId);
    // Remet le livreur disponible
    $livreurObj = new Livreur();
    $livreurObj->changerStatut($driverId, 'Disponible');
    // Tente une assignation automatique pour d'autres livraisons en attente
    $livraisonObj->assignerAutomatique();
}

// Message de succès et redirection vers le tableau de bord du livreur
$_SESSION['success'] = 'Statut mis à jour avec succès.';
redirect(BASE_URL . '/driver/dashboard.php');
