<?php
// Inclusion des fichiers de configuration et de la classe Adresse
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Adresse.php';

// Vérifie si l'utilisateur est connecté et si la requête est de type POST
if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirige vers la page de connexion si les conditions ne sont pas remplies
    redirect(BASE_URL . '/pages/connexion.php');
}

// Sécurise et récupère les données du formulaire
$quartier = securiser($_POST['quartier']);
$ville = securiser($_POST['ville']);
$pointRepere = securiser($_POST['point_repere'] ?? '');

// Crée une instance de la classe Adresse et ajoute la nouvelle adresse en base
$adresse = new Adresse();
$adresse->ajouter($quartier, $ville, $pointRepere, $_SESSION['user_id']);

// Redirige vers la page des adresses de l'utilisateur
redirect(BASE_URL . '/user/mes_adresses.php');
