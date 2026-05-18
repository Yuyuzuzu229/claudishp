<?php
// ============================================================
// CLAUDISHOP - Configuration & Données de démonstration
// ============================================================

define('SITE_NAME', 'ClaudiShop');
define('SITE_URL', 'http://localhost/claudishop');
define('CURRENCY', 'FCFA');

// --- Données fictives produits ---
$produits = [
    ['id'=>1,'nom'=>'Robe Wax fleurie','categorie'=>'Femme','prix'=>32500,'stock'=>18,'statut'=>'actif','sku'=>'RWX-00142','image'=>''],
    ['id'=>2,'nom'=>'Chemise Homme Slim','categorie'=>'Homme','prix'=>18000,'stock'=>35,'statut'=>'actif','sku'=>'CHS-00141','image'=>''],
    ['id'=>3,'nom'=>'Basket cuir mixte','categorie'=>'Accessoires','prix'=>45000,'stock'=>12,'statut'=>'actif','sku'=>'BSK-00140','image'=>''],
    ['id'=>4,'nom'=>'Sac à main Élégance','categorie'=>'Accessoires','prix'=>28500,'stock'=>8,'statut'=>'actif','sku'=>'SAC-00139','image'=>''],
    ['id'=>5,'nom'=>'Parfum Musc Royal','categorie'=>'Beauté','prix'=>22000,'stock'=>26,'statut'=>'actif','sku'=>'PAR-00138','image'=>''],
    ['id'=>6,'nom'=>'Montre Classique','categorie'=>'Accessoires','prix'=>39900,'stock'=>15,'statut'=>'actif','sku'=>'MTR-00137','image'=>''],
    ['id'=>7,'nom'=>'Lunettes de soleil','categorie'=>'Accessoires','prix'=>15900,'stock'=>22,'statut'=>'inactif','sku'=>'LUN-00136','image'=>''],
    ['id'=>8,'nom'=>'Ensemble Wax enfant','categorie'=>'Enfant','prix'=>8900,'stock'=>30,'statut'=>'actif','sku'=>'EWX-00135','image'=>''],
    ['id'=>9,'nom'=>'Jean Slim Fit','categorie'=>'Homme','prix'=>21500,'stock'=>19,'statut'=>'actif','sku'=>'JNS-00134','image'=>''],
    ['id'=>10,'nom'=>'Ceinture en cuir','categorie'=>'Accessoires','prix'=>9900,'stock'=>31,'statut'=>'actif','sku'=>'CEN-00133','image'=>''],
    ['id'=>11,'nom'=>'Hoodie Premium Homme','categorie'=>'Homme','prix'=>45000,'stock'=>14,'statut'=>'actif','sku'=>'HOD-00132','image'=>''],
    ['id'=>12,'nom'=>'Robe longue imprimée','categorie'=>'Femme','prix'=>24500,'stock'=>22,'statut'=>'actif','sku'=>'RLI-00131','image'=>''],
];

// --- Données fictives commandes ---
$commandes = [
    ['id'=>'#C-4822','client'=>'Adjoua K.','initiales'=>'AK','date'=>'13/05/26 10:30','montant'=>'95 000','paiement'=>'MTN Moov','statut_paiement'=>'Payé','statut_commande'=>'Livrée'],
    ['id'=>'#C-4821','client'=>'Didier M.','initiales'=>'DM','date'=>'13/05/26 10:30','montant'=>'32 000','paiement'=>'MTN Moov','statut_paiement'=>'Payé','statut_commande'=>'En route'],
    ['id'=>'#C-4820','client'=>'Grace C.','initiales'=>'GT','date'=>'13/05/26 10:30','montant'=>'22 000','paiement'=>'Cartes','statut_paiement'=>'En attente','statut_commande'=>'En préparation'],
    ['id'=>'#C-4819','client'=>'Grace T.','initiales'=>'GT','date'=>'13/05/26 10:30','montant'=>'78 000','paiement'=>'MTN Moov','statut_paiement'=>'Payé','statut_commande'=>'Annulée'],
    ['id'=>'#C-4818','client'=>'Moussa K.','initiales'=>'MK','date'=>'13/05/26 10:30','montant'=>'56 600','paiement'=>'Cartes','statut_paiement'=>'Payé','statut_commande'=>'En attente'],
    ['id'=>'#C-4816','client'=>'Fatou S.','initiales'=>'FS','date'=>'13/05/26 10:30','montant'=>'22 000','paiement'=>'Cartes','statut_paiement'=>'En attente','statut_commande'=>'Livrée'],
    ['id'=>'#C-4815','client'=>'Brice H.','initiales'=>'BH','date'=>'13/05/26 10:30','montant'=>'44 500','paiement'=>'Cartes','statut_paiement'=>'En attente','statut_commande'=>'En préparation'],
];

