<?php
/**
 * Fonctions liées à la gestion des réservations
 */

/**
 * Vérifie la disponibilité d'un produit
 * 
 * @param mysqli $link Connexion à la base de données
 * @param int $id_produit ID du produit
 * @param int $quantite_demandee Quantité demandée
 * @return array Tableau contenant 'disponible' (booléen) et 'quantite_disponible' (entier)
 */
function checkDisponibiliteProduit($link, $id_produit, $quantite_demandee) {
    // Récupérer la quantité totale disponible du produit
    $query_stock = "SELECT SUM(quantite) as quantite_disponible 
                    FROM inventaire 
                    WHERE id_produit = ?";
    $stmt_stock = $link->prepare($query_stock);
    $stmt_stock->bind_param("i", $id_produit);
    $stmt_stock->execute();
    $result_stock = $stmt_stock->get_result();
    $row_stock = $result_stock->fetch_assoc();
    
    if (!$row_stock || is_null($row_stock['quantite_disponible'])) {
        return ['disponible' => false, 'quantite_disponible' => 0];
    }
    
    $quantite_totale = $row_stock['quantite_disponible'];
    
    // Récupérer les quantités déjà réservées
    $query_reservations = "
        SELECT SUM(quantite) as quantite_reservee
        FROM reservations
        WHERE id_produit = ?
        AND statut IN ('en_attente', 'approuvee')
    ";
    
    $stmt_reservations = $link->prepare($query_reservations);
    $stmt_reservations->bind_param("i", $id_produit);
    $stmt_reservations->execute();
    $result_reservations = $stmt_reservations->get_result();
    $row_reservations = $result_reservations->fetch_assoc();
    
    $quantite_reservee = $row_reservations['quantite_reservee'] ?: 0;
    $quantite_disponible = $quantite_totale - $quantite_reservee;
    
    return [
        'disponible' => $quantite_disponible >= $quantite_demandee,
        'quantite_disponible' => $quantite_disponible
    ];
}

/**
 * Récupère la liste des réservations avec filtres et pagination
 * 
 * @param mysqli $link Connexion à la base de données
 * @param array $filtres Tableau des filtres (statut, id_utilisateur, id_produit, date_debut, date_fin)
 * @param int $page Page actuelle
 * @param int $par_page Nombre d'éléments par page
 * @return array Liste des réservations
 */
function getReservations($link, $filtres = [], $page = 1, $par_page = 10) {
    $offset = ($page - 1) * $par_page;
    
    $conditions = [];
    $params = [];
    $types = "";
    
    // Appliquer les filtres
    if (!empty($filtres['statut'])) {
        $conditions[] = "r.statut = ?";
        $params[] = $filtres['statut'];
        $types .= "s";
    }
    
    if (!empty($filtres['id_utilisateur'])) {
        $conditions[] = "r.id_utilisateur = ?";
        $params[] = $filtres['id_utilisateur'];
        $types .= "i";
    }
    
    if (!empty($filtres['id_produit'])) {
        $conditions[] = "r.id_produit = ?";
        $params[] = $filtres['id_produit'];
        $types .= "i";
    }
    
    if (!empty($filtres['date_debut'])) {
        $conditions[] = "r.date_debut >= ?";
        $params[] = $filtres['date_debut'];
        $types .= "s";
    }
    
    if (!empty($filtres['date_fin'])) {
        $conditions[] = "r.date_fin <= ?";
        $params[] = $filtres['date_fin'];
        $types .= "s";
    }
    
    $where_clause = "";
    if (!empty($conditions)) {
        $where_clause = "WHERE " . implode(" AND ", $conditions);
    }
    
    $query = "
        SELECT r.*, 
               p.reference, p.nom_produit,
               CONCAT(u.prenom, ' ', u.nom) AS nom_complet
        FROM reservations r
        JOIN produits p ON r.id_produit = p.id_produit
        JOIN utilisateurs u ON r.id_utilisateur = u.id_utilisateur
        $where_clause
        ORDER BY r.date_demande DESC
        LIMIT ?, ?
    ";
    
    // Ajouter les paramètres de pagination
    $params[] = $offset;
    $params[] = $par_page;
    $types .= "ii";
    
    $stmt = $link->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    return $reservations;
}

/**
 * Compte le nombre total de réservations avec filtres
 * 
 * @param mysqli $link Connexion à la base de données
 * @param array $filtres Tableau des filtres (statut, id_utilisateur, id_produit, date_debut, date_fin)
 * @return int Nombre total de réservations
 */
