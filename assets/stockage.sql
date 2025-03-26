-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 26 mars 2025 à 20:39
-- Version du serveur :  11.4.2-MariaDB
-- Version de PHP : 7.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `stockage`
--

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

DROP TABLE IF EXISTS `alertes`;
CREATE TABLE IF NOT EXISTS `alertes` (
  `id_alerte` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) DEFAULT NULL,
  `type_alerte` enum('stock_faible','critique','defectueux','autre') NOT NULL,
  `message` text NOT NULL,
  `date_alerte` datetime DEFAULT current_timestamp(),
  `statut` enum('active','traitee','ignoree') DEFAULT 'active',
  `date_resolution` datetime DEFAULT NULL,
  PRIMARY KEY (`id_alerte`),
  KEY `id_produit` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structure de la table `armoires`
--

DROP TABLE IF EXISTS `armoires`;
CREATE TABLE IF NOT EXISTS `armoires` (
  `id_armoire` int(11) NOT NULL AUTO_INCREMENT,
  `nom_armoire` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `emplacement` varchar(100) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_armoire`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `armoires`
--

INSERT INTO `armoires` (`id_armoire`, `nom_armoire`, `description`, `emplacement`, `date_creation`, `date_modification`) VALUES
(1, 'Câble', 'Armoire de stockage des câbles', NULL, '2025-03-23 20:02:27', '2025-03-23 20:02:27'),
(2, 'Foxboro', 'Armoire de composants Foxboro', NULL, '2025-03-23 20:02:27', '2025-03-23 20:02:27'),
(3, 'Rockwell', 'Armoire de composants Rockwell', NULL, '2025-03-23 20:02:27', '2025-03-23 20:02:27'),
(4, 'Informatique A', 'Petite armoire informatique - 3 étages', 'Salle d\'administration', '2025-03-23 20:02:27', '2025-03-23 20:02:27'),
(5, 'Informatique B', 'Petite armoire informatique - 2 étages', 'Salle d\'administration', '2025-03-23 20:02:27', '2025-03-23 20:02:27');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom_categorie`, `description`) VALUES
(1, 'Électronique', 'Composants électroniques divers'),
(2, 'Capteurs', 'Capteurs et détecteurs'),
(3, 'Connectique', 'Câbles et connecteurs'),
(4, 'Composants', 'Composants électriques et mécaniques');

-- --------------------------------------------------------

--
-- Structure de la table `etageres`
--

