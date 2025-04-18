<?php
// /cryptotrade/app/Models/Currency.php
namespace App\Models;

/**
 * Développeur assignés(s) : Moses
 * Entité : Classe 'Currency' de la couche Models
 */

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

    // Pour mettre à jour les données du marché (si besoin).
    public function updateMarketData($id, $data) {
         // Je change juste le prix et la variation ici.
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

    // Je mets à jour prix et % de change.
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

    // Je crée une devise.
    public function create($data) {
        $sql = "INSERT INTO currencies (name, symbol, current_price_usd, change_24h_percent, market_cap_usd, base_volatility, base_trend)
                VALUES (:name, :symbol, :price, :change, :market_cap, :volatility, :trend)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':symbol', $data['symbol']);
        $stmt->bindParam(':price', $data['current_price_cad']); // Note: Assuming CAD price insertion is intended here despite USD column name
        $stmt->bindParam(':change', $data['change_24h_percent']);
        $stmt->bindParam(':market_cap', $data['market_cap_cad']); // Note: Assuming CAD market cap insertion is intended here despite USD column name
        $stmt->bindParam(':volatility', $data['base_volatility']);
        $stmt->bindParam(':trend', $data['base_trend']);

        return $stmt->execute();
    }

    // Je mets à jour une devise (via ID).
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
        $stmt->bindParam(':price', $data['current_price_cad']); // Note: Assuming CAD price update is intended here despite USD column name
        $stmt->bindParam(':change', $data['change_24h_percent']);
        $stmt->bindParam(':market_cap', $data['market_cap_cad']); // Note: Assuming CAD market cap update is intended here despite USD column name
        $stmt->bindParam(':volatility', $data['base_volatility']);
        $stmt->bindParam(':trend', $data['base_trend']);

        return $stmt->execute();
    }

    // Je supprime une devise (via ID).
    public function deleteById($id) {
        // TODO : Attention si je supprime ! Ça impacte les portefeuilles/transactions.
        // Pour l'instant, suppression simple, mais on pourrait bloquer/archiver/mettre à zéro.
        $sql = "DELETE FROM currencies WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>