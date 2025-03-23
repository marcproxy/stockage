<?php
/**
 * Récupère la liste des réservations avec filtres
 */
function getReservations($link_stockage, $filtres = [], $page = 1, $par_page = 10) {
    $reservations = [];
    $conditions = [];
    $params = [];
    $param_types = '';
    
    // Filtre par statut
    if (!empty($filtres['statut'])) {
        $conditions[] = "r.statut = ?";
        $params[] = $filtres['statut'];
        $param_types .= 's';
    }
    
    // Filtre par utilisateur
    if (!empty($filtres['id_utilisateur'])) {
        $conditions[] = "r.id_utilisateur = ?";
        $params[] = $filtres['id_utilisateur'];
        $param_types .= 'i';
    }
    
    // Filtre par produit
    if (!empty($filtres['id_produit'])) {
        $conditions[] = "r.id_produit = ?";
        $params[] = $filtres['id_produit'];
        $param_types .= 'i';
    }
    
    // Filtre par date (après une certaine date)
    if (!empty($filtres['date_debut'])) {
        $conditions[] = "r.date_demande >= ?";
        $params[] = $filtres['date_debut'];
        $param_types .= 's';
    }
    
    // Filtre par date (avant une certaine date)
    if (!empty($filtres['date_fin'])) {
        $conditions[] = "r.date_demande <= ?";
        $params[] = $filtres['date_fin'];
        $param_types .= 's';
    }
    
    // Construction de la requête SQL
    $sql = "
        SELECT 
            r.*,
            p.reference,
            p.nom_produit,
            u.nom_utilisateur,
            CONCAT(u.prenom, ' ', u.nom) AS nom_complet
        FROM 
            reservations r
            JOIN produits p ON r.id_produit = p.id_produit
            JOIN utilisateurs u ON r.id_utilisateur = u.id_utilisateur
    ";
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY r.date_demande DESC";
    
    // Pagination
    $offset = ($page - 1) * $par_page;
    $sql .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $par_page;
    $param_types .= 'ii';
    
    // Exécution de la requête
    $stmt = $link_stockage->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
    }
    
    return $reservations;
}

/**
 * Compte le nombre total de réservations selon les filtres
 */
function countReservations($link_stockage, $filtres = []) {
    $conditions = [];
    $params = [];
    $param_types = '';
    
    // Filtre par statut
    if (!empty($filtres['statut'])) {
        $conditions[] = "r.statut = ?";
        $params[] = $filtres['statut'];
        $param_types .= 's';
    }
    
    // Filtre par utilisateur
    if (!empty($filtres['id_utilisateur'])) {
        $conditions[] = "r.id_utilisateur = ?";
        $params[] = $filtres['id_utilisateur'];
        $param_types .= 'i';
    }
    
    // Filtre par produit
    if (!empty($filtres['id_produit'])) {
        $conditions[] = "r.id_produit = ?";
        $params[] = $filtres['id_produit'];
        $param_types .= 'i';
    }
    
    // Filtre par date (après une certaine date)
    if (!empty($filtres['date_debut'])) {
        $conditions[] = "r.date_demande >= ?";
        $params[] = $filtres['date_debut'];
        $param_types .= 's';
    }
    
    // Filtre par date (avant une certaine date)
    if (!empty($filtres['date_fin'])) {
        $conditions[] = "r.date_demande <= ?";
        $params[] = $filtres['date_fin'];
        $param_types .= 's';
    }
    
    // Construction de la requête SQL
    $sql = "
        SELECT COUNT(*) as total
        FROM reservations r
    ";
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Exécution de la requête
    $count = 0;
    $stmt = $link_stockage->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $count = $row['total'];
    }
    
    return $count;
}

/**
 * Crée une nouvelle réservation
 */
function creerReservation($link_stockage, $id_produit, $id_utilisateur, $quantite, $commentaire = '', $date_expiration = null) {
    // Vérifier si le produit existe
    $stmt = $link_stockage->prepare("SELECT id_produit FROM produits WHERE id_produit = ?");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Le produit spécifié n\'existe pas'];
    }
    
    // Vérifier si l'utilisateur existe
    $stmt = $link_stockage->prepare("SELECT id_utilisateur FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'L\'utilisateur spécifié n\'existe pas'];
    }
    
    // Vérifier la disponibilité du produit
    $stmt = $link_stockage->prepare("
        SELECT i.quantite, p.seuil_alerte
        FROM produits p
        LEFT JOIN inventaire i ON p.id_produit = i.id_produit
        WHERE p.id_produit = ?
    ");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $quantite_disponible = $row['quantite'] ?? 0;
    
    // Vérifier si la quantité demandée est disponible
    if ($quantite > $quantite_disponible) {
        return [
            'success' => false, 
            'message' => 'Quantité insuffisante. Disponible: ' . $quantite_disponible,
            'disponible' => $quantite_disponible
        ];
    }
    
    // Créer la réservation
    $sql = "
        INSERT INTO reservations (
            id_produit, 
            id_utilisateur, 
            quantite, 
            commentaire, 
            date_expiration
        ) VALUES (?, ?, ?, ?, ?)
    ";
    
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("iiiss", $id_produit, $id_utilisateur, $quantite, $commentaire, $date_expiration);
    
    if ($stmt->execute()) {
        $id_reservation = $link_stockage->insert_id;
        
        // Loguer la création de la réservation
        $sql = "
            INSERT INTO reservations_logs (
                id_reservation, 
                ancien_statut, 
                nouveau_statut, 
                id_utilisateur_action, 
                commentaire
            ) VALUES (?, NULL, 'en_attente', ?, 'Création de la réservation')
        ";
        
        $stmt = $link_stockage->prepare($sql);
        $stmt->bind_param("ii", $id_reservation, $id_utilisateur);
        $stmt->execute();
        
        return [
            'success' => true, 
            'message' => 'Réservation créée avec succès', 
            'id_reservation' => $id_reservation
        ];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la création de la réservation: ' . $link_stockage->error];
    }
}

