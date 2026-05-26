<?php
// Inclusion des fichiers de configuration et de la classe Livraison
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Livraison.php';

// Vérifie si l'utilisateur est connecté
if (!isLoggedIn()) {
    // Retourne une erreur JSON si non connecté
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Vérifie si la méthode HTTP est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Retourne une erreur JSON si méthode non autorisée
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupère et sécurise l'ID de commande
$commandeId = intval($_POST['commande_id'] ?? 0);

// Si l'ID de commande est invalide, retourne une erreur
if (!$commandeId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

// Instancie Livraison et récupère la livraison associée à la commande
$livraisonObj = new Livraison();
$livraison = $livraisonObj->getByCommande($commandeId);

// Si la livraison n'existe pas, retourne une erreur
if (!$livraison) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Livraison introuvable']);
    exit;
}

// Si le statut de la livraison permet la mise à jour, passe à "En cours"
if ($livraison['statut'] === 'En route' || $livraison['statut'] === 'Prêt à expédier') {
    $livraisonObj->updateStatut($livraison['id'], 'En cours');
}

// Retourne une réponse JSON de succès
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
exit;
