-- Ajouter 10 Cryptomonnaies populaires
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
('Polkadot', 'DOT', 6.50, 1.50, 900000000000.00, 0.0360, 0.0009),