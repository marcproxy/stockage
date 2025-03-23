<?php
/**
 * Fonctions pour la gestion des réservations
 * Fichier: src/api/reservations_functions.php
 */

/**
 * Récupère la liste des réservations avec filtres
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param array $filtres Filtres à appliquer
 * @param int $page Numéro de page
 * @param int $par_page Nombre d'éléments par page
 * @return array Liste des réservations
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
        $params[] = $filtres['date_debut'] . ' 00:00:00';
        $param_types .= 's';
    }
    
    // Filtre par date (avant une certaine date)
    if (!empty($filtres['date_fin'])) {
        $conditions[] = "r.date_demande <= ?";
        $params[] = $filtres['date_fin'] . ' 23:59:59';
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param array $filtres Filtres à appliquer
 * @return int Nombre de réservations
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
        $params[] = $filtres['date_debut'] . ' 00:00:00';
        $param_types .= 's';
    }
    
    // Filtre par date (avant une certaine date)
    if (!empty($filtres['date_fin'])) {
        $conditions[] = "r.date_demande <= ?";
        $params[] = $filtres['date_fin'] . ' 23:59:59';
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_produit ID du produit
 * @param int $id_utilisateur ID de l'utilisateur
 * @param int $quantite Quantité demandée
 * @param string $commentaire Commentaire optionnel
 * @param string|null $date_expiration Date d'expiration (format YYYY-MM-DD)
 * @return array Résultat de l'opération
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_reservation ID de la réservation
 * @param string $nouveau_statut Nouveau statut (en_attente, approuvee, refusee, annulee, terminee)
 * @param int $id_utilisateur_action ID de l'utilisateur effectuant l'action
 * @param string $commentaire Commentaire optionnel
 * @return array Résultat de l'opération
 */
