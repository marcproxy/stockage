<?php

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

function getAlertesActives($link_stockage) {
    $alertes = [];
    
    $result = $link_stockage->query("
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
 * Récupère des statistiques sur l'inventaire
 */
function getStatsInventaire($link_stockage) {
    $stats = [
        'total_produits' => 0,
        'stock_ok' => 0,
        'stock_faible' => 0,
        'stock_critique' => 0,
        'nombre_categories' => 0
    ];
    
    // Nombre total de produits
    $query = "SELECT COUNT(*) AS total FROM produits";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_produits'] = $row['total'];
    }
    
    // Produits avec un stock OK (quantité > seuil_alerte)
    $query = "SELECT COUNT(DISTINCT p.id_produit) AS total
              FROM produits p 
              JOIN inventaire i ON p.id_produit = i.id_produit
              WHERE i.quantite > p.seuil_alerte";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['stock_ok'] = $row['total'];
    }
    
    // Produits avec un stock faible (quantité <= seuil_alerte mais > 0)
    $query = "SELECT COUNT(DISTINCT p.id_produit) AS total
              FROM produits p 
              JOIN inventaire i ON p.id_produit = i.id_produit
              WHERE i.quantite <= p.seuil_alerte AND i.quantite > 0";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['stock_faible'] = $row['total'];
    }
    
    // Produits avec un stock critique ou en rupture (quantité = 0)
    $query = "SELECT COUNT(DISTINCT p.id_produit) AS total
              FROM produits p 
              LEFT JOIN inventaire i ON p.id_produit = i.id_produit
              WHERE i.quantite = 0 OR i.quantite IS NULL";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['stock_critique'] = $row['total'];
    }
    
    // Nombre de catégories
    $query = "SELECT COUNT(*) AS total FROM categories";
    $result = $link_stockage->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['nombre_categories'] = $row['total'];
    }
    
    return $stats;
}

/**
 * Récupère le nombre total de produits selon les filtres
 */
function getTotalProduits($link_stockage, $filtre_armoire = 0, $filtre_categorie = 0, $filtre_statut = '', $recherche = '') {
    $conditions = [];
    $params = [];
    $param_types = '';
    
    // Filtre par armoire
    if ($filtre_armoire > 0) {
        $conditions[] = "e.id_armoire = ?";
        $params[] = $filtre_armoire;
        $param_types .= 'i';
    }
    
    // Filtre par catégorie
    if ($filtre_categorie > 0) {
        $conditions[] = "p.id_categorie = ?";
        $params[] = $filtre_categorie;
        $param_types .= 'i';
    }
    
    // Filtre par statut
    if (!empty($filtre_statut)) {
        switch ($filtre_statut) {
            case 'ok':
                $conditions[] = "i.quantite > p.seuil_alerte";
                break;
            case 'faible':
                $conditions[] = "i.quantite <= p.seuil_alerte AND i.quantite > 0";
                break;
            case 'critique':
                $conditions[] = "i.quantite <= p.seuil_alerte * 0.5 AND i.quantite > 0";
                break;
            case 'rupture':
                $conditions[] = "i.quantite = 0";
                break;
            case 'non_stocké':
                $conditions[] = "i.quantite IS NULL";
                break;
        }
    }
    
    // Recherche par texte
    if (!empty($recherche)) {
        $conditions[] = "(p.reference LIKE ? OR p.nom_produit LIKE ? OR c.nom_categorie LIKE ? OR a.nom_armoire LIKE ?)";
        $search_param = "%{$recherche}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= 'ssss';
    }
    
    // Construction de la requête SQL
    $sql = "SELECT COUNT(DISTINCT p.id_produit) AS total
            FROM produits p
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            LEFT JOIN inventaire i ON p.id_produit = i.id_produit
            LEFT JOIN sections s ON i.id_section = s.id_section
            LEFT JOIN etageres e ON s.id_etagere = e.id_etagere
            LEFT JOIN armoires a ON e.id_armoire = a.id_armoire";
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Exécution de la requête
    $count = 0;
    if (!empty($params)) {
        $stmt = $link_stockage->prepare($sql);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $count = $row['total'];
        }
    } else {
        $result = $link_stockage->query($sql);
        if ($row = $result->fetch_assoc()) {
            $count = $row['total'];
        }
    }
    
    return $count;
}

