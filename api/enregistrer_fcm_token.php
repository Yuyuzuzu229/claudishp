<?php
// Inclusion de la configuration et de la base de données
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Définition du type de contenu en JSON
header('Content-Type: application/json');

// Vérification que la méthode HTTP est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérification que le livreur est connecté
if (!isset($_SESSION['driver_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Récupération et nettoyage du token FCM
$token = securiser($_POST['fcm_token'] ?? '');
// Vérification que le token n'est pas vide
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token requis']);
    exit;
}

// Connexion à la base de données et mise à jour du token FCM du livreur
$pdo = getPdo();
$stmt = $pdo->prepare("UPDATE livreur SET fcm_token = ? WHERE id = ?");
$ok = $stmt->execute([$token, $_SESSION['driver_id']]);

// Renvoi de la réponse JSON
echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Token enregistré' : 'Erreur',
]);
