<?php

// Inclure le fichier de configuration
require_once '../../src/components/current/config.php';

// Inclure les fichiers de connexion et fonctions
require_once COMPONENTS_PATH . '/db_connect.php';
require_once API_PATH . '/functions.php';
require_once API_PATH . '/reservations-functions.php';

// Définir la page active pour le menu
$page_active = 'reservations';

// Récupérer l'ID de l'utilisateur connecté
$id_utilisateur_connecte = isset($_SESSION['id_utilisateur']) ? $_SESSION['id_utilisateur'] : 0;

// Récupérer la liste des produits disponibles pour la réservation
$produits = getProduitsReservation($link_stockage, 'disponible', 1);

// Traitement du formulaire lors de la soumission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $id_produit = isset($_POST['produit']) ? intval($_POST['produit']) : 0;
    $quantite = isset($_POST['quantite']) ? intval($_POST['quantite']) : 0;
    $motif = isset($_POST['motif']) ? trim($_POST['motif']) : '';
    $id_utilisateur = isset($_POST['utilisateur']) ? intval($_POST['utilisateur']) : $id_utilisateur_connecte;
    
    // Date actuelle pour la réservation
    $date_actuelle = date('Y-m-d');
    
    // Validation des données
    if ($id_produit <= 0) {
        $error_message = "Veuillez sélectionner un produit valide.";
    } elseif ($quantite <= 0) {
        $error_message = "La quantité doit être supérieure à zéro.";
    } elseif (empty($motif)) {
        $error_message = "Veuillez indiquer le motif de la réservation.";
    } elseif ($id_utilisateur <= 0) {
        $error_message = "Utilisateur invalide.";
    } else {
        // Vérifier la disponibilité du produit
        $disponibilite = checkDisponibiliteProduit($link_stockage, $id_produit, $quantite);
        
        if (!$disponibilite['disponible']) {
            $error_message = "Ce produit n'est pas disponible en quantité suffisante. Quantité disponible: " . $disponibilite['quantite_disponible'];
        } else {
            // Créer la réservation
            $date_demande = date('Y-m-d H:i:s');
            $statut = 'en_attente';
            
            $sql = "INSERT INTO reservations 
                    (id_produit, id_utilisateur, quantite, date_demande, motif, statut) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $link_stockage->prepare($sql);
            $stmt->bind_param("iiisss", $id_produit, $id_utilisateur, $quantite, $date_demande, $motif, $statut);
            
            if ($stmt->execute()) {
                $id_reservation = $stmt->insert_id;
                $success_message = "Réservation créée avec succès! Numéro de réservation: " . $id_reservation;
                
                // Redirection après 2 secondes
                header("refresh:2;url=../reservations.php");
            } else {
                $error_message = "Erreur lors de la création de la réservation: " . $stmt->error;
            }
        }
    }
}

// Récupérer la liste des utilisateurs si l'utilisateur actuel a les droits d'admin
$utilisateurs = [];
$is_admin = isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'gestionnaire');

if ($is_admin) {
    $query = "SELECT id_utilisateur, CONCAT(prenom, ' ', nom) AS nom_complet FROM utilisateurs ORDER BY nom";
    $result = $link_stockage->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $utilisateurs[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Réservation - AJI-STOCK</title>
    <link rel="stylesheet" href="<?= HOME_BASE_URL ?>/css/index.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-text {
            margin-top: 5px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .product-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            background-color: #f9f9f9;
        }
        
        .product-info {
            margin-bottom: 10px;
        }
        
        .product-info strong {
            color: #333;
        }
        
        .product-stock {
            color: #28a745;
            font-weight: 600;
        }
        
        .product-stock.low {
            color: #ffc107;
        }
        
        .product-stock.critical {
            color: #dc3545;
        }
        
        #date_debut, #date_fin {
            min-width: 100%;
        }
    </style>