DROP TABLE IF EXISTS `etageres`;
CREATE TABLE IF NOT EXISTS `etageres` (
  `id_etagere` int(11) NOT NULL AUTO_INCREMENT,
  `id_armoire` int(11) NOT NULL,
  `numero_etagere` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `capacite_max` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_etagere`),
  UNIQUE KEY `id_armoire` (`id_armoire`,`numero_etagere`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `etageres`
--

INSERT INTO `etageres` (`id_etagere`, `id_armoire`, `numero_etagere`, `description`, `capacite_max`) VALUES
(1, 1, 0, 'Étage 0 - Câble', 200),
(2, 2, 0, 'Étage 0 - Foxboro', 200),
(3, 3, 0, 'Étage 0 - Rockwell', 200),
(4, 4, 0, 'Étage 0 - Informatique A', 200),
(5, 5, 0, 'Étage 0 - Informatique B', 200),
(6, 1, 1, 'Étage 1 - Câble', 200),
(7, 2, 1, 'Étage 1 - Foxboro', 200),
(8, 3, 1, 'Étage 1 - Rockwell', 200),
(9, 4, 1, 'Étage 1 - Informatique A', 200),
(10, 5, 1, 'Étage 1 - Informatique B', 200),
(11, 1, 2, 'Étage 2 - Câble', 200),
(12, 2, 2, 'Étage 2 - Foxboro', 200),
(13, 3, 2, 'Étage 2 - Rockwell', 200),
(14, 4, 2, 'Étage 2 - Informatique A', 200),
(15, 5, 2, 'Étage 2 - Informatique B', 200),
(16, 1, 3, 'Étage 3 - Câble', 200),
(17, 2, 3, 'Étage 3 - Foxboro', 200),
(18, 3, 3, 'Étage 3 - Rockwell', 200),
(19, 4, 3, 'Étage 3 - Informatique A', 200),
(20, 5, 3, 'Étage 3 - Informatique B', 200),
(21, 1, 4, 'Étage 4 - Câble', 200),
(22, 2, 4, 'Étage 4 - Foxboro', 200),
(23, 3, 4, 'Étage 4 - Rockwell', 200),
(24, 4, 4, 'Étage 4 - Informatique A', 200),
(25, 5, 4, 'Étage 4 - Informatique B', 200),
(26, 1, 5, 'Étage 5 - Câble', 200),
(27, 2, 5, 'Étage 5 - Foxboro', 200),
(28, 3, 5, 'Étage 5 - Rockwell', 200),
(29, 4, 5, 'Étage 5 - Informatique A', 200),
(30, 5, 5, 'Étage 5 - Informatique B', 200);

-- --------------------------------------------------------

--
-- Structure de la table `historique_reservations`
--

DROP TABLE IF EXISTS `historique_reservations`;
CREATE TABLE IF NOT EXISTS `historique_reservations` (
  `id_historique` int(11) NOT NULL AUTO_INCREMENT,
  `id_reservation` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `statut_avant` varchar(20) DEFAULT NULL,
  `statut_apres` varchar(20) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `date_action` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historique`),
  KEY `id_reservation` (`id_reservation`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `historique_reservations`
--

INSERT INTO `historique_reservations` (`id_historique`, `id_reservation`, `id_utilisateur`, `action`, `statut_avant`, `statut_apres`, `commentaire`, `date_action`) VALUES
(1, 1, 1, 'Création', NULL, 'en_attente', 'Création de la réservation', '2025-03-26 21:35:43'),
(2, 2, 1, 'Création', NULL, 'en_attente', 'Création de la réservation', '2025-03-26 21:35:43'),
(3, 2, 1, 'Changement de statut', 'en_attente', 'approuvee', 'Validation après vérification du stock', '2025-03-26 21:35:43'),
(4, 3, 2, 'Création', NULL, 'en_attente', 'Création de la réservation', '2025-03-26 21:35:43'),
(5, 3, 1, 'Changement de statut', 'en_attente', 'approuvee', 'Approuvé', '2025-03-26 21:35:43'),
(6, 3, 1, 'Changement de statut', 'approuvee', 'terminee', 'Matériel retourné', '2025-03-26 21:35:43');

-- --------------------------------------------------------

--
-- Structure de la table `inventaire`
--

DROP TABLE IF EXISTS `inventaire`;
CREATE TABLE IF NOT EXISTS `inventaire` (
  `id_inventaire` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) NOT NULL,
  `id_section` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `date_derniere_entree` datetime DEFAULT NULL,
  `date_derniere_sortie` datetime DEFAULT NULL,
  PRIMARY KEY (`id_inventaire`),
  UNIQUE KEY `id_produit` (`id_produit`,`id_section`),
  KEY `id_section` (`id_section`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `inventaire`
--

INSERT INTO `inventaire` (`id_inventaire`, `id_produit`, `id_section`, `quantite`, `date_derniere_entree`, `date_derniere_sortie`) VALUES
(1, 1, 10, 3, '2025-03-23 19:32:46', NULL),
(2, 2, 3, 5, '2025-03-23 19:41:55', NULL),
(3, 6, 50, 25, '2025-03-26 19:20:49', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `mouvements`
--

DROP TABLE IF EXISTS `mouvements`;
CREATE TABLE IF NOT EXISTS `mouvements` (
  `id_mouvement` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) NOT NULL,
  `id_section` int(11) NOT NULL,
  `type_mouvement` enum('entrée','sortie','transfert') NOT NULL,
  `quantite` int(11) NOT NULL,
  `date_mouvement` datetime DEFAULT current_timestamp(),
  `utilisateur` varchar(50) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `id_section_destination` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_mouvement`),
  KEY `id_produit` (`id_produit`),
  KEY `id_section` (`id_section`),
  KEY `id_section_destination` (`id_section_destination`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `mouvements`
--

INSERT INTO `mouvements` (`id_mouvement`, `id_produit`, `id_section`, `type_mouvement`, `quantite`, `date_mouvement`, `utilisateur`, `commentaire`, `id_section_destination`) VALUES
(1, 1, 10, 'entrée', 3, '2025-03-23 20:32:46', 'Admin', 'Création initiale du produit', NULL),
(2, 2, 3, 'entrée', 5, '2025-03-23 20:41:55', 'Admin', 'Création initiale du produit', NULL),
(3, 6, 50, 'entrée', 25, '2025-03-26 20:20:49', 'Admin', 'Création initiale du produit', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

DROP TABLE IF EXISTS `produits`;
CREATE TABLE IF NOT EXISTS `produits` (
  `id_produit` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL,
  `nom_produit` varchar(100) NOT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `fournisseur` varchar(100) DEFAULT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT NULL,
  `seuil_alerte` int(11) DEFAULT 10,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_produit`),
  UNIQUE KEY `reference` (`reference`),
  KEY `id_categorie` (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_produit`, `reference`, `nom_produit`, `id_categorie`, `description`, `fournisseur`, `prix_unitaire`, `seuil_alerte`, `date_creation`, `date_modification`) VALUES
(1, 'essaie', 'test', 2, 'test', 'test', '0.00', 10, '2025-03-23 20:32:46', '2025-03-23 20:32:46'),
(2, 'test 2', 'test', 3, '', '', '0.00', 10, '2025-03-23 20:41:55', '2025-03-23 20:41:55'),
(3, 'test 5', 'test', 2, '', '', '0.00', 10, '2025-03-23 20:42:50', '2025-03-23 20:42:50'),
(4, 'Test pour l\'inventaire', 'test', 3, '', '', '0.00', 10, '2025-03-23 20:43:08', '2025-03-23 20:43:08'),
(5, 'test56', 'test', 4, '', '', '0.00', 10, '2025-03-23 20:49:05', '2025-03-23 20:49:05'),
(6, 'bonjoru', 'oui', 4, 'test', 'test', '0.00', 10, '2025-03-26 20:20:49', '2025-03-26 20:20:49');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id_reservation` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `date_demande` datetime DEFAULT current_timestamp(),
  `date_expiration` date DEFAULT NULL,
  `statut` enum('en_attente','approuvee','refusee','annulee','terminee') NOT NULL DEFAULT 'en_attente',
  `commentaire` text DEFAULT NULL,
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `motif` text DEFAULT NULL,
  PRIMARY KEY (`id_reservation`),
  KEY `id_produit` (`id_produit`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id_reservation`, `id_produit`, `id_utilisateur`, `quantite`, `date_demande`, `date_expiration`, `statut`, `commentaire`, `date_modification`, `motif`) VALUES
(1, 1, 1, 5, '2025-03-26 21:35:43', NULL, 'en_attente', 'En attente de validation', '2025-03-26 21:35:43', 'Projet A-23'),
(2, 2, 1, 3, '2025-03-26 21:35:43', NULL, 'approuvee', 'Approuvé par le responsable', '2025-03-26 21:35:43', 'Maintenance équipement'),
(3, 3, 2, 10, '2025-03-26 21:35:43', NULL, 'terminee', 'Réservation terminée le 25/03/2025', '2025-03-26 21:35:43', 'Remplacement composants'),
(4, 4, 3, 2, '2025-03-26 21:35:43', NULL, 'refusee', 'Stock insuffisant', '2025-03-26 21:35:43', 'Projet B-45'),
(5, 1, 2, 8, '2025-03-26 21:35:43', NULL, 'annulee', 'Annulée à la demande de l\'utilisateur', '2025-03-26 21:35:43', 'Projet annulé');

-- --------------------------------------------------------

--
-- Structure de la table `sections`
--

DROP TABLE IF EXISTS `sections`;
CREATE TABLE IF NOT EXISTS `sections` (
  `id_section` int(11) NOT NULL AUTO_INCREMENT,
  `id_etagere` int(11) NOT NULL,
  `numero_section` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `capacite_max` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_section`),
  UNIQUE KEY `id_etagere` (`id_etagere`,`numero_section`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `sections`
--

INSERT INTO `sections` (`id_section`, `id_etagere`, `numero_section`, `description`, `capacite_max`) VALUES
(1, 1, 1, 'Section gauche - Étage 0 - Câble', 30),
(2, 6, 1, 'Section gauche - Étage 1 - Câble', 30),
(3, 11, 1, 'Section gauche - Étage 2 - Câble', 30),
(4, 16, 1, 'Section gauche - Étage 3 - Câble', 30),
(5, 21, 1, 'Section gauche - Étage 4 - Câble', 30),
(6, 26, 1, 'Section gauche - Étage 5 - Câble', 30),
(7, 2, 1, 'Section gauche - Étage 0 - Foxboro', 30),
(8, 7, 1, 'Section gauche - Étage 1 - Foxboro', 30),
(9, 12, 1, 'Section gauche - Étage 2 - Foxboro', 30),
(10, 17, 1, 'Section gauche - Étage 3 - Foxboro', 30),
(11, 22, 1, 'Section gauche - Étage 4 - Foxboro', 30),
(12, 27, 1, 'Section gauche - Étage 5 - Foxboro', 30),
(13, 3, 1, 'Section gauche - Étage 0 - Rockwell', 30),
(14, 8, 1, 'Section gauche - Étage 1 - Rockwell', 30),
(15, 13, 1, 'Section gauche - Étage 2 - Rockwell', 30),
(16, 18, 1, 'Section gauche - Étage 3 - Rockwell', 30),
(17, 23, 1, 'Section gauche - Étage 4 - Rockwell', 30),
(18, 28, 1, 'Section gauche - Étage 5 - Rockwell', 30),
(19, 4, 1, 'Section gauche - Étage 0 - Informatique A', 30),
(20, 9, 1, 'Section gauche - Étage 1 - Informatique A', 30),
(21, 14, 1, 'Section gauche - Étage 2 - Informatique A', 30),
(22, 19, 1, 'Section gauche - Étage 3 - Informatique A', 30),
(23, 24, 1, 'Section gauche - Étage 4 - Informatique A', 30),
(24, 29, 1, 'Section gauche - Étage 5 - Informatique A', 30),
(25, 5, 1, 'Section gauche - Étage 0 - Informatique B', 30),
(26, 10, 1, 'Section gauche - Étage 1 - Informatique B', 30),
(27, 15, 1, 'Section gauche - Étage 2 - Informatique B', 30),
(28, 1, 2, 'Section milieu - Étage 0 - Câble', 30),
(29, 6, 2, 'Section milieu - Étage 1 - Câble', 30),
(30, 11, 2, 'Section milieu - Étage 2 - Câble', 30),
(31, 16, 2, 'Section milieu - Étage 3 - Câble', 30),
(32, 21, 2, 'Section milieu - Étage 4 - Câble', 30),
(33, 26, 2, 'Section milieu - Étage 5 - Câble', 30),
(34, 2, 2, 'Section milieu - Étage 0 - Foxboro', 30),
(35, 7, 2, 'Section milieu - Étage 1 - Foxboro', 30),
(36, 12, 2, 'Section milieu - Étage 2 - Foxboro', 30),
(37, 17, 2, 'Section milieu - Étage 3 - Foxboro', 30),
(38, 22, 2, 'Section milieu - Étage 4 - Foxboro', 30),
(39, 27, 2, 'Section milieu - Étage 5 - Foxboro', 30),
(40, 3, 2, 'Section milieu - Étage 0 - Rockwell', 30),
(41, 8, 2, 'Section milieu - Étage 1 - Rockwell', 30),
(42, 13, 2, 'Section milieu - Étage 2 - Rockwell', 30),
(43, 18, 2, 'Section milieu - Étage 3 - Rockwell', 30),
(44, 23, 2, 'Section milieu - Étage 4 - Rockwell', 30),
(45, 28, 2, 'Section milieu - Étage 5 - Rockwell', 30),
(46, 4, 2, 'Section milieu - Étage 0 - Informatique A', 30),
(47, 9, 2, 'Section milieu - Étage 1 - Informatique A', 30),
(48, 14, 2, 'Section milieu - Étage 2 - Informatique A', 30),
(49, 19, 2, 'Section milieu - Étage 3 - Informatique A', 30),
(50, 24, 2, 'Section milieu - Étage 4 - Informatique A', 30),
(51, 29, 2, 'Section milieu - Étage 5 - Informatique A', 30),
(52, 5, 2, 'Section milieu - Étage 0 - Informatique B', 30),
(53, 10, 2, 'Section milieu - Étage 1 - Informatique B', 30),
(54, 15, 2, 'Section milieu - Étage 2 - Informatique B', 30),
(55, 1, 3, 'Section droite - Étage 0 - Câble', 30),
(56, 6, 3, 'Section droite - Étage 1 - Câble', 30),
(57, 11, 3, 'Section droite - Étage 2 - Câble', 30),
(58, 16, 3, 'Section droite - Étage 3 - Câble', 30),
(59, 21, 3, 'Section droite - Étage 4 - Câble', 30),
(60, 26, 3, 'Section droite - Étage 5 - Câble', 30),
(61, 2, 3, 'Section droite - Étage 0 - Foxboro', 30),
(62, 7, 3, 'Section droite - Étage 1 - Foxboro', 30),
(63, 12, 3, 'Section droite - Étage 2 - Foxboro', 30),
(64, 17, 3, 'Section droite - Étage 3 - Foxboro', 30),
(65, 22, 3, 'Section droite - Étage 4 - Foxboro', 30),
(66, 27, 3, 'Section droite - Étage 5 - Foxboro', 30),
(67, 3, 3, 'Section droite - Étage 0 - Rockwell', 30),
(68, 8, 3, 'Section droite - Étage 1 - Rockwell', 30),
(69, 13, 3, 'Section droite - Étage 2 - Rockwell', 30),
(70, 18, 3, 'Section droite - Étage 3 - Rockwell', 30),
(71, 23, 3, 'Section droite - Étage 4 - Rockwell', 30),
(72, 28, 3, 'Section droite - Étage 5 - Rockwell', 30),
(73, 4, 3, 'Section droite - Étage 0 - Informatique A', 30),
(74, 9, 3, 'Section droite - Étage 1 - Informatique A', 30),
(75, 14, 3, 'Section droite - Étage 2 - Informatique A', 30),
(76, 19, 3, 'Section droite - Étage 3 - Informatique A', 30),
(77, 24, 3, 'Section droite - Étage 4 - Informatique A', 30),
(78, 29, 3, 'Section droite - Étage 5 - Informatique A', 30),
(79, 5, 3, 'Section droite - Étage 0 - Informatique B', 30),
(80, 10, 3, 'Section droite - Étage 1 - Informatique B', 30),
(81, 15, 3, 'Section droite - Étage 2 - Informatique B', 30),
(82, 20, 1, 'Section gauche - Étage 3 - Informatique B', 30),
(83, 25, 1, 'Section gauche - Étage 4 - Informatique B', 30),
(84, 30, 1, 'Section gauche - Étage 5 - Informatique B', 30),
(85, 20, 2, 'Section milieu - Étage 3 - Informatique B', 30),
(86, 25, 2, 'Section milieu - Étage 4 - Informatique B', 30),
(87, 30, 2, 'Section milieu - Étage 5 - Informatique B', 30),
(88, 20, 3, 'Section droite - Étage 3 - Informatique B', 30),
(89, 25, 3, 'Section droite - Étage 4 - Informatique B', 30),
(90, 30, 3, 'Section droite - Étage 5 - Informatique B', 30);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
  `nom_utilisateur` varchar(50) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','gestionnaire','lecture') NOT NULL DEFAULT 'lecture',
  `date_creation` datetime DEFAULT current_timestamp(),
  `derniere_connexion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `nom_utilisateur` (`nom_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom_utilisateur`, `mot_de_passe`, `nom`, `prenom`, `email`, `role`, `date_creation`, `derniere_connexion`) VALUES
(1, 'user1', '$2y$10$GlAH.qjJyS6VK3JMAvHRGO3LhFZFNrP3BCS8gG7TZ2FEEjWFl0v2W', 'Doe', 'John', 'john@example.com', 'lecture', '2025-03-26 21:35:13', NULL),
(2, 'user2', '$2y$10$GlAH.qjJyS6VK3JMAvHRGO3LhFZFNrP3BCS8gG7TZ2FEEjWFl0v2W', 'Smith', 'Jane', 'jane@example.com', 'lecture', '2025-03-26 21:35:13', NULL),
(3, 'user3', '$2y$10$GlAH.qjJyS6VK3JMAvHRGO3LhFZFNrP3BCS8gG7TZ2FEEjWFl0v2W', 'Brown', 'Bob', 'bob@example.com', 'lecture', '2025-03-26 21:35:13', NULL);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `alertes`
--
ALTER TABLE `alertes`
  ADD CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`);

--
-- Contraintes pour la table `etageres`
--
ALTER TABLE `etageres`
  ADD CONSTRAINT `etageres_ibfk_1` FOREIGN KEY (`id_armoire`) REFERENCES `armoires` (`id_armoire`) ON DELETE CASCADE;

--
-- Contraintes pour la table `historique_reservations`
--
ALTER TABLE `historique_reservations`
  ADD CONSTRAINT `historique_reservations_ibfk_1` FOREIGN KEY (`id_reservation`) REFERENCES `reservations` (`id_reservation`),
  ADD CONSTRAINT `historique_reservations_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `inventaire`
--
ALTER TABLE `inventaire`
  ADD CONSTRAINT `inventaire_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `inventaire_ibfk_2` FOREIGN KEY (`id_section`) REFERENCES `sections` (`id_section`);

--
-- Contraintes pour la table `mouvements`
--
ALTER TABLE `mouvements`
  ADD CONSTRAINT `mouvements_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `mouvements_ibfk_2` FOREIGN KEY (`id_section`) REFERENCES `sections` (`id_section`),
  ADD CONSTRAINT `mouvements_ibfk_3` FOREIGN KEY (`id_section_destination`) REFERENCES `sections` (`id_section`);

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`);

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`id_etagere`) REFERENCES `etageres` (`id_etagere`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