// --- Utilisateurs ---
$utilisateurs = [
    ['id'=>'#U248','initiales'=>'AK','nom'=>'Adjoua K.','email'=>'adjoua.k@mail.com','role'=>'Administrateur','statut'=>'Actif','derniere_connexion'=>'13/05/26 à 09:45'],
    ['id'=>'#U247','initiales'=>'DM','nom'=>'Didier M.','email'=>'didier.m@mail.com','role'=>'Gestionnaire','statut'=>'Actif','derniere_connexion'=>'13/05/26 à 08:32'],
    ['id'=>'#U246','initiales'=>'GT','nom'=>'Grace T.','email'=>'grace.t@mail.com','role'=>'Responsable logistique','statut'=>'Actif','derniere_connexion'=>'12/05/26 à 17:20'],
    ['id'=>'#U245','initiales'=>'MK','nom'=>'Moussa K.','email'=>'moussa.k@mail.com','role'=>'Gestionnaire','statut'=>'Actif','derniere_connexion'=>'12/05/26 à 11:05'],
    ['id'=>'#U244','initiales'=>'FS','nom'=>'Fatou S.','email'=>'fatou.s@mail.com','role'=>'Support client','statut'=>'Actif','derniere_connexion'=>'11/05/26 à 16:40'],
    ['id'=>'#U243','initiales'=>'BH','nom'=>'Brice H.','email'=>'brice.h@mail.com','role'=>'Comptable','statut'=>'Actif','derniere_connexion'=>'11/05/26 à 10:15'],
    ['id'=>'#U242','initiales'=>'AL','nom'=>'Alain L.','email'=>'alain.l@mail.com','role'=>'Livreur','statut'=>'Inactif','derniere_connexion'=>'08/05/26 à 14:22'],
    ['id'=>'#U241','initiales'=>'CM','nom'=>'Cécile M.','email'=>'cecile.m@mail.com','role'=>'Support client','statut'=>'Actif','derniere_connexion'=>'08/05/26 à 09:10'],
];

// --- Livraisons ---
$livraisons = [
    ['id'=>'#L-9960','commande'=>'#C-4822','initiales'=>'AK','client'=>'Adjoua K.','zone'=>'Cotonou Zone 1','livreur'=>'Paul D.','date_prevue'=>'14/05/26','frais'=>'2 500','statut'=>'En attente'],
    ['id'=>'#L-9961','commande'=>'#C-4821','initiales'=>'DM','client'=>'Didier M.','zone'=>'Cotonou Zone 1','livreur'=>'Jean-Pierre K.','date_prevue'=>'14/05/26','frais'=>'2 500','statut'=>'Prêt à expédier'],
    ['id'=>'#L-9962','commande'=>'#C-4820','initiales'=>'GT','client'=>'Grace T.','zone'=>'Abomey-Calavi','livreur'=>'Jean-Pierre K.','date_prevue'=>'14/05/26','frais'=>'2 500','statut'=>'Prêt à expédier'],
    ['id'=>'#L-9963','commande'=>'#C-4819','initiales'=>'GT','client'=>'Grace T.','zone'=>'Abomey-Calavi','livreur'=>'Non assigné','date_prevue'=>'14/05/26','frais'=>'2 500','statut'=>'En cours de livraison'],
    ['id'=>'#L-9964','commande'=>'#C-4818','initiales'=>'MK','client'=>'Moussa K.','zone'=>'Abomey-Calavi','livreur'=>'Non assigné','date_prevue'=>'14/05/26','frais'=>'2 500','statut'=>'En cours de livraison'],
    ['id'=>'#L-9966','commande'=>'#C-4816','initiales'=>'FS','client'=>'Fatou S.','zone'=>'Abomey-Calavi','livreur'=>'Non assigné','date_prevue'=>'14/05/26','frais'=>'2 500','statut'=>'Livrée'],
];

