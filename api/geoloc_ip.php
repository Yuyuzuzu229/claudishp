<?php
// Inclusion de la configuration
require_once __DIR__ . '/../config/config.php';
// Définition du type de contenu en JSON
header('Content-Type: application/json');

// Récupération de l'adresse IP du client
$ip = $_SERVER['REMOTE_ADDR'];

// IP locale => on utilise la position de la boutique comme approximation
$isLocal = in_array($ip, ['127.0.0.1', '::1']) 
    || preg_match('/^(192\.168|10\.|172\.(1[6-9]|2[0-9]|3[01]))/', $ip);

// Si l'IP est locale, renvoie les coordonnées de la boutique
if ($isLocal) {
    echo json_encode([
        'success' => true,
        'latitude' => getShopLat(),
        'longitude' => getShopLng(),
        'city' => 'Cotonou',
        'country' => 'Bénin',
        'source' => 'local'
    ]);
    exit;
}

// IP publique => appel au service ip-api.com
// Création du contexte de flux avec timeout
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$apiUrl = "http://ip-api.com/json/{$ip}?fields=status,lat,lon,city,country,query";
$response = @file_get_contents($apiUrl, false, $ctx);

// Si la requête échoue, fallback sur la position de la boutique
if ($response === false) {
    echo json_encode([
        'success' => true,
        'latitude' => getShopLat(),
        'longitude' => getShopLng(),
        'city' => 'Position approximative',
        'country' => '',
        'source' => 'fallback'
    ]);
    exit;
}

// Décodage de la réponse JSON de l'API
$data = json_decode($response, true);
// Si la réponse est valide et réussie
if ($data && $data['status'] === 'success') {
    echo json_encode([
        'success' => true,
        'latitude' => $data['lat'],
        'longitude' => $data['lon'],
        'city' => $data['city'] ?? '',
        'country' => $data['country'] ?? '',
        'source' => 'ip'
    ]);
} else {
    // Fallback si l'API ne répond pas correctement
    echo json_encode([
        'success' => true,
        'latitude' => getShopLat(),
        'longitude' => getShopLng(),
        'city' => 'Position approximative',
        'country' => '',
        'source' => 'fallback'
    ]);
}
