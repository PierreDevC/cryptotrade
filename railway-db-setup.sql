-- CryptoTrade Database Setup for Railway MySQL
-- Run this in Railway MySQL console to create tables and add initial data

-- Disable foreign key checks for setup
SET FOREIGN_KEY_CHECKS=0;

-- Create users table
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

-- Create currencies table
DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  `current_price_usd` DECIMAL(20, 8) NOT NULL,
  `change_24h_percent` DECIMAL(10, 4) NOT NULL,
  `market_cap_usd` DECIMAL(25, 2) NOT NULL,
  `base_volatility` DECIMAL(10, 8) NOT NULL,
  `base_trend` DECIMAL(10, 8) NOT NULL,
  `last_price_update_timestamp` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `symbol_unique` (`symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create wallets table
DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `currency_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(25, 8) NOT NULL DEFAULT 0.00000000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_currency_unique` (`user_id`, `currency_id`),
  KEY `wallet_user_fk` (`user_id`),
  KEY `wallet_currency_fk` (`currency_id`),
  CONSTRAINT `wallet_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wallet_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create transactions table
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `currency_id` INT UNSIGNED NOT NULL,
  `type` ENUM('buy', 'sell') NOT NULL,
  `quantity` DECIMAL(25, 8) NOT NULL,
  `price_per_unit_usd` DECIMAL(20, 8) NOT NULL,
  `total_amount_cad` DECIMAL(20, 2) NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaction_user_fk` (`user_id`),
  KEY `transaction_currency_fk` (`currency_id`),
  CONSTRAINT `transaction_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert cryptocurrencies (15 popular cryptocurrencies)
INSERT INTO `currencies` (`name`, `symbol`, `current_price_usd`, `change_24h_percent`, `market_cap_usd`, `base_volatility`, `base_trend`) VALUES
('Bitcoin', 'BTC', 67500.00, 1.85, 1330000000000.00, 0.0150, 0.0008),
('Ethereum', 'ETH', 3550.00, -0.50, 426000000000.00, 0.0210, 0.0012),
('Solana', 'SOL', 150.00, 4.20, 69000000000.00, 0.0380, 0.0015),
('BNB', 'BNB', 610.00, 0.90, 90000000000.00, 0.0250, 0.0010),
('XRP', 'XRP', 0.5200, -1.10, 28000000000.00, 0.0300, 0.0005),
('Cardano', 'ADA', 0.4500, 2.10, 16000000000.00, 0.0320, 0.0007),
('Dogecoin', 'DOGE', 0.1600, 5.50, 23000000000.00, 0.0500, 0.0003),
('Polkadot', 'DOT', 6.50, 1.50, 9000000000.00, 0.0360, 0.0009),
('Chainlink', 'LINK', 15.80, 0.75, 8500000000.00, 0.0280, 0.0006),
('Tether', 'USDT', 1.0000, 0.01, 83000000000.00, 0.0050, 0.0001),
('Avalanche', 'AVAX', 28.50, 3.20, 11000000000.00, 0.0420, 0.0011),
('Polygon', 'MATIC', 0.8900, -2.15, 8200000000.00, 0.0380, 0.0004),
('Litecoin', 'LTC', 85.40, 1.30, 6300000000.00, 0.0290, 0.0007),
('Uniswap', 'UNI', 7.20, 2.80, 4300000000.00, 0.0350, 0.0008),
('Cosmos', 'ATOM', 9.80, -1.50, 3800000000.00, 0.0330, 0.0005);

-- Insert test users (password is 'password123' for all)
INSERT INTO `users` (`fullname`, `email`, `password_hash`, `balance_cad`, `is_admin`, `status`) VALUES
('Admin User', 'admin@cryptotrade.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 100000.00, TRUE, 'active'),
('Test User', 'user@cryptotrade.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10000.00, FALSE, 'active'),
('Demo Trader', 'demo@cryptotrade.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5000.00, FALSE, 'active');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- Verify setup
SELECT 'Setup Complete!' as status;
SELECT COUNT(*) as tables_created FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT COUNT(*) as currencies_added FROM currencies;
SELECT COUNT(*) as users_added FROM users;
