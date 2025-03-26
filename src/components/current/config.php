<?php
// config.php - Fichier de configuration global (à placer à la racine du projet)

// Détermination automatique du chemin absolu de la racine du projet
define('PROJECT_ROOT', 'C:/wamp64/www/stockage');

// Configuration de base
define('HOME_BASE_URL', '/stockage'); // URL de base (pour les liens dans le HTML)

// URL du site
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
define('SITE_URL', $protocol . $domain . HOME_BASE_URL);

// Raccourcis pour les dossiers principaux (avec chemins absolus)
define('COMPONENTS_PATH', PROJECT_ROOT . '/src/components/current');
define('API_PATH', PROJECT_ROOT . '/src/api');
define('TEMPLATES_PATH', PROJECT_ROOT . '/templates');
define('CSS_PATH', PROJECT_ROOT . '/css');
define('JS_PATH', PROJECT_ROOT . '/src/script');
?>