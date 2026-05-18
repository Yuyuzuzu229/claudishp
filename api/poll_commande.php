<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Livraison.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    exit(json_encode(['error' => 'Non connecté']));
}

$commandeId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$commandeId) {
    exit(json_encode(['error' => 'ID invalide']));
}

$commandeObj = new Commande();
$commande = $commandeObj->getById($commandeId);

if (!$commande || $commande['utilisateur_id'] != $_SESSION['user_id']) {
    exit(json_encode(['error' => 'Accès refusé']));
}

$livraisonObj = new Livraison();
$suivi = $livraisonObj->getByCommande($commandeId);

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