// --- Zones de livraison ---
$zones = [
    ['id'=>1,'nom'=>'Cotonou Zone 1','description'=>'Centre-ville et Plateau','frais'=>'1 500','statut'=>'Actif','nb_commandes'=>48],
    ['id'=>2,'nom'=>'Cotonou Zone 2','description'=>'Akpakpa, Aidjèdo, Cadjèhoun','frais'=>'2 000','statut'=>'Actif','nb_commandes'=>36],
    ['id'=>3,'nom'=>'Abomey-Calavi','description'=>'Abomey-Calavi et environs','frais'=>'2 500','statut'=>'Actif','nb_commandes'=>29],
    ['id'=>4,'nom'=>'Porto-Novo','description'=>'Capitale administrative','frais'=>'3 500','statut'=>'Actif','nb_commandes'=>18],
    ['id'=>5,'nom'=>'Parakou','description'=>'Nord Bénin – ville principale','frais'=>'5 000','statut'=>'Inactif','nb_commandes'=>7],
];

// --- Livreurs ---
$livreurs = [
    ['id'=>1,'nom'=>'Paul Dossou','telephone'=>'+229 97 11 22 33','email'=>'paul.d@mail.com','vehicule'=>'Moto','zone'=>'Cotonou Zone 1','statut'=>'Disponible','est_actif'=>true,'date_embauche'=>'01/01/2025'],
    ['id'=>2,'nom'=>'Jean-Pierre Koudé','telephone'=>'+229 97 44 55 66','email'=>'jp.k@mail.com','vehicule'=>'Moto','zone'=>'Cotonou Zone 1, Zone 2','statut'=>'En livraison','est_actif'=>true,'date_embauche'=>'15/02/2025'],
    ['id'=>3,'nom'=>'Koffi Adé','telephone'=>'+229 90 12 34 56','email'=>'koffi.a@mail.com','vehicule'=>'Voiture','zone'=>'Abomey-Calavi','statut'=>'Disponible','est_actif'=>true,'date_embauche'=>'10/03/2025'],
    ['id'=>4,'nom'=>'Sènan Houngbé','telephone'=>'+229 96 77 88 99','email'=>'senan.h@mail.com','vehicule'=>'Moto','zone'=>'Porto-Novo','statut'=>'Inactif','est_actif'=>false,'date_embauche'=>'05/04/2025'],
];

// --- Avis clients ---
$avis = [
    ['id'=>1,'client'=>'Adjoua K.','initiales'=>'AK','produit'=>'T-shirt Oversize Noir','note'=>5,'commentaire'=>'Très bonne qualité de tissu, je recommande !','date'=>'13/05/2026','statut'=>'Publié'],
    ['id'=>2,'client'=>'Didier M.','initiales'=>'DM','produit'=>'Chemise lin blanc','note'=>4,'commentaire'=>'Chemise de qualité, taille conforme. Paiement Moov OK.','date'=>'12/05/2026','statut'=>'Publié'],
    ['id'=>3,'client'=>'Grace T.','initiales'=>'GT','produit'=>'Ensemble pour ma fille','note'=>5,'commentaire'=>'Ensemble pour ma fille, très beau. Livraison rapide à Cotonou.','date'=>'11/05/2026','statut'=>'En modération'],
    ['id'=>4,'client'=>'Moussa K.','initiales'=>'MK','produit'=>'Basket cuir mixte','note'=>3,'commentaire'=>'Produit correct mais livraison un peu longue.','date'=>'10/05/2026','statut'=>'Refusé'],
];

