<?php
// reservations.php - Page de gestion des réservations
require_once '../../src/components/current/db_connect.php';
require_once '../../src/api/functions.php';
require_once '../../src/api/reservations-functions.php';

// Récupération des paramètres de filtrage et pagination
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$id_utilisateur = isset($_GET['utilisateur']) ? intval($_GET['utilisateur']) : 0;
$id_produit = isset($_GET['produit']) ? intval($_GET['produit']) : 0;
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$par_page = 10;

// Filtres pour la requête
$filtres = [
    'statut' => $statut,
    'id_utilisateur' => $id_utilisateur,
    'id_produit' => $id_produit,
    'date_debut' => $date_debut,
    'date_fin' => $date_fin
];

// Récupérer le nombre total de réservations pour la pagination
$total_reservations = countReservations($link_stockage, $filtres);
$pages_totales = ceil($total_reservations / $par_page);

// Récupérer les réservations avec pagination
$reservations = getReservations($link_stockage, $filtres, $page, $par_page);

// Récupérer la liste des utilisateurs pour le filtre
$utilisateurs = [];
$query = "SELECT id_utilisateur, nom_utilisateur, CONCAT(prenom, ' ', nom) AS nom_complet FROM utilisateurs ORDER BY nom";
$result = $link_stockage->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $utilisateurs[] = $row;
    }
}

