<?php
// Inclusion du fichier de configuration principal (remonte de 2 niveaux jusqu'à la racine)
require_once __DIR__ . '/../../config/config.php';
// Vérification que l'utilisateur est connecté et a le rôle administrateur, sinon redirection vers la page de connexion
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }
// Redirection vers la page principale de gestion des catégories
redirect(BASE_URL . '/admin/categories.php');
