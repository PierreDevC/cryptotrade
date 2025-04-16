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

    /**
     * Calculates the FIFO cost basis for the currently held quantity of a specific currency.
     *
     * @param int $userId The user's ID.
     * @param int $currencyId The currency's ID.
     * @return float The total cost basis in CAD for the currently held assets.
     */
    public function calculateCurrentFIFOBaseCost(int $userId, int $currencyId): float {
        // 1. Fetch all BUY transactions for this user/currency, ordered oldest first
        $buySql = "SELECT quantity, total_amount_cad FROM transactions
                   WHERE user_id = :user_id AND currency_id = :currency_id AND type = 'buy'
                   ORDER BY timestamp ASC";
        $buyStmt = $this->db->prepare($buySql);
        $buyStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $buyStmt->bindParam(':currency_id', $currencyId, PDO::PARAM_INT);
        $buyStmt->execute();
        $buyTransactions = $buyStmt->fetchAll();

        // 2. Fetch all SELL transactions for this user/currency, ordered oldest first
        $sellSql = "SELECT quantity FROM transactions
                    WHERE user_id = :user_id AND currency_id = :currency_id AND type = 'sell'
                    ORDER BY timestamp ASC";
        $sellStmt = $this->db->prepare($sellSql);
        $sellStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $sellStmt->bindParam(':currency_id', $currencyId, PDO::PARAM_INT);
        $sellStmt->execute();
        $sellTransactions = $sellStmt->fetchAll();

        $remainingBuys = [];
        foreach ($buyTransactions as $buy) {
            // Store remaining quantity and cost per unit for each buy lot
            $remainingBuys[] = [
                'remaining_quantity' => (float)$buy['quantity'],
                'cost_per_unit' => (float)$buy['quantity'] > 0 ? (float)$buy['total_amount_cad'] / (float)$buy['quantity'] : 0
            ];
        }

        // 3. Simulate sells using FIFO
        foreach ($sellTransactions as $sell) {
            $sellQuantity = (float)$sell['quantity'];
            foreach ($remainingBuys as $key => &$buyLot) { // Use reference to modify array
                if ($sellQuantity <= 0) break; // This sell is fully accounted for

                if ($buyLot['remaining_quantity'] > 0) {
                    $deductAmount = min($sellQuantity, $buyLot['remaining_quantity']);
                    $buyLot['remaining_quantity'] -= $deductAmount;
                    $sellQuantity -= $deductAmount;
                }
            }
            unset($buyLot); // Unset reference after loop
        }

        // 4. Calculate cost basis from remaining buy lots
        $totalCostBasis = 0.0;
        foreach ($remainingBuys as $buyLot) {
            if ($buyLot['remaining_quantity'] > 0) {
                $totalCostBasis += $buyLot['remaining_quantity'] * $buyLot['cost_per_unit'];
            }
        }

        return $totalCostBasis;
    }
}
?>