# CLAUDISHOP - E-commerce de prêt-à-porter

Boutique en ligne spécialisée dans la vente de vêtements et accessoires avec un design épuré aux accents dorés (#C9A03D).

## Stack technique

- **Backend** : PHP 8+ natif (sans framework)
- **Base de données** : MySQL/MariaDB via PDO
- **Frontend** : CSS maison + Font Awesome 6.5.1
- **Typographie** : Inter (corps) / Poppins (titres) via Google Fonts
- **Paiement** : MTN Mobile Money, Moov Money (simulation)

## Structure du projet

```
CLAUDISHOP/
├── actions/          # Traitements PHP (inscription, connexion, panier, commande...)
├── admin/            # Back-office administrateur (dashboard, CRUD)
├── api/              # API REST (endpoints JSON)
├── assets/
│   ├── css/          # style.css (thème complet)
│   ├── images/       # Logos SVG, icônes de paiement, placeholders
│   └── js/           # script.js (interactions front)
├── classes/          # Modèles PHP (Utilisateur, Produit, Commande, Panier...)
├── config/           # Configuration (base de données, sessions, helpers)
├── database/         # Schéma SQL + données de démo
├── includes/         # Templates partiels (header, navbar, footer, sidebar)
├── pages/            # Pages front-office (boutique, panier, checkout, auth...)
├── user/             # Espace client (dashboard, commandes, adresses, avis...)
├── uploads/          # Photos produits (upload)
├── index.php         # Page d'accueil
├── .htaccess         # Réécriture d'URL
└── README.md
```

## Installation

### 1. Cloner le projet
```
git clone <url-du-repo> C:\wamp64\www\CLAUDISHOP
```

### 2. Configurer la base de données
- Lancer WAMP/XAMPP
- Ouvrir phpMyAdmin
- Importer `database/claudishop_bd.sql` (crée la base + tables + données démo)

### 3. Configurer l'accès
Paramètres MySQL par défaut dans `config/database.php` :
- Hôte : `localhost`
- Base : `claudishop_bd`
- Utilisateur : `root`
- Mot de passe : (vide)

Modifier si nécessaire.

### 4. Ajuster l'URL de base
Dans `config/config.php`, modifier :
```php
define('BASE_URL', 'http://localhost/CLAUDISHOP');
```
Ajuster selon votre configuration (port, chemin).

### 5. Activer la réécriture d'URL
- **WAMP** : Cliquer sur l'icône WAMP → Apache → Apache modules → cocher `rewrite_module`
- **XAMPP** : Dans `httpd.conf`, décommenter `LoadModule rewrite_module modules/mod_rewrite.so`
- Dans `httpd.conf`, après `<Directory "C:/wamp64/www/">`, ajouter :
  ```
  AllowOverride All
  ```

### 6. Créer le dossier uploads
```bash
mkdir uploads
```
Donner les droits d'écriture si nécessaire.

## Accès

### Compte administrateur
- Email : `admin@claudishop.com`
- Mot de passe : `admin123`

### Compte utilisateur
Créer un compte via la page d'inscription.

## API REST

Tous les endpoints disponibles sous `/api/` :

### Produits
- `GET /api/produits` — Liste des produits
- `GET /api/produits/{id}` — Détail d'un produit
- `POST /api/produits` — Créer (admin)
- `PUT /api/produits/{id}` — Modifier (admin)
- `DELETE /api/produits/{id}` — Supprimer (admin)

### Catégories
- `GET /api/categories` — Liste
- `POST /api/categories` — Créer (admin)
- `PUT /api/categories/{id}` — Modifier (admin)
- `DELETE /api/categories/{id}` — Supprimer (admin)

### Commandes
- `GET /api/commandes` — Liste (utilisateur = ses commandes, admin = toutes)
- `GET /api/commandes/{id}` — Détail
- `PUT /api/commandes/{id}` — Mettre à jour le statut (admin)

### Panier
- `GET /api/panier` — Voir le panier
- `POST /api/panier` — Ajouter un produit
- `DELETE /api/panier/ligne/{id}` — Supprimer une ligne
- `DELETE /api/panier/vider` — Vider le panier

### Paiements
- `GET /api/paiements` — Liste
- `POST /api/paiements` — Initier un paiement
- `PUT /api/paiements/{id}` — Confirmer/échouer (admin)

### Livraisons
- `GET /api/livraisons` — Liste
- `GET /api/livraisons/{id}` — Détail
- `PUT /api/livraisons/{id}` — Actions (statut, assigner, confirmer)

### Statistiques
- `GET /api/statistiques` — Dashboard admin

### Authentification
- `POST /api/auth` — Connexion
- `DELETE /api/auth` — Déconnexion

## Fonctionnalités

### Front-office
- Catalogue produits avec filtres (catégorie, prix, stock)
- Fiche produit détaillée avec avis clients
- Panier d'achat (ajout, modification, suppression)
- Checkout (mode retrait, adresse, paiement MTN/Moov)
- Inscription / Connexion
- Espace client (profil, commandes, avis, adresses, notifications)

### Back-office
- Dashboard (KPI : produits, commandes, revenus, utilisateurs)
- CRUD Produits (nom, prix, stock, photo, catégorie)
- CRUD Catégories
- CRUD Zones de livraison
- CRUD Livreurs
- Gestion des commandes (détail, mise à jour statut, contact WhatsApp)
- Gestion des livraisons (statut, assignation livreur)
- Gestion des paiements (confirmation)
- Gestion des utilisateurs
- Gestion des avis clients
- Gestion des notifications

## Captures d'écran

(À ajouter)

## License

Projet privé - Tous droits réservés.
