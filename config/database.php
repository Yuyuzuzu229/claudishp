<?php
// ── CLASSE : Database (Singleton) ─────────────────────────────────
// Implémente le patron de conception Singleton pour gérer la connexion
// à la base de données MySQL via PDO
class Database {
    // Instance unique de la classe (pattern Singleton)
    private static $instance = null;
    // Instance PDO pour la connexion à la base de données
    private $pdo;

    // ── CONSTRUCTEUR : privé ───────────────────────────────────────
    // Constructeur privé pour empêcher l'instanciation directe depuis l'extérieur
    private function __construct() {
        // Adresse du serveur MySQL
        $host = 'localhost';
        // Nom de la base de données
        $dbname = 'claudishop';
        // Nom d'utilisateur MySQL
        $username = 'root';
        // Mot de passe MySQL (vide par défaut en environnement local)
        $password = '';
        // Tentative de connexion à la base de données
        try {
            // Crée une nouvelle connexion PDO avec encodage utf8mb4
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    // Active le mode d'erreur : exceptions PDO
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Mode de récupération par défaut : tableau associatif
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Désactive l'émulation des requêtes préparées (natif MySQL)
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        // Attrape les exceptions PDO en cas d'échec de connexion
        } catch (PDOException $e) {
            // Arrête le script avec un message d'erreur générique (sans exposer les détails)
            die("Erreur de connexion à la base de données.");
        }
    }

    // ── MÉTHODE : getInstance ──────────────────────────────────────
    // Retourne l'instance unique de la classe (Singleton)
    public static function getInstance() {
        // Vérifie si l'instance unique n'a pas encore été créée
        if (self::$instance === null) {
            // Crée une nouvelle instance de la classe Database
            self::$instance = new self();
        }
        // Retourne l'instance unique (existante ou nouvellement créée)
        return self::$instance;
    }

    // ── MÉTHODE : getConnection ────────────────────────────────────
    // Retourne l'objet PDO de connexion à la base de données
    public function getConnection() {
        return $this->pdo;
    }
}

// ── FONCTION : getPdo ─────────────────────────────────────────────
// Fonction de commodité pour obtenir rapidement la connexion PDO
function getPdo() {
    // Obtient l'instance unique de Database et retourne la connexion PDO
    return Database::getInstance()->getConnection();
}
