-- /cryptotrade/database/schema.sql
-- Ce fichier définit la structure de la base de données cryptotrade_db.

-- Supprime la base de données si elle existe (pour repartir de zéro facilement)
-- ATTENTION : Cette ligne supprime toutes les données existantes dans cryptotrade_db !
DROP DATABASE IF EXISTS `cryptotrade_db`;

-- Crée la base de données
CREATE DATABASE `cryptotrade_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Sélectionne la base de données pour les commandes suivantes
USE `cryptotrade_db`;

-- Désactiver les vérifications de clés étrangères temporairement pour la création
SET FOREIGN_KEY_CHECKS=0;

--
-- Structure de la table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `balance_cad` DECIMAL(15, 2) DEFAULT 10000.00,
  `is_admin` BOOLEAN DEFAULT FALSE,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `currencies`
--
DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  `current_price_usd` DECIMAL(20, 8) NOT NULL, -- Note: Traité comme CAD dans le backend
  `change_24h_percent` DECIMAL(10, 4) NOT NULL,
  `market_cap_usd` DECIMAL(25, 2) NOT NULL, -- Note: Traité comme CAD dans le backend
  `base_volatility` DECIMAL(10, 8) NOT NULL, -- Ex: 0.0150
  `base_trend` DECIMAL(10, 8) NOT NULL,      -- Ex: 0.0010
  `last_price_update_timestamp` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `symbol_unique` (`symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `wallets`
--
DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `currency_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(25, 8) NOT NULL DEFAULT 0.00000000, -- Précision pour les cryptos
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_currency_unique` (`user_id`, `currency_id`),
  KEY `wallet_user_fk` (`user_id`),
  KEY `wallet_currency_fk` (`currency_id`),
  CONSTRAINT `wallet_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE, -- Supprime le portefeuille si l'utilisateur est supprimé
  CONSTRAINT `wallet_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE RESTRICT -- Empêche la suppression d'une devise si elle est dans un portefeuille
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `transactions`
--
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `currency_id` INT UNSIGNED NOT NULL,
  `type` ENUM('buy', 'sell') NOT NULL,
  `quantity` DECIMAL(25, 8) NOT NULL,
  `price_per_unit_usd` DECIMAL(20, 8) NOT NULL, -- Prix unitaire au moment de la transaction (Traité comme CAD)
  `total_amount_cad` DECIMAL(20, 2) NOT NULL, -- Valeur totale en CAD de la transaction
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaction_user_fk` (`user_id`),
  KEY `transaction_currency_fk` (`currency_id`),
  CONSTRAINT `transaction_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) -- On ne supprime pas ici car on veut garder l'historique même si la devise est retirée (DELETE RESTRICT est par défaut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS=1;