function countReservations($link, $filtres = []) {
    $conditions = [];
    $params = [];
    $types = "";
    
    // Appliquer les filtres
    if (!empty($filtres['statut'])) {
        $conditions[] = "statut = ?";
        $params[] = $filtres['statut'];
        $types .= "s";
    }
    
    if (!empty($filtres['id_utilisateur'])) {
        $conditions[] = "id_utilisateur = ?";
        $params[] = $filtres['id_utilisateur'];
        $types .= "i";
    }
    
    if (!empty($filtres['id_produit'])) {
        $conditions[] = "id_produit = ?";
        $params[] = $filtres['id_produit'];
        $types .= "i";
    }
    
    if (!empty($filtres['date_debut'])) {
        $conditions[] = "date_debut >= ?";
        $params[] = $filtres['date_debut'];
        $types .= "s";
    }
    
    if (!empty($filtres['date_fin'])) {
        $conditions[] = "date_fin <= ?";
        $params[] = $filtres['date_fin'];
        $types .= "s";
    }
    
    $where_clause = "";
    if (!empty($conditions)) {
        $where_clause = "WHERE " . implode(" AND ", $conditions);
    }
    
    $query = "SELECT COUNT(*) as total FROM reservations $where_clause";
    
    $stmt = $link->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}

/**
 * Récupère les détails d'une réservation par son ID
 * 
 * @param mysqli $link Connexion à la base de données
 * @param int $id_reservation ID de la réservation
 * @return array|null Détails de la réservation ou null si non trouvée
 */
function getReservationById($link, $id_reservation) {
    $query = "
        SELECT r.*, 
               p.reference, p.nom_produit, p.description, p.image_url,
               CONCAT(u.prenom, ' ', u.nom) AS nom_complet, u.email, u.telephone
        FROM reservations r
        JOIN produits p ON r.id_produit = p.id_produit
        JOIN utilisateurs u ON r.id_utilisateur = u.id_utilisateur
        WHERE r.id_reservation = ?
    ";
    
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $id_reservation);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * Met à jour le statut d'une réservation
 * 
 * @param mysqli $link Connexion à la base de données
 * @param int $id_reservation ID de la réservation
 * @param string $nouveau_statut Nouveau statut
 * @param int $id_utilisateur ID de l'utilisateur qui effectue l'action
 * @param string $commentaire Commentaire optionnel
 * @return bool True si la mise à jour a réussi, False sinon
 */
function updateReservationStatus($link, $id_reservation, $nouveau_statut, $id_utilisateur, $commentaire = '') {
    // Vérifier si la réservation existe
    $query_check = "SELECT * FROM reservations WHERE id_reservation = ?";
    $stmt_check = $link->prepare($query_check);
    $stmt_check->bind_param("i", $id_reservation);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        return false;
    }
    
    $reservation = $result_check->fetch_assoc();
    
    // Mettre à jour le statut
    $query_update = "UPDATE reservations SET statut = ?, date_modification = NOW() WHERE id_reservation = ?";
    $stmt_update = $link->prepare($query_update);
    $stmt_update->bind_param("si", $nouveau_statut, $id_reservation);
    $success = $stmt_update->execute();
    
    if (!$success) {
        return false;
    }
    
    // Si la réservation est approuvée, définir une date d'expiration (par exemple, +7 jours)
    if ($nouveau_statut === 'approuvee') {
        $date_expiration = date('Y-m-d', strtotime('+7 days'));
        $query_expiration = "UPDATE reservations SET date_expiration = ? WHERE id_reservation = ?";
        $stmt_expiration = $link->prepare($query_expiration);
        $stmt_expiration->bind_param("si", $date_expiration, $id_reservation);
        $stmt_expiration->execute();
    }
    
    // Enregistrer l'historique de l'action
    $query_historique = "
        INSERT INTO historique_reservations 
        (id_reservation, id_utilisateur, action, statut_avant, statut_apres, commentaire, date_action)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ";
    
    $action = "Changement de statut";
    $stmt_historique = $link->prepare($query_historique);
    $stmt_historique->bind_param("iissss", 
        $id_reservation, 
        $id_utilisateur, 
        $action, 
        $reservation['statut'], 
        $nouveau_statut, 
        $commentaire
    );
    $stmt_historique->execute();
    
    return true;
}

/**
 * Récupère l'historique des actions pour une réservation
 * 
 * @param mysqli $link Connexion à la base de données
 * @param int $id_reservation ID de la réservation
 * @return array Liste des actions dans l'historique
 */
function getReservationHistory($link, $id_reservation) {
    $query = "
        SELECT h.*, CONCAT(u.prenom, ' ', u.nom) AS nom_utilisateur
        FROM historique_reservations h
        JOIN utilisateurs u ON h.id_utilisateur = u.id_utilisateur
        WHERE h.id_reservation = ?
        ORDER BY h.date_action DESC
    ";
    
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $id_reservation);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historique = [];
    while ($row = $result->fetch_assoc()) {
        $historique[] = $row;
    }
    
    return $historique;
}

