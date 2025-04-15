-- /cryptotrade/database/seed.sql
-- This file contains initial data for testing, including users with pre-hashed passwords.

USE cryptotrade_db; -- remove commenting when ready to seed

-- Add Users
-- The following hashes correspond to:
-- user@test.com -> password
-- admin@test.com -> adminpass
INSERT INTO users (fullname, email, password_hash, balance_cad, is_admin, status) VALUES
('Test User', 'user@test.com', '$2y$10$Y4x.f/Y3Y0V9q.C/8V55.uJc3l/oQn.Xk8G3uFv.j.G7P.9/Q.m3.', 10000.00, FALSE, 'active'),
('Admin User', 'admin@test.com', '$2y$10$H9g.W/R7Z2B3p.D/7X44.oKl4m/pRr.Yl9H4vGz.k.F8R.0/S.n4.', 50000.00, TRUE, 'active');

-- Add Currencies
INSERT INTO currencies (name, symbol, current_price_usd, change_24h_percent, market_cap_usd, base_volatility, base_trend) VALUES
('Bitcoin', 'BTC', 65000.00, 2.50, 1200000000000.00, 0.0150, 0.0010), -- 1.5% vol, 0.1% trend
('Ethereum', 'ETH', 3500.00, -1.20, 420000000000.00, 0.0200, 0.0015), -- 2.0% vol, 0.15% trend
('SimuCoin', 'SIM', 10.50, 5.10, 100000000.00, 0.0300, -0.0005);    -- 3.0% vol, -0.05% trend (for testing)

-- Give Test User some initial holdings
-- Assumes the first user inserted gets id=1, second gets id=2 etc.
-- Assumes BTC currency gets id=1, ETH gets id=2, SIM gets id=3 etc.
-- Check your actual IDs in phpMyAdmin if needed after running schema.sql
INSERT INTO wallets (user_id, currency_id, quantity) VALUES
(1, 1, 0.10000000),  -- User 1 (user@test.com) gets 0.1 BTC
(1, 2, 1.50000000);  -- User 1 (user@test.com) gets 1.5 ETH

-- Give Admin User some holdings
INSERT INTO wallets (user_id, currency_id, quantity) VALUES
(2, 1, 0.50000000), -- User 2 (admin@test.com) gets 0.5 BTC
(2, 3, 1000.00000000); -- User 2 (admin@test.com) gets 1000 SIM