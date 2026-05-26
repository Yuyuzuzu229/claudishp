<?php
// Inclusion des fichiers de configuration, de base de données et de la classe FedaPay
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/FedaPay.php';

// Récupère le token et l'ID de commande depuis l'URL
$token = securiser($_GET['token'] ?? '');
$commandeId = intval($_GET['commande_id'] ?? 0);

// Si le token est vide, redirige vers l'accueil
if (empty($token)) {
    redirect(BASE_URL . '/index.php');
}

// Instancie FedaPay et annule le paiement via l'API
$fedapay = new FedaPay();
$fedapay->annulerPaiement($token);

// Si un ID de commande est fourni, met à jour les statuts en base
if ($commandeId) {
    $pdo = getPdo();
    // Marque le paiement comme échoué
    $stmt = $pdo->prepare("UPDATE paiement SET statut = 'Échoué' WHERE commande_id = ? AND statut = 'En attente'");
    $stmt->execute([$commandeId]);
    // Marque la commande comme annulée
    $stmt = $pdo->prepare("UPDATE commande SET statut = 'Annulée' WHERE id = ? AND statut = 'Confirmée'");
    $stmt->execute([$commandeId]);

    // Vérifie si un livreur était assigné à cette commande
    $stmt = $pdo->prepare("SELECT livreur_id FROM livraison WHERE commande_id = ? AND livreur_id IS NOT NULL");
    $stmt->execute([$commandeId]);
    $livreur = $stmt->fetch();
    // Si un livreur est trouvé, le remet disponible
    if ($livreur && $livreur['livreur_id']) {
        $pdo->prepare("UPDATE livreur SET statut = 'Disponible' WHERE id = ?")->execute([$livreur['livreur_id']]);
    }
    // Tente une réassignation automatique des livraisons
    require_once __DIR__ . '/../classes/Livraison.php';
    $liv = new Livraison();
    $liv->assignerAutomatique();
}

// Message d'erreur et redirection vers le panier
$_SESSION['error'] = 'Paiement annulé.';
redirect(BASE_URL . '/pages/panier.php');