/**
 * Récupère les produits disponibles pour la réservation
 * 
 * @param mysqli $link Connexion à la base de données
 * @param string $statut Statut du produit (disponible, indisponible, tous)
 * @param int $quantite_min Quantité minimale disponible
 * @return array Liste des produits
 */
function getProduitsReservation($link, $statut = '', $quantite_min = 0) {
    $conditions = [];
    $params = [];
    $types = "";
    
    // Calculer la quantité totale disponible pour chaque produit
    $subquery = "(SELECT SUM(quantite) FROM inventaire WHERE inventaire.id_produit = produits.id_produit) as quantite_disponible";
    
    if ($statut === 'disponible') {
        // Produits avec stock suffisant
        $conditions[] = "(SELECT COALESCE(SUM(quantite), 0) FROM inventaire WHERE inventaire.id_produit = produits.id_produit) >= ?";
        $params[] = $quantite_min;
        $types .= "i";
    } elseif ($statut === 'indisponible') {
        // Produits avec stock insuffisant
        $conditions[] = "(SELECT COALESCE(SUM(quantite), 0) FROM inventaire WHERE inventaire.id_produit = produits.id_produit) < ?";
        $params[] = $quantite_min;
        $types .= "i";
    } elseif ($quantite_min > 0) {
        // Filtre sur quantité minimale uniquement
        $conditions[] = "(SELECT COALESCE(SUM(quantite), 0) FROM inventaire WHERE inventaire.id_produit = produits.id_produit) >= ?";
        $params[] = $quantite_min;
        $types .= "i";
    }
    
    $where_clause = "";
    if (!empty($conditions)) {
        $where_clause = "WHERE " . implode(" AND ", $conditions);
    }
    
    $query = "
        SELECT 
            produits.id_produit, 
            produits.reference, 
            produits.nom_produit, 
            produits.id_categorie,
            produits.description, 
            produits.fournisseur, 
            produits.prix_unitaire,
            $subquery,
            produits.seuil_alerte,
            produits.date_creation, 
            produits.date_modification,
            categories.nom_categorie
        FROM produits
        LEFT JOIN categories ON produits.id_categorie = categories.id_categorie
        $where_clause
        ORDER BY produits.reference
    ";
    
    $stmt = $link->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $produits = [];
    while ($row = $result->fetch_assoc()) {
        $produits[] = $row;
    }
    
    return $produits;
}

/**
 * Récupère les réservations d'un utilisateur spécifique
 * 
 * @param mysqli $link Connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @param array $filtres Filtres supplémentaires
 * @param int $page Page actuelle
 * @param int $par_page Nombre d'éléments par page
 * @return array Liste des réservations de l'utilisateur
 */
function getMesReservations($link, $id_utilisateur, $filtres = [], $page = 1, $par_page = 10) {
    $filtres['id_utilisateur'] = $id_utilisateur;
    return getReservations($link, $filtres, $page, $par_page);
}

/**
 * Exporte les réservations au format CSV
 * 
 * @param mysqli $link Connexion à la base de données
 * @param array $filtres Filtres à appliquer
 * @return string Contenu CSV
 */
function exportReservationsCSV($link, $filtres = []) {
    $reservations = getReservations($link, $filtres, 1, 1000); // Limité à 1000 réservations
    
    $output = fopen('php://temp', 'w');
    
    // En-têtes CSV
    $headers = [
        'ID', 'Date demande', 'Produit', 'Référence', 'Utilisateur', 
        'Quantité', 'Date début', 'Date fin', 'Statut', 'Motif'
    ];
    
    fputcsv($output, $headers);
    
    // Données
    foreach ($reservations as $reservation) {
        $statut_fr = '';
        switch ($reservation['statut']) {
            case 'en_attente': $statut_fr = 'En attente'; break;
            case 'approuvee': $statut_fr = 'Approuvée'; break;
            case 'refusee': $statut_fr = 'Refusée'; break;
            case 'annulee': $statut_fr = 'Annulée'; break;
            case 'terminee': $statut_fr = 'Terminée'; break;
            default: $statut_fr = $reservation['statut'];
        }
        
        $row = [
            $reservation['id_reservation'],
            $reservation['date_demande'],
            $reservation['nom_produit'],
            $reservation['reference'],
            $reservation['nom_complet'],
            $reservation['quantite'],
            $reservation['date_debut'],
            $reservation['date_fin'],
            $statut_fr,
            $reservation['motif']
        ];
        
        fputcsv($output, $row);
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return $csv;
}