<?php
// Inclusion de la configuration et de la base de données
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Bloc try-catch pour les opérations de base de données
try {
    // Connexion à la base de données
    $db = Database::getInstance()->getConnection();

    // Génération des hash bcrypt pour les mots de passe
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $userHash  = password_hash('user123', PASSWORD_BCRYPT);

    // Recherche de l'admin par les deux emails possibles
    $stmt = $db->prepare("SELECT id FROM utilisateur WHERE email IN ('admin@claudishop.bj', 'admin@claudishop.com')");
    $stmt->execute();
    $admin = $stmt->fetch();

    // Si l'admin existe, mise à jour de son mot de passe et rôle
    if ($admin) {
        $db->prepare("UPDATE utilisateur SET email = 'admin@claudishop.bj', mot_de_passe = ?, role = 'admin', est_actif = 1 WHERE id = ?")
           ->execute([$adminHash, $admin['id']]);
    } else {
        // Sinon, création du compte admin
        $db->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role, est_actif) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute(['Admin', 'Super', 'admin@claudishop.bj', $adminHash, '+22997000000', 'admin', 1]);
    }
    echo "✓ Admin : admin@claudishop.bj / admin123<br>";

    // Recherche de l'utilisateur test par email
    $stmt2 = $db->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $stmt2->execute(['jean@email.com']);
    $user = $stmt2->fetch();

    // Si l'utilisateur test existe, mise à jour de son mot de passe
    if ($user) {
        $db->prepare("UPDATE utilisateur SET mot_de_passe = ?, role = 'user', est_actif = 1 WHERE id = ?")
           ->execute([$userHash, $user['id']]);
    } else {
        // Sinon, création du compte utilisateur test
        $db->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role, est_actif) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute(['Dupont', 'Jean', 'jean@email.com', $userHash, '+22990123456', 'user', 1]);
    }
    echo "✓ Utilisateur : jean@email.com / user123<br>";

    // Nettoyage des éventuels doublons (suppression de l'email en .com)
    $db->exec("DELETE FROM utilisateur WHERE email = 'admin@claudishop.com'");

    // Lien vers la page de connexion
    echo '<hr><p><a href="' . BASE_URL . '/pages/connexion.php">Aller à la connexion</a></p>';
// Capture des exceptions
} catch (Exception $e) {
    echo '<p style="color:red">✗ ERREUR : ' . $e->getMessage() . '</p>';
}
