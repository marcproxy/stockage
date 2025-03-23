<?php
// index.php - Page principale de l'application
require_once 'config.php';
require_once 'src/api/functions.php'; // Ajustez le chemin selon votre structure

// Récupérer les statistiques
$stats = getStats($mysqli);

// Récupérer les armoires
$armoires = getArmoires($mysqli);

// Récupérer les produits récents
$produits = getProduits($mysqli, '', 5);

// Récupérer les mouvements récents
$mouvements = getMouvementsRecents($mysqli, 5);

// Récupérer les alertes actives
$alertes = getAlertesActives($mysqli);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockManager - Gestion des Armoires Techniques</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e67e22;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --sidebar-width: 220px;
            --header-height: 60px;
            --bg-color: #f5f9fc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--bg-color);
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Header styles */
        header {
            background-color: var(--dark-color);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 20px 0 var(--sidebar-width);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .main-nav {
            display: flex;
            align-items: center;
            margin-left: 40px;
        }
        
        .main-nav a {
            color: white;
            text-decoration: none;
            padding: 0 15px;
            font-size: 0.95rem;
            font-weight: 500;
            opacity: 0.85;
            transition: opacity 0.2s;
        }
        
        .main-nav a:hover, .main-nav a.active {
            opacity: 1;
        }
        
        .user-info {
            margin-left: auto;
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .notification-icon {
            margin-left: 15px;
            font-size: 1.2rem;
            position: relative;
            cursor: pointer;
            color: #ffd700;
        }
        
        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Sidebar styles */
        .sidebar {
            background-color: white;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 900;
            box-shadow: 1px 0 3px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        
        .logo {
            padding: 15px 20px;
            display: block;
            font-size: 1.6rem;
            font-weight: bold;
            text-decoration: none;
            color: var(--dark-color);
            margin-bottom: 10px;
            background-color: var(--dark-color);
            color: white;
            height: var(--header-height);
            display: flex;
            align-items: center;
        }
        
        .logo span {
            color: var(--secondary-color);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0 10px;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 10px;
            color: #555;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.2s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
        }
        
        .sidebar-menu a.active {
            background-color: rgba(52, 152, 219, 0.15);
            font-weight: 600;
        }
        
        .sidebar-menu .icon {
            margin-right: 10px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        /* Main content styles */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 30px;
            min-height: calc(100vh - var(--header-height));
        }
        
        .page-title {
            margin-bottom: 30px;
        }
        
        .title-container {
            margin-bottom: 20px;
        }
        
        .title-container h1 {
            font-size: 2.5rem;
            color: var(--dark-color);
            font-weight: 700;
            line-height: 1.2;
        }
        
        .title-container h1 span {
            display: block;
        }
        
        .actions-container {
            display: flex;
            justify-content: flex-start;
            margin: 20px 0;
            gap: 15px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-outline {
            background-color: white;
            border: 1px solid #ddd;
            color: #555;
        }
        
        .btn-outline:hover {
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* Stats grid */
        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            flex: 1;
            min-width: 220px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-right: 15px;
        }
        
        .stat-icon.products {
            background-color: #eaf3ff;
            color: var(--secondary-color);
        }
        
        .stat-icon.storage {
            background-color: #e8f7ff;
            color: var(--secondary-color);
        }
        
        .stat-icon.alerts {
            background-color: #ffefe7;
            color: var(--warning-color);
        }
        
        .stat-icon.activity {
            background-color: #e7ffe9;
            color: var(--success-color);
        }
        
        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 3px;
            font-weight: 600;
        }
        
        .stat-info p {
            color: #777;
            font-size: 0.9rem;
        }
        
        /* Content sections */
        .content-section {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .section-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            font-size: 1.2rem;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .section-body {
            padding: 20px;
        }
        
        /* Armoire visualization */
        .armoire-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .armoire-item {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .armoire-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .armoire-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .armoire-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .armoire-count {
            font-size: 0.8rem;
            padding: 2px 8px;
            border-radius: 20px;
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--secondary-color);
        }
        
        .progress-container {
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .progress-fill.low {
            background-color: var(--success-color);
        }
        
        .progress-fill.medium {
            background-color: var(--secondary-color);
        }
        
        .progress-fill.high {
            background-color: var(--warning-color);
        }
        
        .progress-fill.critical {
            background-color: var(--danger-color);
        }
        
        .progress-label {
            font-size: 0.8rem;
            text-align: right;
            color: #777;
            margin-top: 2px;
        }
        
        /* Table styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #eee;
        }
        
        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.success {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
        }
        
        .status-badge.warning {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
        }
        
        .status-badge.danger {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
        }
        
        .action-btn {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1rem;
            padding: 3px;
            margin: 0 3px;
            transition: color 0.2s;
        }
        
        .action-btn:hover {
            color: var(--secondary-color);
        }
        
        .action-btn.delete:hover {
            color: var(--danger-color);
        }
        
        /* Search styles */
        .search-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .search-input {
            width: 100%;
            padding: 8px 15px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 0.9rem;
        }
        
        /* Responsive */
        .menu-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            text-align: center;
            line-height: 50px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 992px) {
            .main-nav {
                display: none;
            }
            
            header {
                padding: 0 20px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .armoire-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="index.php" class="logo">Stock<span>Manager</span></a>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><span class="icon">📊</span> Tableau de bord</a></li>
            <li><a href="inventaire.php"><span class="icon">📦</span> Inventaire</a></li>
            <li><a href="mouvements.php"><span class="icon">🔄</span> Mouvements</a></li>
            <li><a href="armoires.php"><span class="icon">🏢</span> Armoires</a></li>
            <li><a href="sections.php"><span class="icon">📊</span> Sections</a></li>
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
            <a href="index.php" class="active">Tableau de bord</a>
            <a href="inventaire.php">Inventaire</a>
            <a href="fournisseurs.php">Fournisseurs</a>
            <a href="rapports.php">Rapports</a>
            <a href="parametres.php">Paramètres</a>
        </div>
        <div class="user-info">
            <div class="user-avatar">JD</div>
            <span>Jean Dupont</span>
            <div class="notification-icon">🔔
                <span class="notification-count"><?php echo count($alertes); ?></span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Title and Actions -->
        <div class="title-container">
            <h1>
                <span>Gestion</span>
                <span>des</span>
                <span>Armoires</span>
                <span>Techniques</span>
            </h1>
        </div>
        
        <div class="actions-container">
            <a href="export.php" class="btn btn-outline">Exporter vers Excel</a>
            <a href="./templates/add-product.php" class="btn btn-success">+ Ajouter un produit</a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon products">📦</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_references']; ?></h3>
                    <p>Références stockées</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon storage">🏢</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_armoires']; ?></h3>
                    <p>Armoires actives</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon alerts">⚠️</div>
                <div class="stat-info">
                    <h3><?php echo $stats['produits_alerte']; ?></h3>
                    <p>Produits en alerte</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon activity">🔄</div>
                <div class="stat-info">
                    <h3><?php echo $stats['mouvements_mois']; ?></h3>
                    <p>Mouvements ce mois</p>
                </div>
            </div>
        </div>

        <!-- Armoires Section -->
        <div class="content-section">
            <div class="section-header">
                <h2>Visualisation des armoires</h2>
                <a href="armoires.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <div class="section-body">
                <div class="armoire-grid">
                    <?php foreach ($armoires as $armoire): ?>
                        <?php 
                            // Déterminer la classe de couleur en fonction de l'occupation
                            $fillClass = 'low';
                            if ($armoire['occupation'] >= 90) {
                                $fillClass = 'critical';
                            } elseif ($armoire['occupation'] >= 80) {
                                $fillClass = 'high';
                            } elseif ($armoire['occupation'] >= 60) {
                                $fillClass = 'medium';
                            }
                        ?>
                        <div class="armoire-item">
                            <div class="armoire-header">
                                <div class="armoire-title"><?php echo htmlspecialchars($armoire['nom']); ?></div>
                                <div class="armoire-count"><?php echo $armoire['references']; ?>/<?php echo $armoire['capacite']; ?></div>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill <?php echo $fillClass; ?>" style="width: <?php echo $armoire['occupation']; ?>%"></div>
                                </div>
                                <div class="progress-label"><?php echo $armoire['occupation']; ?>% occupée</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Inventory Section -->
        <div class="content-section">
            <div class="section-header">
                <h2>Produits récents</h2>
                <div class="search-container" style="width: 300px;">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchProduit" class="search-input" placeholder="Rechercher un produit...">
                </div>
            </div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Produit</th>
                            <th>Armoire</th>
                            <th>Étage</th>
                            <th>Section</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produits)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Aucun produit trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produits as $produit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($produit['reference']); ?></td>
                                    <td><?php echo htmlspecialchars($produit['nom_produit']); ?></td>
                                    <td><?php echo htmlspecialchars($produit['nom_armoire']); ?></td>
                                    <td><?php echo $produit['numero_etagere']; ?></td>
                                    <td><?php echo $produit['numero_section']; ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'success';
                                            $statusText = 'En stock';
                                            
                                            if ($produit['statut'] == 'rupture') {
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
                                        <a href="produit-editer.php?id=<?php echo $produit['id_produit']; ?>" class="action-btn">✏️</a>
                                        <a href="produit-supprimer.php?id=<?php echo $produit['id_produit']; ?>" class="action-btn delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="text-align: right; margin-top: 15px;">
                    <a href="inventaire.php" class="btn btn-outline btn-sm">Voir tout l'inventaire</a>
                </div>
            </div>
        </div>

        <!-- Recent Movements Section -->
        <div class="content-section">
            <div class="section-header">
                <h2>Mouvements récents</h2>
                <a href="mouvements.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Produit</th>
                            <th>Type</th>
                            <th>Quantité</th>
                            <th>Emplacement</th>
                            <th>Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mouvements)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Aucun mouvement récent</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mouvements as $mouvement): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mouvement['date_mouvement'])); ?></td>
                                    <td><?php echo htmlspecialchars($mouvement['reference'] . ' - ' . $mouvement['nom_produit']); ?></td>
                                    <td>
                                        <?php 
                                            $typeIcon = '🔄';
                                            if ($mouvement['type_mouvement'] == 'entrée') {
                                                $typeIcon = '➕';
                                            } elseif ($mouvement['type_mouvement'] == 'sortie') {
                                                $typeIcon = '➖';
                                            }
                                            echo $typeIcon . ' ' . ucfirst($mouvement['type_mouvement']);
                                        ?>
                                    </td>
                                    <td><?php echo $mouvement['quantite']; ?></td>
                                    <td>
                                        <?php 
                                            echo htmlspecialchars($mouvement['armoire_source'] . ', Étage ' . $mouvement['etage_source'] . ', Section ' . $mouvement['section_source']);
                                            
                                            if ($mouvement['type_mouvement'] == 'transfert' && !empty($mouvement['armoire_destination'])) {
                                                echo ' → ' . htmlspecialchars($mouvement['armoire_destination'] . ', Étage ' . $mouvement['etage_destination'] . ', Section ' . $mouvement['section_destination']);
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mouvement['utilisateur']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alerts Section -->
        <?php if (!empty($alertes)): ?>
        <div class="content-section">
            <div class="section-header">
                <h2>Alertes actives</h2>
                <a href="alertes.php" class="btn btn-outline btn-sm">Voir tout</a>
            </div>
            <div class="section-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Produit</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertes as $alerte): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($alerte['date_alerte'])); ?></td>
                                <td><?php echo htmlspecialchars($alerte['reference'] . ' - ' . $alerte['nom_produit']); ?></td>
                                <td>
                                    <?php 
                                        $typeClass = 'warning';
                                        $typeText = 'Stock faible';
                                        
                                        if ($alerte['type_alerte'] == 'critique') {
                                            $typeClass = 'danger';
                                            $typeText = 'Critique';
                                        } elseif ($alerte['type_alerte'] == 'defectueux') {
                                            $typeClass = 'danger';
                                            $typeText = 'Défectueux';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $typeClass; ?>"><?php echo $typeText; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($alerte['message']); ?></td>
                                <td>
                                    <a href="alerte-resoudre.php?id=<?php echo $alerte['id_alerte']; ?>" class="btn btn-outline btn-sm">Résoudre</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Mobile menu toggle -->
    <div class="menu-toggle">☰</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            // Toggle sidebar on mobile
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
            
            // Search functionality
            const searchInput = document.getElementById('searchProduit');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        window.location.href = 'inventaire.php?search=' + encodeURIComponent(this.value);
                    }
                });
            }
        });
    </script>
</body>
</html>