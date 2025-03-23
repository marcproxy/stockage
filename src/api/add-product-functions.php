<?php
// src/api/functions.php - Fonctions utilitaires pour les requêtes d'API

/**
 * Récupère les statistiques générales
 */
function getStats($link_stockage) {
    $stats = [
        'total_references' => 0,
        'total_armoires' => 0,
        'produits_alerte' => 0,
        'mouvements_mois' => 0
    ];
    
    // Total des références
    $query = "SELECT COUNT(*) as total FROM produits";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_references'] = $row['total'];
    }
    
    // Total des armoires
    $query = "SELECT COUNT(*) as total FROM armoires";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_armoires'] = $row['total'];
    }
    
    // Produits en alerte (quantité < seuil_alerte)
    $query = "SELECT COUNT(DISTINCT p.id_produit) as total
              FROM produits p 
              JOIN inventaire i ON p.id_produit = i.id_produit
              WHERE i.quantite <= p.seuil_alerte";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['produits_alerte'] = $row['total'];
    }
    
    // Mouvements du mois en cours
    $query = "SELECT COUNT(*) as total 
              FROM mouvements 
              WHERE MONTH(date_mouvement) = MONTH(CURRENT_DATE) 
              AND YEAR(date_mouvement) = YEAR(CURRENT_DATE)";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['mouvements_mois'] = $row['total'];
    }
    
    return $stats;
}

/**
 * Récupère la liste des armoires avec leur taux d'occupation
 */
function getArmoires($link_stockage) {
    $armoires = [];
    
    $query = "SELECT 
                id_armoire,
                nom_armoire
              FROM armoires
              ORDER BY nom_armoire";
    
    $result = $link_stockage->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $armoires[] = $row;
        }
    }
    
    return $armoires;
}

/**
 * Récupère la liste des catégories
 */
function getCategories($link_stockage) {
    $categories = [];
    
    $query = "SELECT id_categorie, nom_categorie, description FROM categories ORDER BY nom_categorie";
    $result = $link_stockage->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

/**
 * Récupère la liste des étagères pour une armoire donnée
 */
function getEtageres($link_stockage, $id_armoire) {
    $etageres = [];
    
    $stmt = $link_stockage->prepare("
        SELECT id_etagere, numero_etagere, description, capacite_max
        FROM etageres
        WHERE id_armoire = ?
        ORDER BY numero_etagere
    ");
    $stmt->bind_param("i", $id_armoire);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $etageres[] = $row;
        }
    }
    
    return $etageres;
}

/**
 * Récupère la liste des sections pour une étagère donnée
 */
function getSections($link_stockage, $id_etagere) {
    $sections = [];
    
    $stmt = $link_stockage->prepare("
        SELECT id_section, numero_section, description, capacite_max
        FROM sections
        WHERE id_etagere = ?
        ORDER BY numero_section
    ");
    $stmt->bind_param("i", $id_etagere);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
    }
    
    return $sections;
}

/**
 * Récupère la liste des produits avec informations sur leur emplacement
 */
function getProduits($link_stockage, $search = '', $limit = 0) {
    $produits = [];
    
    $searchCondition = '';
    if (!empty($search)) {
        $searchParam = "%{$search}%";
        $searchCondition = "AND (p.reference LIKE ? OR p.nom_produit LIKE ? OR f.nom_fournisseur LIKE ?)";
    }
    
    $limitCondition = '';
    if ($limit > 0) {
        $limitCondition = "LIMIT " . intval($limit);
    }
    
    $query = "
        SELECT 
            p.id_produit,
            p.reference,
            p.nom_produit,
            p.description,
            p.prix_unitaire,
            p.seuil_alerte,
            c.nom_categorie,
            a.nom_armoire,
            e.numero_etagere,
            s.numero_section,
            i.quantite,
            CASE
                WHEN i.quantite IS NULL THEN 'non_stocké'
                WHEN i.quantite = 0 THEN 'rupture'
                WHEN i.quantite <= p.seuil_alerte * 0.5 THEN 'critique'
                WHEN i.quantite <= p.seuil_alerte THEN 'faible'
                ELSE 'ok'
            END AS statut
        FROM 
            produits p
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            LEFT JOIN inventaire i ON p.id_produit = i.id_produit
            LEFT JOIN sections s ON i.id_section = s.id_section
            LEFT JOIN etageres e ON s.id_etagere = e.id_etagere
            LEFT JOIN armoires a ON e.id_armoire = a.id_armoire
        WHERE 1=1 " . $searchCondition . "
        ORDER BY p.date_creation DESC
        " . $limitCondition;
    
    $stmt = $link_stockage->prepare($query);
    
    if (!empty($search)) {
        $searchParam = "%{$search}%";
        $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produits[] = $row;
        }
    }
    
    return $produits;
}

/**
 * Récupère les mouvements récents
 */
function getMouvementsRecents($link_stockage, $limit = 10) {
    $mouvements = [];
    
    $query = "
        SELECT 
            m.id_mouvement,
            m.date_mouvement,
            p.reference,
            p.nom_produit,
            m.type_mouvement,
            m.quantite,
            m.utilisateur,
            m.commentaire,
            a_source.nom_armoire AS armoire_source,
            e_source.numero_etagere AS etage_source,
            s_source.numero_section AS section_source,
            a_dest.nom_armoire AS armoire_destination,
            e_dest.numero_etagere AS etage_destination,
            s_dest.numero_section AS section_destination
        FROM 
            mouvements m
            JOIN produits p ON m.id_produit = p.id_produit
            JOIN sections s_source ON m.id_section = s_source.id_section
            JOIN etageres e_source ON s_source.id_etagere = e_source.id_etagere
            JOIN armoires a_source ON e_source.id_armoire = a_source.id_armoire
            LEFT JOIN sections s_dest ON m.id_section_destination = s_dest.id_section
            LEFT JOIN etageres e_dest ON s_dest.id_etagere = e_dest.id_etagere
            LEFT JOIN armoires a_dest ON e_dest.id_armoire = a_dest.id_armoire
        ORDER BY m.date_mouvement DESC
        LIMIT ?
    ";
    
    $stmt = $link_stockage->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $mouvements[] = $row;
        }
    }
    
    return $mouvements;
}

/**
 * Récupère les alertes actives
 */
function getAlertesActives($link_stockage) {
    $alertes = [];
    
    $query = "
        SELECT 
            a.id_alerte,
            a.date_alerte,
            a.type_alerte,
            a.message,
            a.statut,
            p.id_produit,
            p.reference,
            p.nom_produit
        FROM 
            alertes a
            LEFT JOIN produits p ON a.id_produit = p.id_produit
        WHERE
            a.statut = 'active'
        ORDER BY 
            a.date_alerte DESC
    ";
    
    $result = $link_stockage->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $alertes[] = $row;
        }
    }
    
    return $alertes;
}