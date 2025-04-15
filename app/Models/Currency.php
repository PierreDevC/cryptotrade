<?php
// /cryptotrade/app/Models/Currency.php
namespace App\Models;

use PDO;

class Currency {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findAll() {
        $stmt = $this->db->query("SELECT * FROM currencies ORDER BY market_cap_usd DESC");
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM currencies WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

     public function findBySymbol($symbol) {
        $stmt = $this->db->prepare("SELECT * FROM currencies WHERE symbol = :symbol");
        $stmt->bindParam(':symbol', $symbol);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Optional: Update market data (used by simulator or external feed)
    public function updateMarketData($id, $data) {
         // Example: only updating price and change
         $sql = "UPDATE currencies SET
                    current_price_usd = :price,
                    change_24h_percent = :change
                 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':price', $data['current_price_usd']);
        $stmt->bindParam(':change', $data['change_24h_percent']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // NEW: Update price and change percentage for a currency
    public function updatePriceAndChange($id, $newPrice, $newChangePercent) {
         $sql = "UPDATE currencies SET
                    current_price_usd = :price,
                    change_24h_percent = :change,
                    last_price_update_timestamp = CURRENT_TIMESTAMP
                 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':price', $newPrice);
        $stmt->bindParam(':change', $newChangePercent);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // NEW: Create a new currency
    public function create($data) {
        $sql = "INSERT INTO currencies (name, symbol, current_price_usd, change_24h_percent, market_cap_usd, base_volatility, base_trend)
                VALUES (:name, :symbol, :price, :change, :market_cap, :volatility, :trend)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':symbol', $data['symbol']);
        $stmt->bindParam(':price', $data['current_price_usd']);
        $stmt->bindParam(':change', $data['change_24h_percent']);
        $stmt->bindParam(':market_cap', $data['market_cap_usd']);
        $stmt->bindParam(':volatility', $data['base_volatility']);
        $stmt->bindParam(':trend', $data['base_trend']);

        return $stmt->execute();
    }

    // NEW: Update an existing currency by ID
    public function update($id, $data) {
        $sql = "UPDATE currencies SET
                    name = :name,
                    symbol = :symbol,
                    current_price_usd = :price,
                    change_24h_percent = :change,
                    market_cap_usd = :market_cap,
                    base_volatility = :volatility,
                    base_trend = :trend
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':symbol', $data['symbol']);
        $stmt->bindParam(':price', $data['current_price_usd']);
        $stmt->bindParam(':change', $data['change_24h_percent']);
        $stmt->bindParam(':market_cap', $data['market_cap_usd']);
        $stmt->bindParam(':volatility', $data['base_volatility']);
        $stmt->bindParam(':trend', $data['base_trend']);

        return $stmt->execute();
    }

    // NEW: Delete a currency by ID
    public function deleteById($id) {
        // TODO: Consider implications - what happens to user wallets holding this? Transactions?
        // For now, simple delete.
        // You might want to prevent deletion if wallets hold this currency,
        // or set holdings to zero, or archive the currency instead.
        $sql = "DELETE FROM currencies WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>