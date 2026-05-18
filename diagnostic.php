<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo '<h2>Diagnostic CLAUDISHOP</h2>';

try {
    $db = Database::getInstance()->getConnection();
    echo '<p style="color:green">✓ Connexion BDD OK</p>';

    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<p>Tables trouvées (' . count($tables) . ') :</p><ul>';
    foreach ($tables as $t) echo '<li>' . $t . '</li>';
    echo '</ul>';

    if (in_array('utilisateur', $tables)) {
        $users = $db->query("SELECT id, email, role, est_actif, LEFT(mot_de_passe, 30) as hash_debut FROM utilisateur")->fetchAll();
        echo '<p>Utilisateurs dans <strong>utilisateur</strong> :</p><table border="1" cellpadding="4"><tr><th>ID</th><th>Email</th><th>Role</th><th>Actif</th><th>Hash (début)</th></tr>';
        foreach ($users as $u) {
            echo '<tr><td>' . $u['id'] . '</td><td>' . $u['email'] . '</td><td>' . $u['role'] . '</td><td>' . $u['est_actif'] . '</td><td>' . $u['hash_debut'] . '...</td></tr>';
        }
        echo '</table>';

        if (!empty($users)) {
            $admin = $users[0];
            $test = password_verify('admin123', $db->query("SELECT mot_de_passe FROM utilisateur WHERE email='admin@claudishop.bj'")->fetchColumn());
            echo '<p>Test admin123: <strong>' . ($test ? '✓ VALIDE' : '✗ INVALIDE') . '</strong></p>';
        }
    } elseif (in_array('utilisateurs', $tables)) {
        echo '<p style="color:orange">⚠ Table <strong>utilisateurs</strong> (pluriel) trouvée au lieu de <strong>utilisateur</strong>.</p>';
        echo '<p>Le code PHP utilise le singulier. Il faut réimporter le fichier database.sql corrigé.</p>';
    } else {
        echo '<p style="color:red">✗ Aucune table utilisateur trouvée.</p>';
    }
} catch (Exception $e) {
    echo '<p style="color:red">✗ ERREUR : ' . $e->getMessage() . '</p>';
}
echo '<hr><p><a href="' . BASE_URL . '/index.php">Retour à l\'accueil</a></p>';
