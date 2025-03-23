<?php
// add-product.php - Formulaire d'ajout d'un produit
require_once '../../src/components/current/db_connect.php';
require_once '../../src/api/add-product-functions.php';

// Récupérer les catégories
$categories = getCategories($link_stockage);

// Récupérer les armoires, étagères et sections
$armoires = getArmoires($link_stockage);
$etageres = [];
$sections = [];

// Traitement du formulaire
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation du formulaire
    $reference = trim($_POST['reference'] ?? '');
    $nom_produit = trim($_POST['nom_produit'] ?? '');
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $fournisseur = trim($_POST['fournisseur'] ?? '');
    $prix_unitaire = floatval(str_replace(',', '.', $_POST['prix_unitaire'] ?? 0));
    $seuil_alerte = intval($_POST['seuil_alerte'] ?? 10);
    $id_section = intval($_POST['id_section'] ?? 0);
    $quantite = intval($_POST['quantite'] ?? 0);
    
    // Validation des champs obligatoires
    $errors = [];
    
    if (empty($reference)) {
        $errors[] = "La référence est obligatoire";
    } else {
        // Vérifier si la référence existe déjà
        $stmt = $link_stockage->prepare("SELECT id_produit FROM produits WHERE reference = ?");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Cette référence existe déjà";
        }
    }
    
    if (empty($nom_produit)) {
        $errors[] = "Le nom du produit est obligatoire";
    }
    
    if ($id_categorie <= 0) {
        $errors[] = "Veuillez sélectionner une catégorie";
    }
    
    if ($id_section <= 0) {
        $errors[] = "Veuillez sélectionner un emplacement (section)";
    }
    
    if (empty($errors)) {
        // Insertion du produit
        $stmt = $link_stockage->prepare("
            INSERT INTO produits (reference, nom_produit, id_categorie, description, fournisseur, prix_unitaire, seuil_alerte)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssissdi", $reference, $nom_produit, $id_categorie, $description, $fournisseur, $prix_unitaire, $seuil_alerte);
        
        if ($stmt->execute()) {
            $id_produit = $link_stockage->insert_id;
            
            // Insertion de l'inventaire (stock initial)
            if ($quantite > 0) {
                $date_now = date('Y-m-d H:i:s');
                $stmt = $link_stockage->prepare("
                    INSERT INTO inventaire (id_produit, id_section, quantite, date_derniere_entree)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("iiis", $id_produit, $id_section, $quantite, $date_now);
                $stmt->execute();
                
                // Ajout du mouvement d'entrée
                $type_mouvement = 'entrée';
                $commentaire = 'Création initiale du produit';
                $utilisateur = 'Admin'; // À remplacer par l'utilisateur connecté
                
                $stmt = $link_stockage->prepare("
                    INSERT INTO mouvements (id_produit, id_section, type_mouvement, quantite, utilisateur, commentaire)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iisiss", $id_produit, $id_section, $type_mouvement, $quantite, $utilisateur, $commentaire);
                $stmt->execute();
            }
            
            $message = "Le produit a été ajouté avec succès";
            $messageType = "success";
            
            // Rediriger vers la liste des produits après un court délai
            header("Refresh: 2; URL=../inventory/inventory.php");
        } else {
            $message = "Erreur lors de l'ajout du produit: " . $link_stockage->error;
            $messageType = "error";
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = "error";
    }
}

// Si une armoire est sélectionnée, récupérer ses étagères
if (isset($_GET['id_armoire'])) {
    $id_armoire = intval($_GET['id_armoire']);
    $etageres = getEtageres($link_stockage, $id_armoire);
}

// Si une étagère est sélectionnée, récupérer ses sections
if (isset($_GET['id_etagere'])) {
    $id_etagere = intval($_GET['id_etagere']);
    $sections = getSections($link_stockage, $id_etagere);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un produit - AJI-STOCK</title>
    <link rel="stylesheet" href="../../css/index.css">
    <link rel="stylesheet" href="../../css/add-product.css">
    <style>
       
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="../index.php" class="logo">Stock<span>Manager</span></a>
        <ul class="sidebar-menu">
            <li><a href="../index.php"><span class="icon">📊</span> Tableau de bord</a></li>
            <li><a href="../inventory" class="active"><span class="icon">📦</span> Inventaire</a></li>
            <li><a href="../mouvements.php"><span class="icon">🔄</span> Mouvements</a></li>
            <li><a href="../armoires.php"><span class="icon">🏢</span> Armoires</a></li>
            <li><a href="../sections.php"><span class="icon">📑</span> Sections</a></li>
            <li><a href="../fournisseurs.php"><span class="icon">👥</span> Fournisseurs</a></li>
            <li><a href="../commandes.php"><span class="icon">🛒</span> Commandes</a></li>
            <li><a href="../rapports.php"><span class="icon">📝</span> Rapports</a></li>
            <li><a href="../alertes.php"><span class="icon">⚠️</span> Alertes</a></li>
            <li><a href="../recherche.php"><span class="icon">🔍</span> Recherche avancée</a></li>
            <li><a href="../parametres.php"><span class="icon">⚙️</span> Paramètres</a></li>
        </ul>
    </div>

    <!-- Header -->
    <header>
        <div class="main-nav">
            <a href="../index.php">Tableau de bord</a>
            <a href="../inventory" class="active">Inventaire</a>
            <a href="../fournisseurs.php">Fournisseurs</a>
            <a href="../rapports.php">Rapports</a>
            <a href="../parametres.php">Paramètres</a>
        </div>
        <div class="user-info">
            <div class="user-avatar">MM</div>
            <span>Marc MARTIN</span>
            <div class="notification-icon">🔔
                <span class="notification-count">3</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="title-container">
            <h1>Ajouter un produit</h1>
        </div>
        
        <div class="actions-container">
            <a href="../inventory/inventory.php" class="btn btn-outline">← Retour à l'inventaire</a>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="">
                <!-- Informations principales -->
                <div class="form-section">
                    <h3>Informations du produit</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="reference" class="required-field">Référence</label>
                                <input type="text" id="reference" name="reference" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="nom_produit" class="required-field">Nom du produit</label>
                                <input type="text" id="nom_produit" name="nom_produit" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="id_categorie" class="required-field">Catégorie</label>
                                <select id="id_categorie" name="id_categorie" class="form-control" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $categorie): ?>
                                        <option value="<?php echo $categorie['id_categorie']; ?>">
                                            <?php echo htmlspecialchars($categorie['nom_categorie']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="fournisseur">Fournisseur</label>
                                <input type="text" id="fournisseur" name="fournisseur" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="prix_unitaire">Prix unitaire (€)</label>
                                <input type="number" id="prix_unitaire" name="prix_unitaire" step="0.01" min="0" class="form-control">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="seuil_alerte">Seuil d'alerte</label>
                                <input type="number" id="seuil_alerte" name="seuil_alerte" value="10" min="1" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"></textarea>
                    </div>
                </div>
                
                <!-- Emplacement et stock -->
                <div class="form-section">
                    <h3>Emplacement et stock initial</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="id_armoire" class="required-field">Armoire</label>
                                <select id="id_armoire" name="id_armoire" class="form-control" required>
                                    <option value="">Sélectionner une armoire</option>
                                    <?php foreach ($armoires as $armoire): ?>
                                        <option value="<?php echo $armoire['id_armoire']; ?>">
                                            <?php echo htmlspecialchars($armoire['nom_armoire']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="id_etagere" class="required-field">Étage</label>
                                <select id="id_etagere" name="id_etagere" class="form-control" required disabled>
                                    <option value="">Sélectionner d&apos;abord une armoire</option>
                                    <?php foreach ($etageres as $etagere): ?>
                                        <option value="<?php echo $etagere['id_etagere']; ?>">
                                            Étage <?php echo $etagere['numero_etagere']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="id_section" class="required-field">Section</label>
                                <select id="id_section" name="id_section" class="form-control" required disabled>
                                    <option value="">Sélectionner d&apos;abord un étage</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?php echo $section['id_section']; ?>">
                                            Section <?php echo $section['numero_section']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="quantite">Quantité initiale</label>
                                <input type="number" id="quantite" name="quantite" value="0" min="0" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="btn-container">
                    <a href="../inventory/inventory.php" class="btn btn-outline">Annuler</a>
                    <button type="submit" class="btn btn-success">Ajouter le produit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile menu toggle -->
    <div class="menu-toggle">☰</div>

    <script src="../../src/script/add-product/add-product.js"></script>
</body>
</html>