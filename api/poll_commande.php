<?php
// Inclusion de la configuration et des classes nécessaires
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Livraison.php';

// Définition du type de contenu en JSON
header('Content-Type: application/json; charset=utf-8');

// Vérification que l'utilisateur est connecté
if (!isLoggedIn()) {
    exit(json_encode(['error' => 'Non connecté']));
}

// Récupération et validation de l'ID de commande passé en paramètre GET
$commandeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$commandeId) {
    exit(json_encode(['error' => 'ID invalide']));
}

// Récupération des informations de la commande
$commandeObj = new Commande();
$commande = $commandeObj->getById($commandeId);

// Vérification : la commande existe et appartient à l'utilisateur connecté
if (!$commande || $commande['utilisateur_id'] != $_SESSION['user_id']) {
    exit(json_encode(['error' => 'Accès refusé']));
}

// Récupération du suivi de livraison pour cette commande
$livraisonObj = new Livraison();
$suivi = $livraisonObj->getByCommande($commandeId);

// Renvoi des informations de suivi en JSON
echo json_encode([
    'commande_statut' => $commande['statut'],
    'livraison_statut' => $suivi ? $suivi['statut'] : null,
    'livraison_id'     => $suivi ? $suivi['id'] : null,
    'livreur_nom'      => $suivi ? ($suivi['livreur_nom'] ?? '') : '',
    'livreur_telephone' => $suivi ? ($suivi['livreur_telephone'] ?? '') : '',
    'livreur_whatsapp' => $suivi ? ($suivi['livreur_whatsapp'] ?? '') : '',
    'livreur_email'    => $suivi ? ($suivi['livreur_email'] ?? '') : '',
    'livreur_photo'    => $suivi ? ($suivi['livreur_photo'] ?? '') : '',
]);
