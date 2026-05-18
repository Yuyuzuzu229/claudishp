<?php
require_once __DIR__ . '/../config/config.php';
unset($_SESSION['driver_id'], $_SESSION['driver_nom'], $_SESSION['driver_telephone']);
redirect(BASE_URL . '/driver/connexion.php');
