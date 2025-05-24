-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 24 mai 2025 à 12:44
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `admisoft_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `tbl_administrators`
--

DROP TABLE IF EXISTS `tbl_administrators`;
CREATE TABLE IF NOT EXISTS `tbl_administrators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `photo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `role` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin',
  `role_inside` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tbl_administrators`
--

INSERT INTO `tbl_administrators` (`id`, `full_name`, `email`, `phone`, `password`, `photo`, `role`, `role_inside`, `status`, `created_at`) VALUES
(1, 'administrator', 'admin@mail.com', '7777777777', '$argon2id$v=19$m=65536,t=4,p=1$bk5VQllsVU1TVnBGODFaSw$IKTJBF0oVPbElMoherEVsmLXrOmSDVG72YmNC027ahY', 'user-1.png', 'super_admin', NULL, 'active', '2025-04-22 13:53:47'),
(2, 'jozeph kabongo', 'jokab@mail.com', '4444444444', '$argon2id$v=19$m=65536,t=4,p=1$bk5VQllsVU1TVnBGODFaSw$IKTJBF0oVPbElMoherEVsmLXrOmSDVG72YmNC027ahY', 'user-13.jpg', 'admin', NULL, 'active', '2025-04-22 13:53:47');

-- --------------------------------------------------------

--
-- Structure de la table `tbl_categories`
--

