<?php
// Inclusion du fichier de configuration principal
require_once __DIR__ . '/../config/config.php';
// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }
// Redirection vers la page d'accueil du tableau de bord
redirect(BASE_URL . '/admin/index.php');
