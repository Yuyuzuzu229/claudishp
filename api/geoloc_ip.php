<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

$ip = $_SERVER['REMOTE_ADDR'];

// IP locale => on utilise la position de la boutique comme approximation
$isLocal = in_array($ip, ['127.0.0.1', '::1']) 
    || preg_match('/^(192\.168|10\.|172\.(1[6-9]|2[0-9]|3[01]))/', $ip);

if ($isLocal) {
    echo json_encode([
        'success' => true,
        'latitude' => SHOP_LAT,
        'longitude' => SHOP_LNG,
        'city' => 'Cotonou',
        'country' => 'Bénin',
        'source' => 'local'
    ]);
    exit;
}

// IP publique => service ip-api.com
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$apiUrl = "http://ip-api.com/json/{$ip}?fields=status,lat,lon,city,country,query";
$response = @file_get_contents($apiUrl, false, $ctx);

if ($response === false) {
    // Fallback sur la boutique
    echo json_encode([
        'success' => true,
        'latitude' => SHOP_LAT,
        'longitude' => SHOP_LNG,
        'city' => 'Position approximative',
        'country' => '',
        'source' => 'fallback'
    ]);
    exit;
}

$data = json_decode($response, true);
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
    echo json_encode([
        'success' => true,
        'latitude' => SHOP_LAT,
        'longitude' => SHOP_LNG,
        'city' => 'Position approximative',
        'country' => '',
        'source' => 'fallback'
    ]);
}
