<?php
// index.php - Page principale de l'application
require_once './src/components/current/db_connect.php';
require_once './src/api/functions.php'; // Ajustez le chemin selon votre structure

// Définir la page active pour le menu
$page_active = 'dashboard';

// Récupérer les statistiques
$stats = getStats($link_stockage);

// Récupérer les armoires
$armoires = getArmoires($link_stockage);

// Récupérer les produits récents
$produits = getProduits($link_stockage, '', 5);

// Récupérer les mouvements récents
$mouvements = getMouvementsRecents($link_stockage, 5);

// Récupérer les alertes actives
$alertes = getAlertesActives($link_stockage);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AJI-STOCK - Gestion des Armoires Techniques</title>
    <link rel="stylesheet" href="./css/index.css">
</head>
<body>
    <?php include_once './src/components/current/sidebar.php'; ?>
 <?php include_once './src/components/current/header.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Title and Actions -->
        <div class="title-container">
            <h1>
                Gestion des Armoire Techniques
            </h1>
        </div>
        
        <div class="actions-container">
            <a href="export.php" class="btn btn-outline">Exporter vers Excel</a>
            <a href="./templates/add-product/add-product.php" class="btn btn-success">+ Ajouter un produit</a>
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
                    <a href="inventory" class="btn btn-outline btn-sm">Voir tout l'inventaire</a>
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

    <script src="./src/script/index/index.js">
        <script src="./src/script/index/navigation.js">
    
    </script>
</body>
</html>