<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['driver_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$token = securiser($_POST['fcm_token'] ?? '');
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token requis']);
    exit;
}

$pdo = getPdo();
$stmt = $pdo->prepare("UPDATE livreur SET fcm_token = ? WHERE id = ?");
$ok = $stmt->execute([$token, $_SESSION['driver_id']]);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Token enregistré' : 'Erreur',
]);
