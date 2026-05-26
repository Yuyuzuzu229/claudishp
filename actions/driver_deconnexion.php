<?php
// Inclusion du fichier de configuration
require_once __DIR__ . '/../config/config.php';

// Supprime les variables de session spécifiques au livreur
unset($_SESSION['driver_id'], $_SESSION['driver_nom'], $_SESSION['driver_telephone']);

// Redirige vers la page de connexion livreur
redirect(BASE_URL . '/driver/connexion.php');
