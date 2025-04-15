<?php
// /cryptotrade/app/Models/Transaction.php
namespace App\Models;

use PDO;

class Transaction {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create($data) {
         $sql = "INSERT INTO transactions (user_id, currency_id, type, quantity, price_per_unit_usd, total_amount_cad)
                 VALUES (:user_id, :currency_id, :type, :quantity, :price_per_unit_usd, :total_amount_cad)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':currency_id', $data['currency_id'], PDO::PARAM_INT);
        $stmt->bindParam(':type', $data['type']); // 'buy' or 'sell'
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':price_per_unit_usd', $data['price_per_unit_usd']);
        $stmt->bindParam(':total_amount_cad', $data['total_amount_cad']);

        return $stmt->execute();
    }

    // Find transactions for a user (e.g., for history page)
    public function findByUser($userId, $limit = 50) {
         $sql = "SELECT t.*, c.symbol as currency_symbol
                 FROM transactions t
                 JOIN currencies c ON t.currency_id = c.id
                 WHERE t.user_id = :user_id
                 ORDER BY t.timestamp DESC
                 LIMIT :limit";
         $stmt = $this->db->prepare($sql);
         $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
         $stmt->execute();
         return $stmt->fetchAll();
    }

    // NEW: Find ALL transactions for a user
    public function findAllByUser($userId) {
        $sql = "SELECT t.*, c.symbol as currency_symbol, c.name as currency_name
                FROM transactions t
                JOIN currencies c ON t.currency_id = c.id
                WHERE t.user_id = :user_id
                ORDER BY t.timestamp DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

      // Optional: Get data for portfolio performance chart
    public function getPortfolioValueHistory($userId) {
         // This is complex. It requires summing asset values at different points in time.
         // A simplified approach might just return recent transactions.
         // A more accurate way involves periodic snapshots or complex calculations.
         // For this project, maybe return the last N transactions or just use Wallet/Currency data.
         // Returning empty for now as it's non-trivial.
        return ['labels' => [], 'values' => []];
    }
}
?>