// --- Catégories ---
$categories = [
    ['id'=>1,'nom'=>'Femme','description'=>'Vêtements et accessoires femme','nb_produits'=>58,'statut'=>'Actif'],
    ['id'=>2,'nom'=>'Homme','description'=>'Vêtements et accessoires homme','nb_produits'=>42,'statut'=>'Actif'],
    ['id'=>3,'nom'=>'Enfant','description'=>'Mode enfant 0-16 ans','nb_produits'=>24,'statut'=>'Actif'],
    ['id'=>4,'nom'=>'Accessoires','description'=>'Sacs, ceintures, lunettes, bijoux','nb_produits'=>18,'statut'=>'Actif'],
    ['id'=>5,'nom'=>'Beauté & Santé','description'=>'Soins et cosmétiques','nb_produits'=>12,'statut'=>'Actif'],
    ['id'=>6,'nom'=>'Soldes','description'=>'Articles en promotion','nb_produits'=>7,'statut'=>'Inactif'],
];

// --- Paiements ---
$paiements = [
    ['id'=>'#P-1284','commande'=>'#C-4822','client'=>'Adjoua K.','montant'=>'95 000','mode'=>'MTN MoMo','statut'=>'Confirmé','date'=>'13/05/26 10:32','reference'=>'MTN-2026-84921'],
    ['id'=>'#P-1283','commande'=>'#C-4821','client'=>'Didier M.','montant'=>'32 500','mode'=>'MTN MoMo','statut'=>'Confirmé','date'=>'13/05/26 09:18','reference'=>'MTN-2026-84820'],
    ['id'=>'#P-1282','commande'=>'#C-4820','client'=>'Grace T.','montant'=>'178 500','mode'=>'Moov Money','statut'=>'En attente','date'=>'12/05/26 17:05','reference'=>'MOV-2026-77341'],
    ['id'=>'#P-1281','commande'=>'#C-4819','client'=>'Moussa K.','montant'=>'56 000','mode'=>'MTN MoMo','statut'=>'Échoué','date'=>'11/05/26 14:30','reference'=>'MTN-2026-71209'],
    ['id'=>'#P-1280','commande'=>'#C-4818','client'=>'Fatou S.','montant'=>'22 000','mode'=>'Moov Money','statut'=>'Confirmé','date'=>'11/05/26 11:20','reference'=>'MOV-2026-70088'],
];

// Helpers
function badge_statut_commande($statut) {
    $map = [
        'Livrée'         => 'badge-green',
        'En route'       => 'badge-blue',
        'En préparation' => 'badge-orange',
        'En attente'     => 'badge-gray',
        'Annulée'        => 'badge-red',
        'Confirmée'      => 'badge-blue',
    ];
    $cls = $map[$statut] ?? 'badge-gray';
    return "<span class='badge $cls'>$statut</span>";
}

function badge_paiement($statut) {
    $map = [
        'Confirmé'   => 'badge-green',
        'Payé'       => 'badge-green',
        'En attente' => 'badge-orange',
        'Échoué'     => 'badge-red',
    ];
    $cls = $map[$statut] ?? 'badge-gray';
    return "<span class='badge $cls'>$statut</span>";
}

function badge_statut_user($statut) {
    return $statut === 'Actif'
        ? "<span class='badge badge-green'>✓ Actif</span>"
        : "<span class='badge badge-gray'>○ Inactif</span>";
}

function badge_avis($statut) {
    $map = ['Publié'=>'badge-green','En modération'=>'badge-orange','Refusé'=>'badge-red'];
    $cls = $map[$statut] ?? 'badge-gray';
    return "<span class='badge $cls'>$statut</span>";
}

function stars($n) {
    $out = '';
    for($i=1;$i<=5;$i++) $out .= $i<=$n ? '★' : '☆';
    return "<span class='stars'>$out</span>";
}

function initials_avatar($initiales, $size=32) {
    return "<div class='avatar' style='width:{$size}px;height:{$size}px;border-radius:50%;background:#1A1A1A;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:".($size*0.38)."px;font-weight:600;'>$initiales</div>";
}
?>
