<?php
require_once __DIR__ . '/../config/database.php';

class Utilisateur {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM utilisateur ORDER BY date_inscription DESC");
        return $stmt->fetchAll();
    }

    public function search($q) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ? ORDER BY date_inscription DESC");
        $like = "%$q%";
        $stmt->execute([$like, $like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
    }

    public function getNouveauxCeMois() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM utilisateur WHERE MONTH(date_inscription) = MONTH(CURRENT_DATE) AND YEAR(date_inscription) = YEAR(CURRENT_DATE)");
        return $stmt->fetchColumn();
    }

    public function getActifs() {
        return $this->db->query("SELECT COUNT(*) FROM utilisateur WHERE est_actif = 1")->fetchColumn();
    }

    public function inscrire($nom, $prenom, $email, $motDePasse, $telephone, $role = 'client') {
        if ($this->getByEmail($email)) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
        }
        $hash = password_hash($motDePasse, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$nom, $prenom, $email, $hash, $telephone, $role]);
        if (!$ok) {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription.'];
        }
        $_SESSION['user_id'] = $this->db->lastInsertId();
        $_SESSION['user_email'] = $email;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_role'] = $role;
        return ['success' => true, 'message' => 'Inscription réussie.'];
    }

    public function connecter($email, $motDePasse) {
        $user = $this->getByEmail($email);
        if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }
        if (!$user['est_actif']) {
            return ['success' => false, 'message' => 'Compte désactivé. Contactez l\'administrateur.'];
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_role'] = $user['role'];
        $this->updateDerniereConnexion($user['id']);
        return ['success' => true, 'role' => $user['role']];
    }

    public function verifierMotDePasse($email, $motDePasse) {
        $user = $this->getByEmail($email);
        if ($user && password_verify($motDePasse, $user['mot_de_passe'])) {
            return $user;
        }
        return false;
    }

    public function update($id, $nom, $prenom, $email, $telephone) {
        return $this->mettreAJour($id, $nom, $prenom, $email, $telephone);
    }

    public function mettreAJour($id, $nom, $prenom, $email, $telephone) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?");
        return $stmt->execute([$nom, $prenom, $email, $telephone, $id]);
    }

    public function changerMotDePasse($id, $motDePasse) {
        $hash = password_hash($motDePasse, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    public function updateDerniereConnexion($id) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateRole($id, $role) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $id]);
    }

    public function toggleActif($id) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET est_actif = NOT est_actif WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
