<?php
// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../config/database.php';

// Classe gérant les opérations liées aux utilisateurs (CRUD, authentification, gestion de session)
class Utilisateur {
    // Instance de connexion PDO à la base de données
    private $db;

    // Constructeur : initialise la connexion à la base via le singleton Database
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupère tous les utilisateurs triés par date d'inscription décroissante
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM utilisateur ORDER BY date_inscription DESC");
        return $stmt->fetchAll();
    }

    // Recherche des utilisateurs par nom, prénom, email ou téléphone
    public function search($q) {
        // Préparation d'une requête avec LIKE sur plusieurs colonnes
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ? ORDER BY date_inscription DESC");
        // Ajout des wildcards % pour la recherche partielle
        $like = "%$q%";
        $stmt->execute([$like, $like, $like, $like]);
        return $stmt->fetchAll();
    }

    // Récupère un utilisateur par son identifiant
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Récupère un utilisateur par son adresse email
    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getByTelephone($telephone) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE telephone = ?");
        $stmt->execute([$telephone]);
        return $stmt->fetch();
    }

    // Retourne le nombre total d'utilisateurs
    public function getNombre() {
        return $this->db->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
    }

    // Retourne le nombre d'utilisateurs inscrits ce mois-ci
    public function getNouveauxCeMois() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM utilisateur WHERE MONTH(date_inscription) = MONTH(CURRENT_DATE) AND YEAR(date_inscription) = YEAR(CURRENT_DATE)");
        return $stmt->fetchColumn();
    }

    // Retourne le nombre d'utilisateurs actifs (est_actif = 1)
    public function getActifs() {
        return $this->db->query("SELECT COUNT(*) FROM utilisateur WHERE est_actif = 1")->fetchColumn();
    }

    // Inscrit un nouvel utilisateur : vérifie l'unicité de l'email/téléphone, hache le mot de passe, et connecte automatiquement
    public function inscrire($nom, $prenom, $email, $motDePasse, $telephone, $role = 'user') {
        // Vérifie si l'email est déjà utilisé (si fourni)
        if (!empty($email) && $this->getByEmail($email)) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
        }
        // Vérifie si le téléphone est déjà utilisé (si fourni)
        if (!empty($telephone) && $this->getByTelephone($telephone)) {
            return ['success' => false, 'message' => 'Ce numéro de téléphone est déjà utilisé.'];
        }
        // Génère un email unique pour les inscriptions sans email
        if (empty($email) && !empty($telephone)) {
            $email = 'tel-' . preg_replace('/[^0-9]/', '', $telephone) . '@claudishop.local';
        }
        // Hachage du mot de passe avec BCRYPT
        $hash = password_hash($motDePasse, PASSWORD_BCRYPT);
        // Insertion du nouvel utilisateur en base
        $stmt = $this->db->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, telephone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$nom, $prenom, $email, $hash, $telephone, $role]);
        // Si l'insertion échoue, retourne une erreur
        if (!$ok) {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription.'];
        }
        // Connexion automatique après inscription : remplissage de la session
        $_SESSION['user_id'] = $this->db->lastInsertId();
        $_SESSION['user_email'] = $email;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_telephone'] = $telephone;
        return ['success' => true, 'message' => 'Inscription réussie.'];
    }

    // Connecte un utilisateur : vérifie email/téléphone + mot de passe, puis initialise la session
    public function connecter($identifiant, $motDePasse) {
        // Récupération de l'utilisateur par email ou téléphone
        $user = $this->getByEmail($identifiant);
        if (!$user) {
            $user = $this->getByTelephone($identifiant);
        }
        // Si l'utilisateur n'existe pas OU que le mot de passe est incorrect
        if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
            return ['success' => false, 'message' => 'Email/téléphone ou mot de passe incorrect.'];
        }
        // Si le compte est désactivé, on refuse la connexion
        if (!$user['est_actif']) {
            return ['success' => false, 'message' => 'Compte désactivé. Contactez l\'administrateur.'];
        }
        // Initialisation des variables de session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_telephone'] = $user['telephone'];
        // Mise à jour de la date de dernière connexion
        $this->updateDerniereConnexion($user['id']);
        return ['success' => true, 'role' => $user['role']];
    }

    // Vérifie si un mot de passe correspond à un email (sans connexion)
    public function verifierMotDePasse($email, $motDePasse) {
        $user = $this->getByEmail($email);
        // Si l'utilisateur existe et que le mot de passe est correct, retourne l'utilisateur
        if ($user && password_verify($motDePasse, $user['mot_de_passe'])) {
            return $user;
        }
        // Sinon retourne false
        return false;
    }

    // Alias de mettreAJour pour la compatibilité
    public function update($id, $nom, $prenom, $email, $telephone) {
        return $this->mettreAJour($id, $nom, $prenom, $email, $telephone);
    }

    // Met à jour les informations d'un utilisateur (nom, prénom, email, téléphone)
    public function mettreAJour($id, $nom, $prenom, $email, $telephone) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?");
        return $stmt->execute([$nom, $prenom, $email, $telephone, $id]);
    }

    // Change le mot de passe d'un utilisateur (haché avec BCRYPT)
    public function changerMotDePasse($id, $motDePasse) {
        $hash = password_hash($motDePasse, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    // Met à jour la date de dernière connexion d'un utilisateur avec l'horodatage actuel
    public function updateDerniereConnexion($id) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Change le rôle d'un utilisateur
    public function updateRole($id, $role) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $id]);
    }

    // Bascule l'état actif/inactif d'un utilisateur
    public function toggleActif($id) {
        $stmt = $this->db->prepare("UPDATE utilisateur SET est_actif = NOT est_actif WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getNombreByPeriode($debut, $fin) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateur WHERE date_inscription BETWEEN ? AND ?");
        $stmt->execute([$debut, $fin]);
        return (int)$stmt->fetchColumn();
    }
}
