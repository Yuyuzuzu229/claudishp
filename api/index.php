<?php
// Définition des en-têtes CORS et du type de contenu JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion des requêtes OPTIONS (préflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inclusion de la configuration, base de données et toutes les classes
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Produit.php';
require_once __DIR__ . '/../classes/Categorie.php';
require_once __DIR__ . '/../classes/Commande.php';
require_once __DIR__ . '/../classes/Panier.php';
require_once __DIR__ . '/../classes/Paiement.php';
require_once __DIR__ . '/../classes/Livraison.php';
require_once __DIR__ . '/../classes/Livreur.php';
require_once __DIR__ . '/../classes/ZoneLivraison.php';
require_once __DIR__ . '/../classes/Avis.php';
require_once __DIR__ . '/../classes/Utilisateur.php';
require_once __DIR__ . '/../classes/Notification.php';

// Connexion PDO à la base de données
$pdo = Database::getInstance()->getConnection();
// Méthode HTTP de la requête
$method = $_SERVER['REQUEST_METHOD'];

// Analyse de l'URL : endpoint, id, subresource
$endpoint = $_GET['endpoint'] ?? '';
$parts = explode('/', trim($endpoint, '/'));
$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;
$subresource = $parts[2] ?? null;

// Fonction utilitaire : envoie une réponse JSON avec un code HTTP
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Fonction utilitaire : envoie une réponse d'erreur JSON
function jsonError($message, $code = 400) {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

// Fonction utilitaire : récupère et décode le corps JSON de la requête
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// Fonction utilitaire : vérifie que l'utilisateur est authentifié
function verifierAuth() {
    if (!isLoggedIn()) {
        jsonError('Non authentifié.', 401);
    }
}

// Fonction utilitaire : vérifie que l'utilisateur est administrateur
function verifierAdmin() {
    verifierAuth();
    if (!isAdmin()) {
        jsonError('Accès refusé.', 403);
    }
}

// Bloc try-catch principal pour gérer les erreurs serveur
try {
    // Routage principal basé sur la ressource demandée
    switch ($resource) {
        // === PRODUITS ===
        case 'produits':
            $produitObj = new Produit();
            // GET : liste de tous les produits
            if ($method === 'GET' && !$id) {
                $produits = $produitObj->getAll();
                jsonResponse(['success' => true, 'data' => $produits]);
            // GET : détail d'un produit par ID
            } elseif ($method === 'GET' && $id) {
                $produit = $produitObj->getById(intval($id));
                $produit ? jsonResponse(['success' => true, 'data' => $produit]) : jsonError('Produit introuvable.', 404);
            // POST : création d'un produit (admin seulement)
            } elseif ($method === 'POST') {
                verifierAdmin();
                $data = getJsonInput();
                $produitObj->ajouter(
                    securiser($data['nom']),
                    securiser($data['description'] ?? ''),
                    floatval($data['prix']),
                    intval($data['stock']),
                    intval($data['categorie_id']),
                    $data['photo'] ?? 'default.jpg',
                    $data['taille'] ?? null,
                    $data['couleur'] ?? null,
                    $data['matiere'] ?? null,
                    !empty($data['solde_prix']) ? floatval($data['solde_prix']) : null
                );
                jsonResponse(['success' => true, 'message' => 'Produit créé.'], 201);
            // PUT : modification d'un produit (admin seulement)
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $produitObj->modifier(
                    intval($id),
                    securiser($data['nom']),
                    securiser($data['description'] ?? ''),
                    floatval($data['prix']),
                    intval($data['stock']),
                    intval($data['categorie_id']),
                    $data['photo'] ?? null,
                    $data['taille'] ?? null,
                    $data['couleur'] ?? null,
                    $data['matiere'] ?? null,
                    !empty($data['solde_prix']) ? floatval($data['solde_prix']) : null
                );
                jsonResponse(['success' => true, 'message' => 'Produit modifié.']);
            // DELETE : suppression d'un produit (admin seulement)
            } elseif ($method === 'DELETE' && $id) {
                verifierAdmin();
                $produitObj->supprimer(intval($id));
                jsonResponse(['success' => true, 'message' => 'Produit supprimé.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === CATÉGORIES ===
        case 'categories':
            $categorieObj = new Categorie();
            // GET : liste de toutes les catégories
            if ($method === 'GET' && !$id) {
                jsonResponse(['success' => true, 'data' => $categorieObj->getAll()]);
            // GET : détail d'une catégorie par ID
            } elseif ($method === 'GET' && $id) {
                $cat = $categorieObj->getById(intval($id));
                $cat ? jsonResponse(['success' => true, 'data' => $cat]) : jsonError('Catégorie introuvable.', 404);
            // POST : création d'une catégorie (admin seulement)
            } elseif ($method === 'POST') {
                verifierAdmin();
                $data = getJsonInput();
                $categorieObj->ajouter(securiser($data['nom']), securiser($data['description'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Catégorie créée.'], 201);
            // PUT : modification d'une catégorie (admin seulement)
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $categorieObj->modifier(intval($id), securiser($data['nom']), securiser($data['description'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Catégorie modifiée.']);
            // DELETE : suppression d'une catégorie (admin seulement)
            } elseif ($method === 'DELETE' && $id) {
                verifierAdmin();
                $categorieObj->supprimer(intval($id));
                jsonResponse(['success' => true, 'message' => 'Catégorie supprimée.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === COMMANDES ===
        case 'commandes':
            $commandeObj = new Commande();
            verifierAuth();
            // GET : liste des commandes (toutes pour admin, sinon celles de l'utilisateur)
            if ($method === 'GET' && !$id) {
                $commandes = isAdmin() ? $commandeObj->getAll() : $commandeObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $commandes]);
            // GET : détail d'une commande avec ses lignes
            } elseif ($method === 'GET' && $id) {
                $commande = $commandeObj->getById(intval($id));
                if (!$commande) jsonError('Commande introuvable.', 404);
                // Vérification des droits d'accès
                if (!isAdmin() && $commande['utilisateur_id'] != $_SESSION['user_id']) jsonError('Accès refusé.', 403);
                $lignes = $commandeObj->getLignes($commande['id']);
                $commande['lignes'] = $lignes;
                jsonResponse(['success' => true, 'data' => $commande]);
            // PUT : mise à jour du statut d'une commande (admin seulement)
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $commandeObj->updateStatut(intval($id), securiser($data['statut']));
                jsonResponse(['success' => true, 'message' => 'Statut mis à jour.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === PANIER ===
        case 'panier':
            verifierAuth();
            $panierObj = new Panier();
            // Récupération du panier actif de l'utilisateur
            $panierId = $panierObj->getPanierActif($_SESSION['user_id']);

            // GET : contenu du panier, total et nombre d'articles
            if ($method === 'GET') {
                $lignes = $panierObj->getLignes($panierId);
                $total = $panierObj->calculerTotal($panierId);
                $nbArticles = $panierObj->getNombreArticles($panierId);
                jsonResponse(['success' => true, 'data' => ['lignes' => $lignes, 'total' => $total, 'nb_articles' => $nbArticles]]);
            // POST : ajout d'un produit au panier
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $produitId = intval($data['produit_id']);
                $quantite = intval($data['quantite'] ?? 1);
                $result = $panierObj->ajouterProduit($panierId, $produitId, max(1, $quantite));
                jsonResponse(['success' => true, 'message' => 'Produit ajouté au panier.']);
            // DELETE : suppression d'une ligne du panier
            } elseif ($method === 'DELETE' && $id === 'ligne' && $subresource) {
                $panierObj->supprimerLigne(intval($subresource));
                jsonResponse(['success' => true, 'message' => 'Ligne supprimée.']);
            // DELETE : vidage complet du panier
            } elseif ($method === 'DELETE' && $id === 'vider') {
                $panierObj->vider($panierId);
                jsonResponse(['success' => true, 'message' => 'Panier vidé.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === PAIEMENTS ===
        case 'paiements':
            $paiementObj = new Paiement();
            verifierAuth();
            // GET : liste des paiements (tous pour admin, ceux de l'utilisateur sinon)
            if ($method === 'GET') {
                $paiements = isAdmin() ? $paiementObj->getAll() : $paiementObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $paiements]);
            // POST : création d'un paiement
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $commandeId = intval($data['commande_id']);
                $montant = floatval($data['montant']);
                $mode = securiser($data['mode'] ?? 'MTN Mobile Money');
                $paiementObj->creer($commandeId, $montant, $mode);
                jsonResponse(['success' => true, 'message' => 'Paiement initié.'], 201);
            // PUT : confirmation ou échec d'un paiement (admin seulement)
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $action = $data['action'] ?? '';
                if ($action === 'confirmer') $paiementObj->confirmer(intval($id));
                elseif ($action === 'echouer') $paiementObj->echouer(intval($id));
                else jsonError('Action invalide.');
                jsonResponse(['success' => true, 'message' => 'Paiement mis à jour.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === LIVRAISONS ===
        case 'livraisons':
            $livraisonObj = new Livraison();
            verifierAuth();
            // GET : liste des livraisons (toutes pour admin, celles de l'utilisateur sinon)
            if ($method === 'GET' && !$id) {
                $livraisons = isAdmin() ? $livraisonObj->getAll() : $livraisonObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $livraisons]);
            // GET : détail d'une livraison par ID
            } elseif ($method === 'GET' && $id) {
                $liv = $livraisonObj->getById(intval($id));
                $liv ? jsonResponse(['success' => true, 'data' => $liv]) : jsonError('Livraison introuvable.', 404);
            // PUT : mise à jour d'une livraison (admin seulement)
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $action = $data['action'] ?? '';
                if ($action === 'statut') $livraisonObj->updateStatut(intval($id), securiser($data['statut']));
                elseif ($action === 'assigner') $livraisonObj->assignerLivreur(intval($id), intval($data['livreur_id']));
                elseif ($action === 'confirmer') $livraisonObj->confirmerReception(intval($id));
                else jsonError('Action invalide.');
                jsonResponse(['success' => true, 'message' => 'Livraison mise à jour.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === LIVREURS ===
        case 'livreurs':
            $livreurObj = new Livreur();
            verifierAdmin();
            // GET : liste de tous les livreurs
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => $livreurObj->getAll()]);
            // POST : création d'un livreur
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $livreurObj->ajouter(
                    securiser($data['nom']),
                    securiser($data['telephone']),
                    securiser($data['email'] ?? ''),
                    securiser($data['vehicule'] ?? ''),
                    securiser($data['statut'] ?? 'Disponible'),
                    securiser($data['zone_affectation'] ?? '')
                );
                jsonResponse(['success' => true, 'message' => 'Livreur créé.'], 201);
            // PUT : modification d'un livreur
            } elseif ($method === 'PUT' && $id) {
                $data = getJsonInput();
                $livreurObj->modifier(intval($id), securiser($data['nom']), securiser($data['telephone']), securiser($data['email'] ?? ''), securiser($data['vehicule'] ?? ''), securiser($data['statut'] ?? 'Disponible'), securiser($data['zone_affectation'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Livreur modifié.']);
            // DELETE : suppression d'un livreur
            } elseif ($method === 'DELETE' && $id) {
                $livreurObj->supprimer(intval($id));
                jsonResponse(['success' => true, 'message' => 'Livreur supprimé.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === ZONES DE LIVRAISON ===
        case 'zones':
            $zoneObj = new ZoneLivraison();
            // GET : liste des zones actives
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => $zoneObj->getActives()]);
            // POST : création d'une zone (admin seulement)
            } elseif ($method === 'POST') {
                verifierAdmin();
                $data = getJsonInput();
                $zoneObj->ajouter(securiser($data['nom']), securiser($data['description'] ?? ''), floatval($data['tarif']));
                jsonResponse(['success' => true, 'message' => 'Zone créée.'], 201);
            // PUT : modification d'une zone (admin seulement)
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $zoneObj->modifier(intval($id), securiser($data['nom']), securiser($data['description'] ?? ''), floatval($data['tarif']));
                jsonResponse(['success' => true, 'message' => 'Zone modifiée.']);
            // DELETE : suppression d'une zone (admin seulement)
            } elseif ($method === 'DELETE' && $id) {
                verifierAdmin();
                $zoneObj->supprimer(intval($id));
                jsonResponse(['success' => true, 'message' => 'Zone supprimée.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === AVIS ===
        case 'avis':
            $avisObj = new Avis();
            verifierAuth();
            // GET : liste des avis (tous pour admin, ceux de l'utilisateur sinon)
            if ($method === 'GET') {
                $avisList = isAdmin() ? $avisObj->getAll() : $avisObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $avisList]);
            // POST : création d'un avis
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $avisObj->ajouter(intval($data['produit_id']), $_SESSION['user_id'], intval($data['note']), securiser($data['commentaire'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Avis ajouté.'], 201);
            // DELETE : suppression d'un avis (admin seulement)
            } elseif ($method === 'DELETE' && $id) {
                verifierAdmin();
                $avisObj->supprimer(intval($id));
                jsonResponse(['success' => true, 'message' => 'Avis supprimé.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === UTILISATEURS ===
        case 'utilisateurs':
            verifierAdmin();
            $utilisateurObj = new Utilisateur();
            // GET : liste de tous les utilisateurs
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => $utilisateurObj->getAll()]);
            // PUT : mise à jour du rôle d'un utilisateur
            } elseif ($method === 'PUT' && $id) {
                $data = getJsonInput();
                $utilisateurObj->updateRole(intval($id), securiser($data['role']));
                jsonResponse(['success' => true, 'message' => 'Rôle mis à jour.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === NOTIFICATIONS ===
        case 'notifications':
            verifierAuth();
            $notifObj = new Notification();
            // GET : liste des notifications
            if ($method === 'GET') {
                $notifs = isAdmin() ? $notifObj->getAll() : $notifObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $notifs]);
            // POST : marquer toutes les notifications comme lues
            } elseif ($method === 'POST' && $id === 'marquer-lu') {
                $notifObj->marquerToutesLues($_SESSION['user_id']);
                jsonResponse(['success' => true, 'message' => 'Notifications marquées lues.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === STATISTIQUES (Admin) ===
        case 'statistiques':
            verifierAdmin();
            // Instanciation des objets nécessaires aux statistiques
            $produitObj = new Produit();
            $commandeObj = new Commande();
            $utilisateurObj = new Utilisateur();
            $paiementObj = new Paiement();
            $avisObj = new Avis();

            // Agrégation de toutes les statistiques
            $stats = [
                'produits' => $produitObj->getNombre(),
                'stock_faible' => $produitObj->getStockFaible(),
                'commandes' => $commandeObj->getNombre(),
                'commandes_encours' => $commandeObj->getEnCours(),
                'commandes_jour' => $commandeObj->getCommandesDuJour(),
                'total_ventes' => $commandeObj->getTotalVentes(),
                'utilisateurs' => $utilisateurObj->getNombre(),
                'nouveaux_utilisateurs' => $utilisateurObj->getNouveauxCeMois(),
                'total_paiements' => $paiementObj->getTotalPaiements(),
                'avis' => $avisObj->getNombre(),
                'ventes_7_jours' => $commandeObj->getVentes7Jours(),
                'statuts_commandes' => $commandeObj->getRepartitionStatuts(),
                'statistiques_mensuelles' => $commandeObj->getStatistiquesMois()
            ];
            jsonResponse(['success' => true, 'data' => $stats]);
            break;

        // === AUTH ===
        case 'auth':
            // POST : connexion de l'utilisateur
            if ($method === 'POST') {
                $data = getJsonInput();
                $email = securiser($data['email']);
                $motDePasse = $data['mot_de_passe'];
                $utilisateur = new Utilisateur();
                $user = $utilisateur->verifierMotDePasse($email, $motDePasse);
                // Si l'utilisateur existe, création de la session
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $utilisateur->updateDerniereConnexion($user['id']);
                    jsonResponse(['success' => true, 'message' => 'Connecté.', 'user' => [
                        'id' => $user['id'], 'nom' => $user['nom'], 'prenom' => $user['prenom'],
                        'email' => $user['email'], 'role' => $user['role']
                    ]]);
                } else {
                    // Échec de l'authentification
                    jsonError('Email ou mot de passe incorrect.', 401);
                }
            // DELETE : déconnexion de l'utilisateur
            } elseif ($method === 'DELETE') {
                session_destroy();
                jsonResponse(['success' => true, 'message' => 'Déconnecté.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // Endpoint par défaut : introuvable
        default:
            jsonError('Endpoint introuvable.', 404);
    }
// Capture des exceptions et renvoi d'une erreur 500
} catch (Exception $e) {
    jsonError('Erreur serveur: ' . $e->getMessage(), 500);
}
