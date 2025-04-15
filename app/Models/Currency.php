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
}
?>