// Récupérer les produits pour le filtre
$produits = getProduits($link_stockage, '', 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations - AJI-STOCK</title>
    <link rel="stylesheet" href="../../css/index.css">
    <style>
        .reservation-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            min-width: 110px;
        }
        
        .status-en-attente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approuvee {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-refusee {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-annulee {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .status-terminee {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .filters-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
        }
        
        .filter-label {
            margin-right: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #555;
        }
        
        .filter-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .actions-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-trigger {
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        
        .dropdown-trigger:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            min-width: 160px;
            background-color: white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .dropdown-content a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            font-size: 0.9rem;
        }
        
        .dropdown-content a:hover {
            background-color: #f5f5f5;
        }
        
        .actions-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .status-badge-sm {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .badge-en-attente { background-color: #ffc107; }
        .badge-approuvee { background-color: #28a745; }
        .badge-refusee { background-color: #dc3545; }
        .badge-annulee { background-color: #6c757d; }
        .badge-terminee { background-color: #17a2b8; }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #777;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="index.php" class="logo">AJI<span>Stock</span></a>
        <ul class="sidebar-menu">
        <li><a href="<?= BASE_URL ?>/index.php" class="active"><span class="icon">📊</span> Tableau de bord</a></li>
            <li><a href="<?= BASE_URL ?>/templates/inventory/inventory.php"><span class="icon">📦</span> Inventaire</a></li>
            <li><a href="mouvements.php"><span class="icon">🔄</span> Mouvements</a></li>
            <li><a href="armoires.php"><span class="icon">🏢</span> Armoires</a></li>
            <li><a href="sections.php"><span class="icon">📊</span> Sections</a></li>
            <li><a href="<?= BASE_URL ?>/templates/reservations/reservations.php" class="active"><span class="icon">🔖</span> Réservations</a></li>
            <li><a href="fournisseurs.php"><span class="icon">👥</span> Fournisseurs</a></li>
            <li><a href="commandes.php"><span class="icon">🛒</span> Commandes</a></li>
            <li><a href="rapports.php"><span class="icon">📝</span> Rapports</a></li>
            <li><a href="alertes.php"><span class="icon">⚠️</span> Alertes</a></li>
            <li><a href="recherche.php"><span class="icon">🔍</span> Recherche avancée</a></li>
            <li><a href="parametres.php"><span class="icon">⚙️</span> Paramètres</a></li>
        </ul>
    </div>

    <!-- Header -->
    <header>
        <div class="main-nav">
            <a href="index.php">Tableau de bord</a>
            <a href="inventaire.php">Inventaire</a>
            <a href="reservations.php" class="active">Réservations</a>
            <a href="rapports.php">Rapports</a>
            <a href="parametres.php">Paramètres</a>
        </div>
        <div class="user-info">
            <div class="user-avatar">MM</div>
            <span>Marc MARTIN</span>
            <div class="notification-icon">🔔
                <span class="notification-count"><?php echo count(getAlertesActives($link_stockage)); ?></span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="title-container">
            <h1>Gestion des réservations</h1>
        </div>
        
        <div class="actions-container">
            <a href="./templates/nouvelle-reservation.php" class="btn btn-success">+ Nouvelle réservation</a>
            <a href="mes-reservations.php" class="btn btn-outline">Mes réservations</a>
            <a href="export_reservations.php" class="btn btn-outline">Exporter <span class="icon">↓</span></a>
        </div>

        <!-- Résumé des réservations -->
        <div class="inventory-badges">
            <?php 
                $en_attente = countReservations($link_stockage, ['statut' => 'en_attente']);
                $approuvees = countReservations($link_stockage, ['statut' => 'approuvee']);
                $terminees = countReservations($link_stockage, ['statut' => 'terminee']);
            ?>
            <div class="inventory-badge">
                <span class="icon">🔖</span>
                <span class="number"><?php echo $total_reservations; ?></span>
                <span>Réservations totales</span>
            </div>
            <div class="inventory-badge status-en-attente">
                <span class="icon">⏳</span>
                <span class="number"><?php echo $en_attente; ?></span>
                <span>En attente</span>
            </div>
            <div class="inventory-badge status-approuvee">
                <span class="icon">✅</span>
                <span class="number"><?php echo $approuvees; ?></span>
                <span>Approuvées</span>
            </div>
            <div class="inventory-badge status-terminee">
                <span class="icon">🔄</span>
                <span class="number"><?php echo $terminees; ?></span>
                <span>Terminées</span>
            </div>
        </div>

        <!-- Filtres -->
        <div class="content-section">
            <div class="section-header">
                <h2>Filtres</h2>
            </div>
            <div class="section-body">
                <form action="reservations.php" method="get" id="filtersForm">
                    <div class="filters-container">
                        <div class="filter-group">
                            <label for="statut" class="filter-label">Statut:</label>
                            <select name="statut" id="statut" class="filter-select">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?php echo $statut == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="approuvee" <?php echo $statut == 'approuvee' ? 'selected' : ''; ?>>Approuvée</option>
                                <option value="refusee" <?php echo $statut == 'refusee' ? 'selected' : ''; ?>>Refusée</option>
                                <option value="annulee" <?php echo $statut == 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                <option value="terminee" <?php echo $statut == 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="utilisateur" class="filter-label">Utilisateur:</label>
                            <select name="utilisateur" id="utilisateur" class="filter-select">
                                <option value="0">Tous les utilisateurs</option>
                                <?php foreach ($utilisateurs as $user): ?>
                                    <option value="<?php echo $user['id_utilisateur']; ?>" <?php echo $id_utilisateur == $user['id_utilisateur'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['nom_complet']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="produit" class="filter-label">Produit:</label>
                            <select name="produit" id="produit" class="filter-select">
                                <option value="0">Tous les produits</option>
                                <?php foreach ($produits as $produit): ?>
                                    <option value="<?php echo $produit['id_produit']; ?>" <?php echo $id_produit == $produit['id_produit'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($produit['reference'] . ' - ' . $produit['nom_produit']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group date-filter">
                            <label for="date_debut" class="filter-label">Période:</label>
                            <input type="date" id="date_debut" name="date_debut" class="filter-select" value="<?php echo $date_debut; ?>">
                            <span>à</span>
                            <input type="date" id="date_fin" name="date_fin" class="filter-select" value="<?php echo $date_fin; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn btn-outline">Filtrer</button>
                            <a href="reservations.php" class="btn btn-outline">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des réservations -->
        <div class="content-section">
            <div class="section-header">
                <h2>Liste des réservations</h2>
                <div>
                    <span><?php echo $total_reservations; ?> réservation(s) trouvée(s)</span>
                </div>
            </div>
            <div class="section-body">
                <?php if (empty($reservations)): ?>
                    <div class="empty-state">
                        <p>Aucune réservation ne correspond aux critères de recherche.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date demande</th>
                                <th>Produit</th>
                                <th>Utilisateur</th>
                                <th>Quantité</th>
                                <th>Statut</th>
                                <th>Date d'expiration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo $reservation['id_reservation']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($reservation['date_demande'])); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['reference'] . ' - ' . $reservation['nom_produit']); ?></td>
                                    <td><?php echo htmlspecialchars($reservation['nom_complet']); ?></td>
                                    <td><?php echo $reservation['quantite']; ?></td>
                                    <td>
                                        <span class="reservation-status status-<?php echo $reservation['statut']; ?>">
                                            <?php
                                                switch ($reservation['statut']) {
                                                    case 'en_attente': echo 'En attente'; break;
                                                    case 'approuvee': echo 'Approuvée'; break;
                                                    case 'refusee': echo 'Refusée'; break;
                                                    case 'annulee': echo 'Annulée'; break;
                                                    case 'terminee': echo 'Terminée'; break;
                                                    default: echo $reservation['statut'];
                                                }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo !empty($reservation['date_expiration']) ? date('d/m/Y', strtotime($reservation['date_expiration'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <div class="actions-dropdown">
                                            <span class="dropdown-trigger">
                                                Actions <span class="icon">▼</span>
                                            </span>
                                            <div class="dropdown-content">
                                                <a href="reservation-details.php?id=<?php echo $reservation['id_reservation']; ?>">Voir détails</a>
                                                
                                                <?php if ($reservation['statut'] == 'en_attente'): ?>
                                                    <a href="reservation-action.php?id=<?php echo $reservation['id_reservation']; ?>&action=approuver">Approuver</a>
                                                    <a href="reservation-action.php?id=<?php echo $reservation['id_reservation']; ?>&action=refuser">Refuser</a>
                                                <?php endif; ?>
                                                
                                                <?php if ($reservation['statut'] == 'approuvee'): ?>
                                                    <a href="reservation-action.php?id=<?php echo $reservation['id_reservation']; ?>&action=terminer">Terminer</a>
                                                    <a href="reservation-action.php?id=<?php echo $reservation['id_reservation']; ?>&action=annuler">Annuler</a>
                                                <?php endif; ?>
                                                
                                                <?php if ($reservation['statut'] == 'refusee' || $reservation['statut'] == 'annulee'): ?>
                                                    <a href="reservation-action.php?id=<?php echo $reservation['id_reservation']; ?>&action=relancer">Relancer</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($pages_totales > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&statut=<?php echo $statut; ?>&utilisateur=<?php echo $id_utilisateur; ?>&produit=<?php echo $id_produit; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="pagination-link">«</a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($pages_totales, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<a href="?page=1&statut=' . $statut . '&utilisateur=' . $id_utilisateur . '&produit=' . $id_produit . '&date_debut=' . $date_debut . '&date_fin=' . $date_fin . '" class="pagination-link">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $page) ? 'active' : '';
                                echo '<a href="?page=' . $i . '&statut=' . $statut . '&utilisateur=' . $id_utilisateur . '&produit=' . $id_produit . '&date_debut=' . $date_debut . '&date_fin=' . $date_fin . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
                            }
                            
                            if ($end_page < $pages_totales) {
                                if ($end_page < $pages_totales - 1) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                                echo '<a href="?page=' . $pages_totales . '&statut=' . $statut . '&utilisateur=' . $id_utilisateur . '&produit=' . $id_produit . '&date_debut=' . $date_debut . '&date_fin=' . $date_fin . '" class="pagination-link">' . $pages_totales . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $pages_totales): ?>
                                <a href="?page=<?php echo $page + 1; ?>&statut=<?php echo $statut; ?>&utilisateur=<?php echo $id_utilisateur; ?>&produit=<?php echo $id_produit; ?>&date_debut=<?php echo $date_debut; ?>&date_fin=<?php echo $date_fin; ?>" class="pagination-link">»</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile menu toggle -->
    <div class="menu-toggle">☰</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 992 && 
                    sidebar.classList.contains('show') && 
                    !sidebar.contains(e.target) && 
                    e.target !== menuToggle) {
                    sidebar.classList.remove('show');
                }
            });
            
            // Auto-submit form on filter change
            const filterForm = document.getElementById('filtersForm');
            const filterSelects = filterForm.querySelectorAll('select');
            
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    filterForm.submit();
                });
            });
        });
    </script>
</body>
</html>