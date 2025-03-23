<?php
// etageres_ajax.php - Script pour récupérer les étagères en AJAX
// Ce fichier doit être placé dans le même répertoire que add-product.php

// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Définir l'en-tête pour indiquer que la réponse est au format JSON
header('Content-Type: application/json');

// Inclure le fichier de connexion à la base de données
require_once '../components/current/db_connect.php';

// Vérifier la présence du paramètre id_armoire
if (!isset($_GET['id_armoire']) || empty($_GET['id_armoire'])) {
    echo json_encode(['error' => 'ID d\'armoire manquant']);
    exit;
}

// Récupérer et sécuriser l'ID de l'armoire
$id_armoire = intval($_GET['id_armoire']);

// Préparer et exécuter la requête pour récupérer les étagères
$etageres = [];
$query = "SELECT id_etagere, numero_etagere, description 
          FROM etageres 
          WHERE id_armoire = ? 
          ORDER BY numero_etagere";

$stmt = $link_stockage->prepare($query);
$stmt->bind_param("i", $id_armoire);
$stmt->execute();
$result = $stmt->get_result();

// Récupérer les résultats
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $etageres[] = $row;
    }
}

// Renvoyer les données au format JSON
echo json_encode($etageres);
?>