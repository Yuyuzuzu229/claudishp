<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

$pdo = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

$endpoint = $_GET['endpoint'] ?? '';
$parts = explode('/', trim($endpoint, '/'));
$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;
$subresource = $parts[2] ?? null;

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $code = 400) {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function verifierAuth() {
    if (!isLoggedIn()) {
        jsonError('Non authentifié.', 401);
    }
}

function verifierAdmin() {
    verifierAuth();
    if (!isAdmin()) {
        jsonError('Accès refusé.', 403);
    }
}

try {
    switch ($resource) {
        // === PRODUITS ===
        case 'produits':
            $produitObj = new Produit();
            if ($method === 'GET' && !$id) {
                $produits = $produitObj->getAll();
                jsonResponse(['success' => true, 'data' => $produits]);
            } elseif ($method === 'GET' && $id) {
                $produit = $produitObj->getById(intval($id));
                $produit ? jsonResponse(['success' => true, 'data' => $produit]) : jsonError('Produit introuvable.', 404);
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
            if ($method === 'GET' && !$id) {
                jsonResponse(['success' => true, 'data' => $categorieObj->getAll()]);
            } elseif ($method === 'GET' && $id) {
                $cat = $categorieObj->getById(intval($id));
                $cat ? jsonResponse(['success' => true, 'data' => $cat]) : jsonError('Catégorie introuvable.', 404);
            } elseif ($method === 'POST') {
                verifierAdmin();
                $data = getJsonInput();
                $categorieObj->ajouter(securiser($data['nom']), securiser($data['description'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Catégorie créée.'], 201);
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $categorieObj->modifier(intval($id), securiser($data['nom']), securiser($data['description'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Catégorie modifiée.']);
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
            if ($method === 'GET' && !$id) {
                $commandes = isAdmin() ? $commandeObj->getAll() : $commandeObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $commandes]);
            } elseif ($method === 'GET' && $id) {
                $commande = $commandeObj->getById(intval($id));
                if (!$commande) jsonError('Commande introuvable.', 404);
                if (!isAdmin() && $commande['utilisateur_id'] != $_SESSION['user_id']) jsonError('Accès refusé.', 403);
                $lignes = $commandeObj->getLignes($commande['id']);
                $commande['lignes'] = $lignes;
                jsonResponse(['success' => true, 'data' => $commande]);
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
            $panierId = $panierObj->getPanierActif($_SESSION['user_id']);

            if ($method === 'GET') {
                $lignes = $panierObj->getLignes($panierId);
                $total = $panierObj->calculerTotal($panierId);
                $nbArticles = $panierObj->getNombreArticles($panierId);
                jsonResponse(['success' => true, 'data' => ['lignes' => $lignes, 'total' => $total, 'nb_articles' => $nbArticles]]);
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $produitId = intval($data['produit_id']);
                $quantite = intval($data['quantite'] ?? 1);
                $result = $panierObj->ajouterProduit($panierId, $produitId, max(1, $quantite));
                jsonResponse(['success' => true, 'message' => 'Produit ajouté au panier.']);
            } elseif ($method === 'DELETE' && $id === 'ligne' && $subresource) {
                $panierObj->supprimerLigne(intval($subresource));
                jsonResponse(['success' => true, 'message' => 'Ligne supprimée.']);
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
            if ($method === 'GET') {
                $paiements = isAdmin() ? $paiementObj->getAll() : $paiementObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $paiements]);
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $commandeId = intval($data['commande_id']);
                $montant = floatval($data['montant']);
                $mode = securiser($data['mode'] ?? 'MTN Mobile Money');
                $paiementObj->creer($commandeId, $montant, $mode);
                jsonResponse(['success' => true, 'message' => 'Paiement initié.'], 201);
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
            if ($method === 'GET' && !$id) {
                $livraisons = isAdmin() ? $livraisonObj->getAll() : $livraisonObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $livraisons]);
            } elseif ($method === 'GET' && $id) {
                $liv = $livraisonObj->getById(intval($id));
                $liv ? jsonResponse(['success' => true, 'data' => $liv]) : jsonError('Livraison introuvable.', 404);
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
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => $livreurObj->getAll()]);
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
            } elseif ($method === 'PUT' && $id) {
                $data = getJsonInput();
                $livreurObj->modifier(intval($id), securiser($data['nom']), securiser($data['telephone']), securiser($data['email'] ?? ''), securiser($data['vehicule'] ?? ''), securiser($data['statut'] ?? 'Disponible'), securiser($data['zone_affectation'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Livreur modifié.']);
            } elseif ($method === 'DELETE' && $id) {
                $livreurObj->supprimer(intval($id));
                jsonResponse(['success' => true, 'message' => 'Livreur supprimé.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        // === ZONES ===
        case 'zones':
            $zoneObj = new ZoneLivraison();
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => $zoneObj->getActives()]);
            } elseif ($method === 'POST') {
                verifierAdmin();
                $data = getJsonInput();
                $zoneObj->ajouter(securiser($data['nom']), securiser($data['description'] ?? ''), floatval($data['tarif']));
                jsonResponse(['success' => true, 'message' => 'Zone créée.'], 201);
            } elseif ($method === 'PUT' && $id) {
                verifierAdmin();
                $data = getJsonInput();
                $zoneObj->modifier(intval($id), securiser($data['nom']), securiser($data['description'] ?? ''), floatval($data['tarif']));
                jsonResponse(['success' => true, 'message' => 'Zone modifiée.']);
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
            if ($method === 'GET') {
                $avisList = isAdmin() ? $avisObj->getAll() : $avisObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $avisList]);
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $avisObj->ajouter(intval($data['produit_id']), $_SESSION['user_id'], intval($data['note']), securiser($data['commentaire'] ?? ''));
                jsonResponse(['success' => true, 'message' => 'Avis ajouté.'], 201);
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
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => $utilisateurObj->getAll()]);
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
            if ($method === 'GET') {
                $notifs = isAdmin() ? $notifObj->getAll() : $notifObj->getByUtilisateur($_SESSION['user_id']);
                jsonResponse(['success' => true, 'data' => $notifs]);
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
            $produitObj = new Produit();
            $commandeObj = new Commande();
            $utilisateurObj = new Utilisateur();
            $paiementObj = new Paiement();
            $avisObj = new Avis();

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
            if ($method === 'POST') {
                $data = getJsonInput();
                $email = securiser($data['email']);
                $motDePasse = $data['mot_de_passe'];
                $utilisateur = new Utilisateur();
                $user = $utilisateur->verifierMotDePasse($email, $motDePasse);
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
                    jsonError('Email ou mot de passe incorrect.', 401);
                }
            } elseif ($method === 'DELETE') {
                session_destroy();
                jsonResponse(['success' => true, 'message' => 'Déconnecté.']);
            } else {
                jsonError('Méthode non autorisée.', 405);
            }
            break;

        default:
            jsonError('Endpoint introuvable.', 404);
    }
} catch (Exception $e) {
    jsonError('Erreur serveur: ' . $e->getMessage(), 500);
}
