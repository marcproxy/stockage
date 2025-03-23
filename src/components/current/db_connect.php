<?php

// Infos for connexion to the database
$db_host = 'localhost';
$db_name = 'stockage';
$db_user = 'root';
$db_pass = 'root'; 

// Etablish connnexion
$link_stockage = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connexion
if ($link_stockage->connect_error) {
    die("Erreur de connexion à la base de données: " . $link_stockage->connect_error);
}

// Set encoding
$link_stockage->set_charset("utf8mb4");

// Define constants
define('BASE_URL', '/stockage');  // Chemin de base du site