</head>
<body>
<?php include COMPONENTS_PATH. '/sidebar.php'; ?>
<?php include COMPONENTS_PATH .'/header.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="title-container">
            <h1>Nouvelle Réservation</h1>
        </div>
        
        <div class="breadcrumbs">
            <a href="../reservations.php">Réservations</a> &gt; Nouvelle Réservation
        </div>
        
        <div class="content-section">
            <div class="section-header">
                <h2>Formulaire de réservation</h2>
            </div>
            <div class="section-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-container">
                    <form action="new-reservation.php" method="post">
                        <?php if ($is_admin): ?>
                            <div class="form-group">
                                <label for="utilisateur" class="form-label">Utilisateur*</label>
                                <select id="utilisateur" name="utilisateur" class="form-control" required>
                                    <option value="">Sélectionner un utilisateur</option>
                                    <?php foreach ($utilisateurs as $user): ?>
                                        <option value="<?php echo $user['id_utilisateur']; ?>" <?php echo ($user['id_utilisateur'] == $id_utilisateur_connecte) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['nom_complet']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="utilisateur" value="<?php echo $id_utilisateur_connecte; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="produit" class="form-label">Produit*</label>
                            <select id="produit" name="produit" class="form-control" required onchange="updateProductInfo()">
                                <option value="">Sélectionner un produit</option>
                                <?php foreach ($produits as $produit): ?>
                                    <option value="<?php echo $produit['id_produit']; ?>" 
                                            data-stock="<?php echo $produit['quantite_disponible']; ?>"
                                            data-reference="<?php echo htmlspecialchars($produit['reference']); ?>"
                                            data-description="<?php echo htmlspecialchars($produit['description']); ?>">
                                        <?php echo htmlspecialchars($produit['reference'] . ' - ' . $produit['nom_produit']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div id="product-details" class="product-card" style="display: none;">
                                <div class="product-info">
                                    <strong>Référence:</strong> <span id="product-reference"></span>
                                </div>
                                <div class="product-info">
                                    <strong>Description:</strong> <span id="product-description"></span>
                                </div>
                                <div class="product-info">
                                    <strong>Stock disponible:</strong> <span id="product-stock" class="product-stock"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantite" class="form-label">Quantité*</label>
                            <input type="number" id="quantite" name="quantite" class="form-control" min="1" required>
                            <div class="form-text">Nombre d'unités à réserver</div>
                        </div>
                        

                        
                        <div class="form-group">
                            <label for="motif" class="form-label">Motif de la réservation*</label>
                            <textarea id="motif" name="motif" class="form-control" rows="4" required></textarea>
                            <div class="form-text">Veuillez expliquer brièvement la raison de cette réservation</div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">Créer la réservation</button>
                            <a href="../reservations.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
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
            
            // Aucune validation de date n'est nécessaire car les champs ont été supprimés
            
            // Initialize product info if a product is already selected
            updateProductInfo();
        });
        
        function updateProductInfo() {
            const productSelect = document.getElementById('produit');
            const productDetails = document.getElementById('product-details');
            const productReference = document.getElementById('product-reference');
            const productDescription = document.getElementById('product-description');
            const productStock = document.getElementById('product-stock');
            const quantityInput = document.getElementById('quantite');
            
            if (productSelect.value) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const stock = parseInt(selectedOption.dataset.stock);
                
                productReference.textContent = selectedOption.dataset.reference;
                productDescription.textContent = selectedOption.dataset.description || 'Aucune description disponible';
                productStock.textContent = stock + ' unité(s)';
                
                // Set max quantity to available stock
                quantityInput.max = stock;
                
                // Style stock indication based on quantity
                if (stock > 10) {
                    productStock.className = 'product-stock';
                } else if (stock > 3) {
                    productStock.className = 'product-stock low';
                } else {
                    productStock.className = 'product-stock critical';
                }
                
                productDetails.style.display = 'block';
            } else {
                productDetails.style.display = 'none';
            }
        }
    </script>
</body>
</html>