/**
 * Récupère les produits filtrés pour l'inventaire
 */
function getProduitsAvecFiltres($link_stockage, $filtre_armoire = 0, $filtre_categorie = 0, $filtre_statut = '', $recherche = '', $limit = 15, $offset = 0) {
    $produits = [];
    $conditions = [];
    $params = [];
    $param_types = '';
    
    // Filtre par armoire
    if ($filtre_armoire > 0) {
        $conditions[] = "e.id_armoire = ?";
        $params[] = $filtre_armoire;
        $param_types .= 'i';
    }
    
    // Filtre par catégorie
    if ($filtre_categorie > 0) {
        $conditions[] = "p.id_categorie = ?";
        $params[] = $filtre_categorie;
        $param_types .= 'i';
    }
    
    // Filtre par statut
    if (!empty($filtre_statut)) {
        switch ($filtre_statut) {
            case 'ok':
                $conditions[] = "i.quantite > p.seuil_alerte";
                break;
            case 'faible':
                $conditions[] = "i.quantite <= p.seuil_alerte AND i.quantite > 0";
                break;
            case 'critique':
                $conditions[] = "i.quantite <= p.seuil_alerte * 0.5 AND i.quantite > 0";
                break;
            case 'rupture':
                $conditions[] = "i.quantite = 0";
                break;
            case 'non_stocké':
                $conditions[] = "i.quantite IS NULL";
                break;
        }
    }
    
    // Recherche par texte
    if (!empty($recherche)) {
        $conditions[] = "(p.reference LIKE ? OR p.nom_produit LIKE ? OR c.nom_categorie LIKE ? OR a.nom_armoire LIKE ?)";
        $search_param = "%{$recherche}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= 'ssss';
    }
    
    // Construction de la requête SQL
    $sql = "SELECT 
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
                LEFT JOIN armoires a ON e.id_armoire = a.id_armoire";
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY p.reference ASC";
    
    // Ajout de la pagination
    if ($limit > 0) {
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $param_types .= 'ii';
    }
    
    // Exécution de la requête
    if (!empty($params)) {
        $stmt = $link_stockage->prepare($sql);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $link_stockage->query($sql);
    }
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produits[] = $row;
        }
    }
    
    return $produits;
}

/**
 * Récupère les détails d'un produit spécifique
 */
