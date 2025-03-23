<?php
// inventory - Page de gestion de l'inventaire
require_once '../../src/components/current/db_connect.php';
require_once '../../src/api/inventory-functions.php';


// Définir les filtres par défaut
$filtre_armoire = isset($_GET['armoire']) ? intval($_GET['armoire']) : 0;
$filtre_categorie = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;
$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$recherche = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$produits_par_page = 15;

// Récupérer les armoires pour le filtre
$armoires = getArmoires($link_stockage);

// Récupérer les catégories pour le filtre
$categories = getCategories($link_stockage);

// Calculer le total de produits pour la pagination
$total_produits = getTotalProduits($link_stockage, $filtre_armoire, $filtre_categorie, $filtre_statut, $recherche);
$total_pages = ceil($total_produits / $produits_par_page);
$offset = ($page - 1) * $produits_par_page;

// Récupérer les produits avec pagination et filtres
$produits = getProduitsAvecFiltres($link_stockage, $filtre_armoire, $filtre_categorie, $filtre_statut, $recherche, $produits_par_page, $offset);

// Définir les options de statut pour le filtre
$statuts = [
    'tous' => 'Tous les statuts',
    'ok' => 'En stock',
    'faible' => 'Stock faible',
    'critique' => 'Stock critique',
    'rupture' => 'Rupture de stock',
    'non_stocké' => 'Non stocké'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire - AJI-STOCK</title>
    <link rel="stylesheet" href="../../css/index.css">
    <style>
        .filters-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
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
            min-width: 150px;
        }
        
        .clear-filters {
            margin-left: auto;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination-link {
            padding: 5px 10px;
            margin: 0 3px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--dark-color);
            border: 1px solid #ddd;
            background-color: white;
        }
        
        .pagination-link.active {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }
        
        .pagination-link:hover:not(.active) {
            background-color: #f5f5f5;
        }
        
        .inventory-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .inventory-badge {
            padding: 10px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .inventory-badge .icon {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .inventory-badge .number {
            font-weight: 600;
            font-size: 1.1rem;
            margin-right: 5px;
        }
        
        .stock-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-ok {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stock-critical {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .export-btn {
            margin-left: 15px;
        }
        
        .bulk-actions {
            margin-top: 15px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px dashed #ddd;
        }
        
        .bulk-title {
            font-weight: 600;
            margin-right: 15px;
        }
        
        .checkable .data-table td:first-child {
            width: 40px;
            text-align: center;
        }
        
        .select-all-container {
            margin-bottom: 10px;
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
            <a href="inventory" class="active">Inventaire</a>
            <a href="fournisseurs.php">Fournisseurs</a>
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
            <h1>Gestion de l'inventaire</h1>
        </div>
        
        <div class="actions-container">
            <a href="./templates/add-product.php" class="btn btn-success">+ Ajouter un produit</a>
            <a href="./templates/add-mouvement.php" class="btn btn-outline">Nouveau mouvement</a>
            <a href="export_inventory" class="btn btn-outline export-btn">Exporter <span class="icon">↓</span></a>
        </div>

        <!-- Résumé de l'inventaire -->
        <div class="inventory-badges">
            <?php 
                $stats = getStatsInventaire($link_stockage);
            ?>
            <div class="inventory-badge">
                <span class="icon">📦</span>
                <span class="number"><?php echo $stats['total_produits']; ?></span>
                <span>Produits</span>
            </div>
            <div class="inventory-badge stock-ok">
                <span class="icon">✅</span>
                <span class="number"><?php echo $stats['stock_ok']; ?></span>
                <span>En stock</span>
            </div>
            <div class="inventory-badge stock-warning">
                <span class="icon">⚠️</span>
                <span class="number"><?php echo $stats['stock_faible']; ?></span>
                <span>Stock faible</span>
            </div>
            <div class="inventory-badge stock-critical">
                <span class="icon">🔴</span>
                <span class="number"><?php echo $stats['stock_critique']; ?></span>
                <span>Stock critique/rupture</span>
            </div>
            <div class="inventory-badge">
                <span class="icon">🏭</span>
                <span class="number"><?php echo $stats['nombre_categories']; ?></span>
                <span>Catégories</span>
            </div>
        </div>

        <!-- Filtres -->
        <div class="content-section">
            <div class="section-header">
                <h2>Filtres</h2>
            </div>
            <div class="section-body">
                <form action="inventory" method="get" id="filtersForm">
                    <div class="filters-container">
                        <div class="search-container" style="width: 300px;">
                            <span class="search-icon">🔍</span>
                            <input type="text" name="search" id="searchProduit" class="search-input" 
                                   placeholder="Rechercher par référence, nom..." 
                                   value="<?php echo htmlspecialchars($recherche); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="armoire" class="filter-label">Armoire :</label>
                            <select name="armoire" id="armoire" class="filter-select">
                                <option value="0">Toutes les armoires</option>
                                <?php foreach ($armoires as $armoire): ?>
                                    <option value="<?php echo $armoire['id_armoire']; ?>" 
                                            <?php echo ($filtre_armoire == $armoire['id_armoire']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($armoire['nom_armoire']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="categorie" class="filter-label">Catégorie:</label>
                            <select name="categorie" id="categorie" class="filter-select">
                                <option value="0">Toutes les catégories</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id_categorie']; ?>"
                                            <?php echo ($filtre_categorie == $categorie['id_categorie']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categorie['nom_categorie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="statut" class="filter-label">Statut:</label>
                            <select name="statut" id="statut" class="filter-select">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statuts as $key => $value): ?>
                                    <?php if ($key !== 'tous'): ?>
                                        <option value="<?php echo $key; ?>"
                                                <?php echo ($filtre_statut === $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn btn-outline">Filtrer</button>
                            <a href="inventory" class="btn btn-outline clear-filters">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau d'inventaire -->
        <div class="content-section">
            <div class="section-header">
                <h2>Liste des produits</h2>
                <div>
                    <span><?php echo $total_produits; ?> produit(s) trouvé(s)</span>
                </div>
            </div>
            <div class="section-body">
                <form action="bulk_actions.php" method="post" id="bulkForm">
                    <div class="select-all-container">
                        <label>
                            <input type="checkbox" id="selectAll"> Tout sélectionner
                        </label>
                    </div>
                    
                    <table class="data-table checkable">
                        <thead>
                            <tr>
                                <th><span class="sr-only">Sélection</span></th>
                                <th>Référence</th>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th>Emplacement</th>
                                <th>Quantité</th>
                                <th>Prix Unitaire</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produits)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center;">Aucun produit trouvé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($produits as $produit): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_products[]" value="<?php echo $produit['id_produit']; ?>" class="product-checkbox">
                                        </td>
                                        <td><?php echo htmlspecialchars($produit['reference']); ?></td>
                                        <td><?php echo htmlspecialchars($produit['nom_produit']); ?></td>
                                        <td><?php echo htmlspecialchars($produit['nom_categorie'] ?? 'Non catégorisé'); ?></td>
                                        <td>
                                            <?php if (!empty($produit['nom_armoire'])): ?>
                                                <?php echo htmlspecialchars($produit['nom_armoire']); ?>,
                                                Étage <?php echo $produit['numero_etagere']; ?>,
                                                <?php 
                                                    $position = '';
                                                    switch ($produit['numero_section']) {
                                                        case 1: $position = 'Gauche'; break;
                                                        case 2: $position = 'Milieu'; break;
                                                        case 3: $position = 'Droite'; break;
                                                    }
                                                    echo $position;
                                                ?>
                                            <?php else: ?>
                                                Non stocké
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (!is_null($produit['quantite'])) ? $produit['quantite'] : '-'; ?></td>
                                        <td><?php echo (!is_null($produit['prix_unitaire'])) ? number_format($produit['prix_unitaire'], 2, ',', ' ') . ' €' : '-'; ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = 'success';
                                                $statusText = 'En stock';
                                                
                                                if ($produit['statut'] == 'non_stocké') {
                                                    $statusClass = 'warning';
                                                    $statusText = 'Non stocké';
                                                } elseif ($produit['statut'] == 'rupture') {
                                                    $statusClass = 'danger';
                                                    $statusText = 'Rupture';
                                                } elseif ($produit['statut'] == 'critique') {
                                                    $statusClass = 'danger';
                                                    $statusText = 'Critique';
                                                } elseif ($produit['statut'] == 'faible') {
                                                    $statusClass = 'warning';
                                                    $statusText = 'Stock faible';
                                                }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td>
                                            <a href="produit-details.php?id=<?php echo $produit['id_produit']; ?>" class="action-btn" title="Voir détails">👁️</a>
                                            <a href="produit-editer.php?id=<?php echo $produit['id_produit']; ?>" class="action-btn" title="Modifier">✏️</a>
                                            <a href="produit-supprimer.php?id=<?php echo $produit['id_produit']; ?>" class="action-btn delete" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <?php if (!empty($produits)): ?>
                        <div class="bulk-actions">
                            <span class="bulk-title">Actions groupées:</span>
                            <select name="bulk_action" id="bulk_action" class="filter-select">
                                <option value="">Sélectionner une action</option>
                                <option value="export">Exporter la sélection</option>
                                <option value="move">Déplacer</option>
                                <option value="delete">Supprimer</option>
                            </select>
                            <button type="submit" class="btn btn-outline" id="applyBulkAction" disabled>Appliquer</button>
                        </div>
                    <?php endif; ?>
                </form>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&armoire=<?php echo $filtre_armoire; ?>&categorie=<?php echo $filtre_categorie; ?>&statut=<?php echo $filtre_statut; ?>&search=<?php echo urlencode($recherche); ?>" class="pagination-link">«</a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="?page=1&armoire=' . $filtre_armoire . '&categorie=' . $filtre_categorie . '&statut=' . $filtre_statut . '&search=' . urlencode($recherche) . '" class="pagination-link">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active_class = ($i == $page) ? 'active' : '';
                            echo '<a href="?page=' . $i . '&armoire=' . $filtre_armoire . '&categorie=' . $filtre_categorie . '&statut=' . $filtre_statut . '&search=' . urlencode($recherche) . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . '&armoire=' . $filtre_armoire . '&categorie=' . $filtre_categorie . '&statut=' . $filtre_statut . '&search=' . urlencode($recherche) . '" class="pagination-link">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&armoire=<?php echo $filtre_armoire; ?>&categorie=<?php echo $filtre_categorie; ?>&statut=<?php echo $filtre_statut; ?>&search=<?php echo urlencode($recherche); ?>" class="pagination-link">»</a>
                        <?php endif; ?>
                    </div>
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
            
            // Filtres
            const filterForm = document.getElementById('filtersForm');
            const filterSelects = filterForm.querySelectorAll('select');
            
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    filterForm.submit();
                });
            });
            
            // Sélection en masse
            const selectAll = document.getElementById('selectAll');
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const applyBulkButton = document.getElementById('applyBulkAction');
            
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const isChecked = this.checked;
                    
                    productCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });
                    
                    updateBulkActionButton();
                });
            }
            
            if (productCheckboxes.length > 0) {
                productCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        updateBulkActionButton();
                        
                        // Vérifier si toutes les cases sont cochées
                        const allChecked = Array.from(productCheckboxes).every(c => c.checked);
                        if (selectAll) {
                            selectAll.checked = allChecked;
                        }
                    });
                });
            }
            
            function updateBulkActionButton() {
                const anyChecked = Array.from(productCheckboxes).some(c => c.checked);
                if (applyBulkButton) {
                    applyBulkButton.disabled = !anyChecked;
                }
            }
            
            // Validation du formulaire d'actions groupées
            const bulkForm = document.getElementById('bulkForm');
            const bulkActionSelect = document.getElementById('bulk_action');
            
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    const selectedAction = bulkActionSelect.value;
                    const selectedProducts = Array.from(productCheckboxes).filter(c => c.checked);
                    
                    if (!selectedAction) {
                        e.preventDefault();
                        alert('Veuillez sélectionner une action à effectuer.');
                        return false;
                    }
                    
                    if (selectedProducts.length === 0) {
                        e.preventDefault();
                        alert('Veuillez sélectionner au moins un produit.');
                        return false;
                    }
                    
                    if (selectedAction === 'delete') {
                        if (!confirm('Êtes-vous sûr de vouloir supprimer les produits sélectionnés ? Cette action est irréversible.')) {
                            e.preventDefault();
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>