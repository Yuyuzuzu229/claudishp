<?php
require_once __DIR__ . '/../../config/config.php';
if (!isLoggedIn() || !isAdmin()) { redirect(BASE_URL . '/pages/connexion.php'); }
redirect(BASE_URL . '/admin/livreurs.php');