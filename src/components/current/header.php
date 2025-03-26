<?php
// header.php - Fichier du header et de la barre de navigation supérieure
// Vérifier si la constante TEMPLATES_PATH est définie
if (!defined('TEMPLATES_PATH')) {
    define('TEMPLATES_PATH', '/stockage'); // Valeur par défaut, à remplacer par votre config
}

// S'assurer que $page_active est défini
$page_active = $page_active ?? '';

// S'assurer que $alertes est défini
if (!isset($alertes) || !is_array($alertes)) {
    $alertes = [];
}
?>
<!-- Header -->
<header>
    <div class="main-nav">
        <a href="<?= TEMPLATES_PATH ?>/index.php" class="<?= $page_active === 'dashboard' ? 'active' : '' ?>">Tableau de bord</a>
        <a href="<?= TEMPLATES_PATH ?>/templates/inventory/inventory.php" class="<?= $page_active === 'inventory' ? 'active' : '' ?>">Inventaire</a>
        <a href="<?= TEMPLATES_PATH ?>/fournisseurs.php" class="<?= $page_active === 'fournisseurs' ? 'active' : '' ?>">Fournisseurs</a>
        <a href="<?= TEMPLATES_PATH ?>/rapports.php" class="<?= $page_active === 'rapports' ? 'active' : '' ?>">Rapports</a>
        <a href="<?= TEMPLATES_PATH ?>/parametres.php" class="<?= $page_active === 'parametres' ? 'active' : '' ?>">Paramètres</a>
    </div>
    <div class="user-info">
        <div class="user-avatar">MM</div>
        <span>Marc MARTIN</span>
        <div class="notification-icon">🔔
            <span class="notification-count"><?php echo count($alertes); ?></span>
        </div>
    </div>
</header>