function changerStatutReservation($link_stockage, $id_reservation, $nouveau_statut, $id_utilisateur_action, $commentaire = '') {
    // Vérifier si la réservation existe
    $stmt = $link_stockage->prepare("SELECT id_reservation, statut, id_produit, quantite FROM reservations WHERE id_reservation = ?");
    $stmt->bind_param("i", $id_reservation);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'La réservation spécifiée n\'existe pas'];
    }
    
    $row = $result->fetch_assoc();
    $ancien_statut = $row['statut'];
    $id_produit = $row['id_produit'];
    $quantite = $row['quantite'];
    
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
    
    // Début de la transaction
    $link_stockage->begin_transaction();
    
    try {
        // Mettre à jour le statut de la réservation
        $stmt = $link_stockage->prepare("
            UPDATE reservations 
            SET statut = ?, date_modification = NOW()
            WHERE id_reservation = ?
        ");
        $stmt->bind_param("si", $nouveau_statut, $id_reservation);
        $stmt->execute();
        
        // Enregistrer le changement dans les logs
        $stmt = $link_stockage->prepare("
            INSERT INTO reservations_logs (
                id_reservation, 
                ancien_statut, 
                nouveau_statut, 
                id_utilisateur_action, 
                commentaire
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issis", $id_reservation, $ancien_statut, $nouveau_statut, $id_utilisateur_action, $commentaire);
        $stmt->execute();
        
        // Si la réservation est terminée, créer un mouvement de sortie
        if ($nouveau_statut === 'terminee' && $ancien_statut === 'approuvee') {
            // Récupérer la section du produit
            $stmt = $link_stockage->prepare("
                SELECT s.id_section
                FROM inventaire i
                JOIN sections s ON i.id_section = s.id_section
                WHERE i.id_produit = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $id_produit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $id_section = $row['id_section'];
                
                // Vérifier si le stock est suffisant
                $stmt = $link_stockage->prepare("
                    SELECT quantite 
                    FROM inventaire 
                    WHERE id_produit = ? AND id_section = ?
                ");
                $stmt->bind_param("ii", $id_produit, $id_section);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stock_actuel = $row['quantite'];
                
                if ($stock_actuel < $quantite) {
                    throw new Exception('Stock insuffisant pour terminer la réservation');
                }
                
                // Créer le mouvement de sortie
                $stmt = $link_stockage->prepare("
                    INSERT INTO mouvements (
                        id_produit, 
                        id_section, 
                        type_mouvement, 
                        quantite, 
                        utilisateur, 
                        commentaire
                    ) VALUES (?, ?, 'sortie', ?, ?, ?)
                ");
                
                // Récupérer le nom d'utilisateur
                $stmt_user = $link_stockage->prepare("
                    SELECT CONCAT(prenom, ' ', nom) AS nom_complet 
                    FROM utilisateurs 
                    WHERE id_utilisateur = ?
                ");
                $stmt_user->bind_param("i", $id_utilisateur_action);
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();
                $row_user = $result_user->fetch_assoc();
                $nom_utilisateur = $row_user['nom_complet'];
                
                $commentaire_mouvement = 'Sortie liée à la réservation #' . $id_reservation;
                $stmt->bind_param("iiiss", $id_produit, $id_section, $quantite, $nom_utilisateur, $commentaire_mouvement);
                $stmt->execute();
                
                // Mettre à jour l'inventaire
                $stmt = $link_stockage->prepare("
                    UPDATE inventaire 
                    SET quantite = quantite - ?, date_derniere_sortie = NOW() 
                    WHERE id_produit = ? AND id_section = ?
                ");
                $stmt->bind_param("iii", $quantite, $id_produit, $id_section);
                $stmt->execute();
                
                // Vérifier si le stock est sous le seuil d'alerte
                $stmt = $link_stockage->prepare("
                    SELECT i.quantite, p.seuil_alerte, p.reference, p.nom_produit
                    FROM inventaire i
                    JOIN produits p ON i.id_produit = p.id_produit
                    WHERE i.id_produit = ? AND i.id_section = ?
                ");
                $stmt->bind_param("ii", $id_produit, $id_section);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['quantite'] <= $row['seuil_alerte']) {
                    // Créer une alerte de stock faible
                    $type_alerte = ($row['quantite'] <= $row['seuil_alerte'] * 0.5) ? 'critique' : 'stock_faible';
                    $message_alerte = ($type_alerte == 'critique') 
                        ? "Stock critique : {$row['quantite']} unités de {$row['reference']} - {$row['nom_produit']} restantes"
                        : "Stock faible : {$row['quantite']} unités de {$row['reference']} - {$row['nom_produit']} restantes";
                    
                    $stmt = $link_stockage->prepare("
                        INSERT INTO alertes (
                            id_produit, 
                            type_alerte, 
                            message, 
                            date_alerte, 
                            statut
                        ) VALUES (?, ?, ?, NOW(), 'active')
                        ON DUPLICATE KEY UPDATE 
                            message = VALUES(message),
                            date_alerte = NOW(),
                            statut = 'active'
                    ");
                    $stmt->bind_param("iss", $id_produit, $type_alerte, $message_alerte);
                    $stmt->execute();
                }
            }
        }
        
        // Valider la transaction
        $link_stockage->commit();
        
        return [
            'success' => true, 
            'message' => 'Statut de la réservation modifié avec succès de ' . $ancien_statut . ' à ' . $nouveau_statut
        ];
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $link_stockage->rollback();
        return [
            'success' => false, 
            'message' => 'Erreur lors de la modification du statut: ' . $e->getMessage()
        ];
    }
}

/**
 * Récupère les détails d'une réservation
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_reservation ID de la réservation
 * @return array|null Détails de la réservation ou null si non trouvée
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_reservation ID de la réservation
 * @return array Historique des modifications
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_produit ID du produit
 * @return array Liste des réservations en attente
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @return array Liste des réservations de l'utilisateur
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_produit ID du produit
 * @return int Quantité réservée
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
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @param int $id_produit ID du produit
 * @return int Quantité disponible
 */
function getQuantiteDisponible($link_stockage, $id_produit) {
    // Récupérer la quantité en stock
    $stmt = $link_stockage->prepare("
        SELECT SUM(i.quantite) as total_stock
        FROM inventaire i
        WHERE i.id_produit = ?
    ");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $quantite_stock = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $quantite_stock = intval($row['total_stock'] ?? 0);
    }
    
    // Récupérer la quantité réservée
    $quantite_reservee = getQuantiteReservee($link_stockage, $id_produit);
    
    // Calculer la quantité disponible
    $quantite_disponible = max(0, $quantite_stock - $quantite_reservee);
    
    return $quantite_disponible;
}

/**
 * Annule les réservations expirées
 * 
 * @param mysqli $link_stockage Connexion à la base de données
 * @return array Résultat de l'opération
 */
function annulerReservationsExpirees($link_stockage) {
    $now = date('Y-m-d H:i:s');
    $count = 0;
    
    // Récupérer les réservations expirées
    $sql = "
        SELECT id_reservation
        FROM reservations
        WHERE date_expiration IS NOT NULL 
        AND date_expiration < ?
        AND statut IN ('en_attente', 'approuvee')
    ";
    
    $stmt = $link_stockage->prepare($sql);
    $stmt->bind_param("s", $now);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations_expirees = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reservations_expirees[] = $row['id_reservation'];
        }
    }
    
    // Annuler chaque réservation expirée
    foreach ($reservations_expirees as $id_reservation) {
        $result = changerStatutReservation(
            $link_stockage,
            $id_reservation,
            'annulee',
            1, // ID utilisateur système
            'Annulation automatique : réservation expirée'
        );
        
        if ($result['success']) {
            $count++;
        }
    }
    
    return [
        'success' => true,
        'message' => $count . ' réservation(s) expirée(s) annulée(s)',
        'count' => $count
    ];
}
?>