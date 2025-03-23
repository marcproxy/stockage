<?php
// functions.php - Fonctions utilitaires pour l'application avec mysqli

// Fonction pour récupérer les statistiques principales
function getStats($mysqli) {
    $stats = [];
    
    // Compter le nombre total de références
    $result = $mysqli->query("SELECT COUNT(*) as total FROM produits");
    $row = $result->fetch_assoc();
    $stats['total_references'] = $row['total'];
    
    // Compter le nombre d'armoires actives
    $result = $mysqli->query("SELECT COUNT(*) as total FROM armoires");
    $row = $result->fetch_assoc();
    $stats['total_armoires'] = $row['total'];
    
    // Compter le nombre de produits en alerte
    $result = $mysqli->query("
        SELECT COUNT(*) as total FROM inventaire i 
        JOIN produits p ON i.id_produit = p.id_produit 
        WHERE i.quantite <= p.seuil_alerte
    ");
    $row = $result->fetch_assoc();
    $stats['produits_alerte'] = $row['total'];
    
    // Compter le nombre de mouvements ce mois
    $currentMonth = date('m');
    $currentYear = date('Y');
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) as total FROM mouvements 
        WHERE MONTH(date_mouvement) = ? AND YEAR(date_mouvement) = ?
    ");
    $stmt->bind_param("ss", $currentMonth, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['mouvements_mois'] = $row['total'];
    
    return $stats;
}

// Fonction pour récupérer les armoires avec leur taux d'occupation
function getArmoires($mysqli) {
    $armoires = [];
    
    // Récupérer les informations de base sur les armoires
    $result = $mysqli->query("
        SELECT id_armoire, nom_armoire, description 
        FROM armoires 
        ORDER BY nom_armoire
    ");
    
    while ($armoire = $result->fetch_assoc()) {
        // Compter le nombre de références stockées dans cette armoire
        $stmt = $mysqli->prepare("
            SELECT COUNT(DISTINCT i.id_produit) as total_produits
            FROM inventaire i
            JOIN sections s ON i.id_section = s.id_section
            JOIN etageres e ON s.id_etagere = e.id_etagere
            WHERE e.id_armoire = ?
        ");
        $stmt->bind_param("i", $armoire['id_armoire']);
        $stmt->execute();
        $countResult = $stmt->get_result();
        $countRow = $countResult->fetch_assoc();
        
        // Créer le tableau d'informations sur l'armoire
        $armoires[] = [
            'id' => $armoire['id_armoire'],
            'nom' => $armoire['nom_armoire'],
            'description' => $armoire['description'],
            'references' => $countRow['total_produits'],
            'capacite' => 200, // Capacité maximale définie
            'occupation' => $countRow['total_produits'] > 0 ? round(($countRow['total_produits'] / 200) * 100) : 0
        ];
    }
    
    return $armoires;
}

// Fonction pour récupérer les produits récents ou recherchés
function getProduits($mysqli, $search = '', $limit = 10, $armoireId = null) {
    $produits = [];
    
    $sql = "
        SELECT 
            p.id_produit,
            p.reference,
            p.nom_produit,
            a.nom_armoire,
            e.numero_etagere,
            s.numero_section,
            i.quantite,
            p.seuil_alerte,
            CASE 
                WHEN i.quantite <= 0 THEN 'rupture'
                WHEN i.quantite < p.seuil_alerte THEN 'critique'
                WHEN i.quantite < (p.seuil_alerte * 2) THEN 'faible'
                ELSE 'normal'
            END AS statut
        FROM 
            produits p
        JOIN 
            inventaire i ON p.id_produit = i.id_produit
        JOIN 
            sections s ON i.id_section = s.id_section
        JOIN 
            etageres e ON s.id_etagere = e.id_etagere
        JOIN 
            armoires a ON e.id_armoire = a.id_armoire
    ";
    
    $where = [];
    $params = [];
    $types = "";
    
    // Ajouter la condition de recherche si nécessaire
    if (!empty($search)) {
        $where[] = "(p.reference LIKE ? OR p.nom_produit LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    // Filtrer par armoire si spécifié
    if ($armoireId !== null) {
        $where[] = "a.id_armoire = ?";
        $params[] = $armoireId;
        $types .= "i";
    }
    
    // Ajouter les clauses WHERE si nécessaire
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Ordonner et limiter les résultats
    $sql .= " ORDER BY p.id_produit DESC LIMIT ?";
    $params[] = $limit;
    $types .= "i";
    
    // Préparer et exécuter la requête
    $stmt = $mysqli->prepare($sql);
    
    if (!empty($params)) {
        // Convertir l'array de paramètres en variables individuelles pour bind_param
        $bindParams = array($types);
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bindParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $produits[] = $row;
    }
    
    return $produits;
}

// Fonction pour obtenir les derniers mouvements
function getMouvementsRecents($mysqli, $limit = 5) {
    $mouvements = [];
    
    $stmt = $mysqli->prepare("
        SELECT 
            m.id_mouvement,
            m.type_mouvement,
            m.quantite,
            m.date_mouvement,
            m.utilisateur,
            m.commentaire,
            p.reference,
            p.nom_produit,
            a1.nom_armoire as armoire_source,
            e1.numero_etagere as etage_source,
            s1.numero_section as section_source,
            a2.nom_armoire as armoire_destination,
            e2.numero_etagere as etage_destination,
            s2.numero_section as section_destination
        FROM 
            mouvements m
        JOIN 
            produits p ON m.id_produit = p.id_produit
        JOIN 
            sections s1 ON m.id_section = s1.id_section
        JOIN 
            etageres e1 ON s1.id_etagere = e1.id_etagere
        JOIN 
            armoires a1 ON e1.id_armoire = a1.id_armoire
        LEFT JOIN 
            sections s2 ON m.id_section_destination = s2.id_section
        LEFT JOIN 
            etageres e2 ON s2.id_etagere = e2.id_etagere
        LEFT JOIN 
            armoires a2 ON e2.id_armoire = a2.id_armoire
        ORDER BY 
            m.date_mouvement DESC
        LIMIT ?
    ");
    
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $mouvements[] = $row;
    }
    
    return $mouvements;
}

// Fonction pour récupérer les alertes actives
function getAlertesActives($mysqli) {
    $alertes = [];
    
    $result = $mysqli->query("
        SELECT 
            a.*,
            p.reference,
            p.nom_produit,
            i.quantite,
            p.seuil_alerte,
            ar.nom_armoire,
            e.numero_etagere,
            s.numero_section
        FROM 
            alertes a
        JOIN 
            produits p ON a.id_produit = p.id_produit
        LEFT JOIN 
            inventaire i ON p.id_produit = i.id_produit
        LEFT JOIN 
            sections s ON i.id_section = s.id_section
        LEFT JOIN 
            etageres e ON s.id_etagere = e.id_etagere
        LEFT JOIN 
            armoires ar ON e.id_armoire = ar.id_armoire
        WHERE 
            a.statut = 'active'
        ORDER BY 
            a.date_alerte DESC
    ");
    
    while ($row = $result->fetch_assoc()) {
        $alertes[] = $row;
    }
    
    return $alertes;
}

// Fonction pour nettoyer et sécuriser l'entrée
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour formater la date en français
function formatDateFr($date) {
    $timestamp = strtotime($date);
    $jour = date('j', $timestamp);
    $mois = date('n', $timestamp);
    $annee = date('Y', $timestamp);
    $heure = date('H:i', $timestamp);
    
    $mois_fr = array(
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    );
    
    return $jour . ' ' . $mois_fr[$mois] . ' ' . $annee . ' à ' . $heure;
}