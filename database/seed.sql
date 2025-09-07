-- Ajouter 15 Cryptomonnaies populaires
-- Les prix, variations, market caps sont indicatifs et non en temps réel.
-- Les volatilités et tendances sont des estimations pour la simulation.
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