DROP TABLE IF EXISTS `tbl_categories`;
CREATE TABLE IF NOT EXISTS `tbl_categories` (
  `cat_id` int NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`cat_id`),
  UNIQUE KEY `cat_name` (`cat_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tbl_categories`
--

INSERT INTO `tbl_categories` (`cat_id`, `cat_name`) VALUES
(1, 'Accessoires Informatiques'),
(11, 'Audio-Visuel'),
(4, 'Batteries et Alimentation'),
(6, 'Connectivité et connectique'),
(7, 'Ergonomie et Bureau'),
(3, 'Imprimantes et Consommables'),
(12, 'Jeu'),
(5, 'Logiciels et Licences'),
(9, 'Sécurité'),
(10, 'Service'),
(2, 'Stockage et Mémoire'),
(8, 'Téléphonie et Accessoires connectés');

-- --------------------------------------------------------

--
-- Structure de la table `tbl_colors`
--

DROP TABLE IF EXISTS `tbl_colors`;
CREATE TABLE IF NOT EXISTS `tbl_colors` (
  `color_id` int NOT NULL AUTO_INCREMENT,
  `color_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`color_id`),
  UNIQUE KEY `color_name` (`color_name`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `tbl_colors`
--

INSERT INTO `tbl_colors` (`color_id`, `color_name`) VALUES
(1, 'Blanc'),
(3, 'Bleue'),
(4, 'Jaune'),
(2, 'Noir'),
(7, 'Orange'),
(6, 'Rouge'),
(5, 'Vert');

-- --------------------------------------------------------

--
-- Structure de la table `tbl_customers`
--

DROP TABLE IF EXISTS `tbl_customers`;
CREATE TABLE IF NOT EXISTS `tbl_customers` (
  `cust_id` int NOT NULL AUTO_INCREMENT,
  `cust_first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cust_last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cust_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cust_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cust_phone_verified` tinyint(1) NOT NULL DEFAULT '0',
  `cust_email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `cust_token` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `cust_password` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `cust_status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'inactive',
  `cust_role` enum('customer','seller') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'customer',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cust_id`),
  UNIQUE KEY `cust_email` (`cust_email`),
  UNIQUE KEY `cust_phone` (`cust_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_customer_addresses`
--

DROP TABLE IF EXISTS `tbl_customer_addresses`;
CREATE TABLE IF NOT EXISTS `tbl_customer_addresses` (
  `address_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `address_type` enum('domicile','company','facturation','') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `plot_num` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `street` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `quarter` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `commune` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `city` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `country` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`address_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_hastags`
--

DROP TABLE IF EXISTS `tbl_hastags`;
CREATE TABLE IF NOT EXISTS `tbl_hastags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_hastag_posts`
--

DROP TABLE IF EXISTS `tbl_hastag_posts`;
CREATE TABLE IF NOT EXISTS `tbl_hastag_posts` (
  `hashtag_id` int NOT NULL,
  `post_id` int NOT NULL,
  KEY `hashtag_id` (`hashtag_id`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_messages`
--

DROP TABLE IF EXISTS `tbl_messages`;
CREATE TABLE IF NOT EXISTS `tbl_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_orders`
--

DROP TABLE IF EXISTS `tbl_orders`;
CREATE TABLE IF NOT EXISTS `tbl_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_num` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `customer_id` int NOT NULL,
  `total` decimal(10,0) NOT NULL,
  `status` enum('pending','processing','delivered','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `status_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_num` (`order_num`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_order_histories`
--

DROP TABLE IF EXISTS `tbl_order_histories`;
CREATE TABLE IF NOT EXISTS `tbl_order_histories` (
  `or_hi_id` int NOT NULL AUTO_INCREMENT,
  `order_num` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`or_hi_id`),
  KEY `order_num` (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_order_items`
--

DROP TABLE IF EXISTS `tbl_order_items`;
CREATE TABLE IF NOT EXISTS `tbl_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,0) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_payments`
--

DROP TABLE IF EXISTS `tbl_payments`;
CREATE TABLE IF NOT EXISTS `tbl_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','paid','failed','cancelled','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_posts`
--

DROP TABLE IF EXISTS `tbl_posts`;
CREATE TABLE IF NOT EXISTS `tbl_posts` (
  `post_id` int NOT NULL AUTO_INCREMENT,
  `author_id` int NOT NULL,
  `post_title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `post_subject` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `post_content` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `post_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `post_views` int NOT NULL DEFAULT '1',
  `post_likes` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_products`
--

DROP TABLE IF EXISTS `tbl_products`;
CREATE TABLE IF NOT EXISTS `tbl_products` (
  `p_id` int NOT NULL AUTO_INCREMENT,
  `cat_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `p_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `p_price` decimal(10,0) NOT NULL,
  `p_qty` int NOT NULL,
  `p_treshold` int NOT NULL DEFAULT '3',
  `p_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `p_description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `p_sku` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `p_feature` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `p_last_restock` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`p_id`),
  UNIQUE KEY `p_name` (`p_name`),
  KEY `cat_id` (`cat_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_product_color`
--

DROP TABLE IF EXISTS `tbl_product_color`;
CREATE TABLE IF NOT EXISTS `tbl_product_color` (
  `id` int NOT NULL AUTO_INCREMENT,
  `color_id` int NOT NULL,
  `product_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `color_id` (`color_id`),
  KEY `p_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_rating`
--

DROP TABLE IF EXISTS `tbl_rating`;
CREATE TABLE IF NOT EXISTS `tbl_rating` (
  `rt_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `cust_id` int NOT NULL,
  `comment` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `rating` int NOT NULL,
  PRIMARY KEY (`rt_id`),
  KEY `p_id` (`product_id`),
  KEY `cust_id` (`cust_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tbl_services`
--

DROP TABLE IF EXISTS `tbl_services`;
CREATE TABLE IF NOT EXISTS `tbl_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `base_price` decimal(10,0) NOT NULL,
  `image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `tbl_customer_addresses`
--
ALTER TABLE `tbl_customer_addresses`
  ADD CONSTRAINT `tbl_customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`cust_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_hastag_posts`
--
ALTER TABLE `tbl_hastag_posts`
  ADD CONSTRAINT `tbl_hastag_posts_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `tbl_posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_hastag_posts_ibfk_2` FOREIGN KEY (`hashtag_id`) REFERENCES `tbl_hastags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`cust_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_order_histories`
--
ALTER TABLE `tbl_order_histories`
  ADD CONSTRAINT `tbl_order_histories_ibfk_1` FOREIGN KEY (`order_num`) REFERENCES `tbl_orders` (`order_num`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_order_items`
--
ALTER TABLE `tbl_order_items`
  ADD CONSTRAINT `tbl_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`p_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_payments`
--
ALTER TABLE `tbl_payments`
  ADD CONSTRAINT `tbl_payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_posts`
--
ALTER TABLE `tbl_posts`
  ADD CONSTRAINT `tbl_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `tbl_administrators` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD CONSTRAINT `tbl_products_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `tbl_categories` (`cat_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `tbl_customers` (`cust_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_product_color`
--
ALTER TABLE `tbl_product_color`
  ADD CONSTRAINT `tbl_product_color_ibfk_1` FOREIGN KEY (`color_id`) REFERENCES `tbl_colors` (`color_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_product_color_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`p_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tbl_rating`
--
ALTER TABLE `tbl_rating`
  ADD CONSTRAINT `tbl_rating_ibfk_1` FOREIGN KEY (`cust_id`) REFERENCES `tbl_customers` (`cust_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_rating_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`p_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
