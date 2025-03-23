<?php
// config.php - À placer dans le même dossier que index.php

// Informations de connexion à la base de données
$db_host = 'localhost';
$db_name = 'stockage';
$db_user = 'root';  // Généralement 'root' pour MAMP
$db_pass = 'root';  // Généralement 'root' pour MAMP (ou parfois vide '')

// Établir la connexion
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Vérifier la connexion
if ($mysqli->connect_error) {
    die("Erreur de connexion à la base de données: " . $mysqli->connect_error);
}

// S'assurer que les caractères sont encodés en UTF-8
$mysqli->set_charset("utf8mb4");

// Définir certaines constantes utiles
define('BASE_URL', '/stockage');  // Chemin de base du site