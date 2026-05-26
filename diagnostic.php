<?php
// Inclusion de la configuration et de la base de données
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Titre de la page de diagnostic
echo '<h2>Diagnostic CLAUDISHOP</h2>';

// Bloc try-catch pour la connexion à la base de données
try {
    // Connexion à la base de données
    $db = Database::getInstance()->getConnection();
    echo '<p style="color:green">✓ Connexion BDD OK</p>';

    // Récupération de la liste des tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p>Tables trouvées (' . count($tables) . ') :</p><ul>';
    // Boucle d'affichage de chaque table
    foreach ($tables as $t) echo '<li>' . $t . '</li>';
    echo '</ul>';

    // Vérification de l'existence de la table 'utilisateur'
    if (in_array('utilisateur', $tables)) {
        // Récupération des utilisateurs
        $users = $db->query("SELECT id, email, role, est_actif, LEFT(mot_de_passe, 30) as hash_debut FROM utilisateur")->fetchAll();
        echo '<p>Utilisateurs dans <strong>utilisateur</strong> :</p><table border="1" cellpadding="4"><tr><th>ID</th><th>Email</th><th>Role</th><th>Actif</th><th>Hash (début)</th></tr>';
        // Boucle d'affichage de chaque utilisateur
        foreach ($users as $u) {
            echo '<tr><td>' . $u['id'] . '</td><td>' . $u['email'] . '</td><td>' . $u['role'] . '</td><td>' . $u['est_actif'] . '</td><td>' . $u['hash_debut'] . '...</td></tr>';
        }
        echo '</table>';

        // Test de vérification du mot de passe admin
        if (!empty($users)) {
            $admin = $users[0];
            $test = password_verify('admin123', $db->query("SELECT mot_de_passe FROM utilisateur WHERE email='admin@claudishop.bj'")->fetchColumn());
            echo '<p>Test admin123: <strong>' . ($test ? '✓ VALIDE' : '✗ INVALIDE') . '</strong></p>';
        }
    // Si la table 'utilisateurs' (pluriel) existe au lieu de 'utilisateur' (singulier)
    } elseif (in_array('utilisateurs', $tables)) {
        echo '<p style="color:orange">⚠ Table <strong>utilisateurs</strong> (pluriel) trouvée au lieu de <strong>utilisateur</strong>.</p>';
        echo '<p>Le code PHP utilise le singulier. Il faut réimporter le fichier database.sql corrigé.</p>';
    } else {
        // Aucune table utilisateur trouvée
        echo '<p style="color:red">✗ Aucune table utilisateur trouvée.</p>';
    }
// Capture des exceptions de base de données
} catch (Exception $e) {
    echo '<p style="color:red">✗ ERREUR : ' . $e->getMessage() . '</p>';
}
echo '<hr><p><a href="' . BASE_URL . '/index.php">Retour à l\'accueil</a></p>';