/**
 * Modifie le statut d'une réservation
 */
function changerStatutReservation($link_stockage, $id_reservation, $nouveau_statut, $id_utilisateur_action, $commentaire = '') {
    // Vérifier si la réservation existe
    $stmt = $link_stockage->prepare("SELECT id_reservation, statut FROM reservations WHERE id_reservation = ?");
    $stmt->bind_param("i", $id_reservation);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'La réservation spécifiée n\'existe pas'];
    }
    
    $row = $result->fetch_assoc();
    $ancien_statut = $row['statut'];
    
    // Vérifier la validité du changement de statut
    $transitions_valides = [
        'en_attente' => ['approuvee', 'refusee', 'annulee'],
        'approuvee' => ['terminee', 'annulee'],
        'refusee' => ['en_attente'],
        'annulee' => ['en_attente'],
        'terminee' => []
    ];
    
    if (!in_array($nouveau_statut, $transitions_valides[$ancien_statut])) {
        return [
            'success' => false, 
            'message' => 'Transition de statut invalide: ' . $ancien_statut . ' -> ' . $nouveau_statut
        ];
    }
    
    // Appeler la procédure stockée pour changer le statut
    $sql = "CALL ChangerStatutReservation(?, ?, ?, ?)";
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("isis", $id_reservation, $nouveau_statut, $id_utilisateur_action, $commentaire);
    
    if ($stmt->execute()) {
        return [
            'success' => true, 
            'message' => 'Statut de la réservation modifié avec succès'
        ];
    } else {
        return [
            'success' => false, 
            'message' => 'Erreur lors de la modification du statut: ' . $link_stockage->error
        ];
    }
}

/**
 * Récupère les détails d'une réservation
 */
function getReservationDetails($link_stockage, $id_reservation) {
    $sql = "
        SELECT 
            r.*,
            p.reference,
            p.nom_produit,
            u.nom_utilisateur,
            CONCAT(u.prenom, ' ', u.nom) AS nom_complet
        FROM 
            reservations r
            JOIN produits p ON r.id_produit = p.id_produit
            JOIN utilisateurs u ON r.id_utilisateur = u.id_utilisateur
        WHERE 
            r.id_reservation = ?
    ";
    
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("i", $id_reservation);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row;
    } else {
        return null;
    }
}

/**
 * Récupère l'historique des modifications d'une réservation
 */
function getReservationLogs($link_stockage, $id_reservation) {
    $logs = [];
    
    $sql = "
        SELECT 
            l.*,
            CONCAT(u.prenom, ' ', u.nom) AS nom_utilisateur
        FROM 
            reservations_logs l
            JOIN utilisateurs u ON l.id_utilisateur_action = u.id_utilisateur
        WHERE 
            l.id_reservation = ?
        ORDER BY 
            l.date_action ASC
    ";
    
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("i", $id_reservation);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}

/**
 * Récupère les réservations en attente pour un produit
 */
function getReservationsEnAttenteProduit($link_stockage, $id_produit) {
    $reservations = [];
    
    $sql = "
        SELECT 
            r.*,
            CONCAT(u.prenom, ' ', u.nom) AS nom_utilisateur
        FROM 
            reservations r
            JOIN utilisateurs u ON r.id_utilisateur = u.id_utilisateur
        WHERE 
            r.id_produit = ?
            AND r.statut = 'en_attente'
        ORDER BY 
            r.date_demande ASC
    ";
    
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
    }
    
    return $reservations;
}

/**
 * Récupère les réservations d'un utilisateur
 */
function getMesReservations($link_stockage, $id_utilisateur) {
    $reservations = [];
    
    $sql = "
        SELECT 
            r.*,
            p.reference,
            p.nom_produit
        FROM 
            reservations r
            JOIN produits p ON r.id_produit = p.id_produit
        WHERE 
            r.id_utilisateur = ?
        ORDER BY 
            r.date_demande DESC
    ";
    
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
    }
    
    return $reservations;
}

/**
 * Calcule la quantité réservée pour un produit
 */
function getQuantiteReservee($link_stockage, $id_produit) {
    $sql = "
        SELECT SUM(quantite) as total_reserve
        FROM reservations
        WHERE id_produit = ? AND statut = 'approuvee'
    ";
    
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return intval($row['total_reserve'] ?? 0);
    } else {
        return 0;
    }
}

/**
 * Calcule la quantité réellement disponible (inventaire - réservations)
 */
function getQuantiteDisponible($link_stockage, $id_produit) {
    // Récupérer la quantité en stock
    $stmt = $link_stockage->prepare("
        SELECT i.quantite
        FROM inventaire i
        WHERE i.id_produit = ?
    ");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $quantite_stock = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $quantite_stock = intval($row['quantite'] ?? 0);
    }
    
    // Récupérer la quantité réservée
    $quantite_reservee = getQuantiteReservee($link_stockage, $id_produit);
    
    // Calculer la quantité disponible
    $quantite_disponible = max(0, $quantite_stock - $quantite_reservee);
    
    return $quantite_disponible;
}
?>