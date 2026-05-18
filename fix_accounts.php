<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $userHash  = password_hash('user123', PASSWORD_BCRYPT);

    // Chercher admin par les deux emails possibles
    $stmt = $db->prepare("SELECT id FROM utilisateur WHERE email IN ('admin@claudishop.bj', 'admin@claudishop.com')");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        $db->prepare("UPDATE utilisateur SET email = 'admin@claudishop.bj', mot_de_passe = ?, role = 'admin', est_actif = 1 WHERE id = ?")
           ->execute([$adminHash, $admin['id']]);
    } else {
        $db->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role, est_actif) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute(['Admin', 'Super', 'admin@claudishop.bj', $adminHash, '+22997000000', 'admin', 1]);
    }
    echo "✓ Admin : admin@claudishop.bj / admin123<br>";

    // Utilisateur test
    $stmt2 = $db->prepare("SELECT id FROM utilisateur WHERE email = ?");
    $stmt2->execute(['jean@email.com']);
    $user = $stmt2->fetch();

    if ($user) {
        $db->prepare("UPDATE utilisateur SET mot_de_passe = ?, role = 'user', est_actif = 1 WHERE id = ?")
           ->execute([$userHash, $user['id']]);
    } else {
        $db->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role, est_actif) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute(['Dupont', 'Jean', 'jean@email.com', $userHash, '+22990123456', 'user', 1]);
    }
    echo "✓ Utilisateur : jean@email.com / user123<br>";

    // Nettoyer les éventuels doublons
    $db->exec("DELETE FROM utilisateur WHERE email = 'admin@claudishop.com'");

    echo '<hr><p><a href="' . BASE_URL . '/pages/connexion.php">Aller à la connexion</a></p>';
} catch (Exception $e) {
    echo '<p style="color:red">✗ ERREUR : ' . $e->getMessage() . '</p>';
}
