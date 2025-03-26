<?php
// sidebar.php - Fichier de la barre de navigation latérale
// S'assurer que $page_active est défini
$page_active = $page_active ?? '';

// Déterminer l'URL de base (en utilisant le domaine actuel)
$base_url = '/stockage'; // Vous pouvez aussi utiliser HOME_BASE_URL si défini

// S'assurer que $page_active est défini
$page_active = $page_active ?? '';
?>
<!-- Sidebar -->
<div class="sidebar">
    <a href="<?= $base_url ?>/index.php" class="logo">AJI<span>Stock</span></a>
    <ul class="sidebar-menu">
        <li><a href="<?= $base_url ?>/index.php" class="<?= $page_active === 'dashboard' ? 'active' : '' ?>"><span class="icon">📊</span> Tableau de bord</a></li>
        <li><a href="<?= $base_url ?>/templates/inventory/inventory.php" class="<?= $page_active === 'inventory' ? 'active' : '' ?>"><span class="icon">📦</span> Inventaire</a></li>
        <li><a href="<?= $base_url ?>/mouvements.php" class="<?= $page_active === 'mouvements' ? 'active' : '' ?>"><span class="icon">🔄</span> Mouvements</a></li>
        <li><a href="<?= $base_url ?>/armoires.php" class="<?= $page_active === 'armoires' ? 'active' : '' ?>"><span class="icon">🏢</span> Armoires</a></li>
        <li><a href="<?= $base_url ?>/sections.php" class="<?= $page_active === 'sections' ? 'active' : '' ?>"><span class="icon">📊</span> Sections</a></li>
        <li><a href="<?= $base_url ?>/templates/reservations/reservations.php" class="<?= $page_active === 'reservations' ? 'active' : '' ?>"><span class="icon">🔖</span> Réservations</a></li>
        <li><a href="<?= $base_url ?>/fournisseurs.php" class="<?= $page_active === 'fournisseurs' ? 'active' : '' ?>"><span class="icon">👥</span> Fournisseurs</a></li>
        <li><a href="<?= $base_url ?>/commandes.php" class="<?= $page_active === 'commandes' ? 'active' : '' ?>"><span class="icon">🛒</span> Commandes</a></li>
        <li><a href="<?= $base_url ?>/rapports.php" class="<?= $page_active === 'rapports' ? 'active' : '' ?>"><span class="icon">📝</span> Rapports</a></li>
        <li><a href="<?= $base_url ?>/alertes.php" class="<?= $page_active === 'alertes' ? 'active' : '' ?>"><span class="icon">⚠️</span> Alertes</a></li>
        <li><a href="<?= $base_url ?>/recherche.php" class="<?= $page_active === 'recherche' ? 'active' : '' ?>"><span class="icon">🔍</span> Recherche avancée</a></li>
        <li><a href="<?= $base_url ?>/parametres.php" class="<?= $page_active === 'parametres' ? 'active' : '' ?>"><span class="icon">⚙️</span> Paramètres</a></li>
    </ul>
</div>

<!-- Mobile menu toggle -->
<div class="menu-toggle">☰</div>