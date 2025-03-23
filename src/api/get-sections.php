<?php
// sections_ajax.php - Script pour récupérer les sections en AJAX
// Ce fichier doit être placé dans le même répertoire que add-product.php

// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Définir l'en-tête pour indiquer que la réponse est au format JSON
header('Content-Type: application/json');

// Inclure le fichier de connexion à la base de données
require_once '../components/current/db_connect.php';

// Vérifier la présence du paramètre id_etagere
if (!isset($_GET['id_etagere']) || empty($_GET['id_etagere'])) {
    echo json_encode(['error' => 'ID d\'étagère manquant']);
    exit;
}

// Récupérer et sécuriser l'ID de l'étagère
$id_etagere = intval($_GET['id_etagere']);

// Préparer et exécuter la requête pour récupérer les sections
$sections = [];
$query = "SELECT id_section, numero_section, description 
          FROM sections 
          WHERE id_etagere = ? 
          ORDER BY numero_section";

$stmt = $link_stockage->prepare($query);
$stmt->bind_param("i", $id_etagere);
$stmt->execute();
$result = $stmt->get_result();

// Récupérer les résultats
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
}

// Renvoyer les données au format JSON
echo json_encode($sections);
?>