function getProduitDetails($link_stockage, $id_produit) {
    $produit = null;
    
    $query = "
        SELECT 
            p.*,
            c.nom_categorie,
            i.quantite,
            i.date_derniere_entree,
            i.date_derniere_sortie,
            s.id_section,
            s.numero_section,
            e.id_etagere,
            e.numero_etagere,
            a.id_armoire,
            a.nom_armoire
        FROM 
            produits p
            LEFT JOIN categories c ON p.id_categorie = c.id_categorie
            LEFT JOIN inventaire i ON p.id_produit = i.id_produit
            LEFT JOIN sections s ON i.id_section = s.id_section
            LEFT JOIN etageres e ON s.id_etagere = e.id_etagere
            LEFT JOIN armoires a ON e.id_armoire = a.id_armoire
        WHERE 
            p.id_produit = ?
    ";
    
    $stmt = $link_stockage->prepare($query);
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $produit = $row;
        
        // Ajouter le statut du stock
        if (is_null($produit['quantite'])) {
            $produit['statut'] = 'non_stocké';
            $produit['statut_texte'] = 'Non stocké';
        } elseif ($produit['quantite'] == 0) {
            $produit['statut'] = 'rupture';
            $produit['statut_texte'] = 'Rupture de stock';
        } elseif ($produit['quantite'] <= $produit['seuil_alerte'] * 0.5) {
            $produit['statut'] = 'critique';
            $produit['statut_texte'] = 'Stock critique';
        } elseif ($produit['quantite'] <= $produit['seuil_alerte']) {
            $produit['statut'] = 'faible';
            $produit['statut_texte'] = 'Stock faible';
        } else {
            $produit['statut'] = 'ok';
            $produit['statut_texte'] = 'En stock';
        }
        
        // Ajouter le texte de l'emplacement
        if (!empty($produit['numero_section'])) {
            switch ($produit['numero_section']) {
                case 1: $position = 'Gauche'; break;
                case 2: $position = 'Milieu'; break;
                case 3: $position = 'Droite'; break;
                default: $position = 'Section ' . $produit['numero_section']; break;
            }
            $produit['emplacement'] = $produit['nom_armoire'] . ', Étage ' . $produit['numero_etagere'] . ', ' . $position;
        } else {
            $produit['emplacement'] = 'Non stocké';
        }
    }
    
    return $produit;
}

/**
 * Supprime un produit de l'inventaire et ses données associées
 */
function supprimerProduit($link_stockage, $id_produit) {
    // Vérifier que le produit existe
    $stmt = $link_stockage->prepare("SELECT id_produit FROM produits WHERE id_produit = ?");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Le produit n\'existe pas'];
    }
    
    // Début de la transaction
    $link_stockage->begin_transaction();
    
    try {
        // Supprimer les alertes liées au produit
        $stmt = $link_stockage->prepare("DELETE FROM alertes WHERE id_produit = ?");
        $stmt->bind_param("i", $id_produit);
        $stmt->execute();
        
        // Supprimer les mouvements liés au produit
        $stmt = $link_stockage->prepare("DELETE FROM mouvements WHERE id_produit = ?");
        $stmt->bind_param("i", $id_produit);
        $stmt->execute();
        
        // Supprimer les données d'inventaire
        $stmt = $link_stockage->prepare("DELETE FROM inventaire WHERE id_produit = ?");
        $stmt->bind_param("i", $id_produit);
        $stmt->execute();
        
        // Supprimer le produit
        $stmt = $link_stockage->prepare("DELETE FROM produits WHERE id_produit = ?");
        $stmt->bind_param("i", $id_produit);
        $stmt->execute();
        
        // Valider la transaction
        $link_stockage->commit();
        
        return ['success' => true, 'message' => 'Produit supprimé avec succès'];
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $link_stockage->rollback();
        return ['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
    }
}

/**
 * Met à jour la quantité d'un produit dans l'inventaire
 */
