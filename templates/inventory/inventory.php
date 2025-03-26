<?php
// inventory - Page de gestion de l'inventaire

// Inclure le fichier de configuration
require_once '../../src/components/current/config.php';

// Inclure les fichiers de connexion et fonctions
require_once COMPONENTS_PATH . '/db_connect.php';
require_once API_PATH . '/inventory-functions.php';

// Définir la page active pour le menu (correction de l'opérateur d'affectation)
$page_active = 'inventory';

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

// Récupérer les alertes actives (nécessaire pour le header)
$alertes = getAlertesActives($link_stockage);

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
    <link rel="stylesheet" href="<?= CSS_PATH ?>/index.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/inventory.css">
</head>
<body>
  <?php include COMPONENTS_PATH. '/sidebar.php'; ?>
  <?php include COMPONENTS_PATH .'/header.php'; ?>
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

    <script src="<?= JS_PATH ?>/inventory/inventory.js">
   
    </script>
</body>
</html>