function updateQuantiteProduit($link_stockage, $id_produit, $nouvelle_quantite, $utilisateur, $commentaire = '') {
    // Vérifier que le produit existe
    $stmt = $link_stockage->prepare("
        SELECT p.id_produit, i.quantite, i.id_section 
        FROM produits p
        LEFT JOIN inventaire i ON p.id_produit = i.id_produit
        WHERE p.id_produit = ?
    ");
    $stmt->bind_param("i", $id_produit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Le produit n\'existe pas'];
    }
    
    $row = $result->fetch_assoc();
    $quantite_actuelle = $row['quantite'];
    $id_section = $row['id_section'];
    
    // Si le produit n'est pas encore dans l'inventaire, on ne peut pas mettre à jour la quantité
    if (is_null($id_section)) {
        return ['success' => false, 'message' => 'Le produit n\'est pas stocké dans une section'];
    }
    
    // Calculer la différence de quantité
    $difference = $nouvelle_quantite - $quantite_actuelle;
    
    // Si pas de changement, ne rien faire
    if ($difference == 0) {
        return ['success' => true, 'message' => 'Aucun changement de quantité'];
    }
    
    // Début de la transaction
    $link_stockage->begin_transaction();
    
    try {
        // Mettre à jour l'inventaire
        if ($nouvelle_quantite > 0) {
            // Type de mouvement et date à mettre à jour
            $type_mouvement = ($difference > 0) ? 'entrée' : 'sortie';
            $date_field = ($difference > 0) ? 'date_derniere_entree' : 'date_derniere_sortie';
            $date_now = date('Y-m-d H:i:s');
            
            // Mettre à jour l'inventaire
            $stmt = $link_stockage->prepare("
                UPDATE inventaire 
                SET quantite = ?, {$date_field} = ?
                WHERE id_produit = ?
            ");
            $stmt->bind_param("isi", $nouvelle_quantite, $date_now, $id_produit);
            $stmt->execute();
            
            // Enregistrer le mouvement
            $quantite_mouvement = abs($difference);
            $stmt = $link_stockage->prepare("
                INSERT INTO mouvements (id_produit, id_section, type_mouvement, quantite, utilisateur, commentaire)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisiss", $id_produit, $id_section, $type_mouvement, $quantite_mouvement, $utilisateur, $commentaire);
            $stmt->execute();
        } else {
            // Si la quantité est 0, mettre à jour avec une sortie
            $type_mouvement = 'sortie';
            $date_now = date('Y-m-d H:i:s');
            
            // Mettre à jour l'inventaire
            $stmt = $link_stockage->prepare("
                UPDATE inventaire 
                SET quantite = 0, date_derniere_sortie = ?
                WHERE id_produit = ?
            ");
            $stmt->bind_param("si", $date_now, $id_produit);
            $stmt->execute();
            
            // Enregistrer le mouvement de sortie
            $quantite_mouvement = $quantite_actuelle; // Sortie de toute la quantité restante
            $stmt = $link_stockage->prepare("
                INSERT INTO mouvements (id_produit, id_section, type_mouvement, quantite, utilisateur, commentaire)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisiss", $id_produit, $id_section, $type_mouvement, $quantite_mouvement, $utilisateur, $commentaire);
            $stmt->execute();
        }
        
        // Vérifier si une alerte doit être créée ou supprimée
        $stmt = $link_stockage->prepare("SELECT seuil_alerte FROM produits WHERE id_produit = ?");
        $stmt->bind_param("i", $id_produit);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $seuil_alerte = $row['seuil_alerte'];
        
        // Supprimer les alertes existantes de type stock_faible ou critique
        $stmt = $link_stockage->prepare("
            DELETE FROM alertes 
            WHERE id_produit = ? AND (type_alerte = 'stock_faible' OR type_alerte = 'critique')
        ");
        $stmt->bind_param("i", $id_produit);
        $stmt->execute();
        
        // Créer une nouvelle alerte si nécessaire
        if ($nouvelle_quantite <= $seuil_alerte && $nouvelle_quantite > 0) {
            $type_alerte = ($nouvelle_quantite <= $seuil_alerte * 0.5) ? 'critique' : 'stock_faible';
            $message = ($type_alerte == 'critique') 
                ? "Stock critique : {$nouvelle_quantite} unités restantes (seuil: {$seuil_alerte})"
                : "Stock faible : {$nouvelle_quantite} unités restantes (seuil: {$seuil_alerte})";
            
            $stmt = $link_stockage->prepare("
                INSERT INTO alertes (id_produit, type_alerte, message, date_alerte, statut)
                VALUES (?, ?, ?, NOW(), 'active')
            ");
            $stmt->bind_param("iss", $id_produit, $type_alerte, $message);
            $stmt->execute();
        }
        
        // Valider la transaction
        $link_stockage->commit();
        
        return ['success' => true, 'message' => 'Quantité mise à jour avec succès'];
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $link_stockage->rollback();
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
    }
}
?>