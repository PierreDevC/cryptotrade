<?php
// /cryptotrade/app/Models/Transaction.php
namespace App\Models;

/**
 * Développeur assignés(s) : Aboubacar
 * Entité : Classe 'Transaction' de la couche Models
 */

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
        $stmt->bindParam(':type', $data['type']); // 'achat' ou 'vente'
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':price_per_unit_usd', $data['price_per_unit_usd']);
        $stmt->bindParam(':total_amount_cad', $data['total_amount_cad']);

        return $stmt->execute();
    }

    // Je cherche les transactions d'un user (pour l'historique, par ex.)
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

    // Je cherche TOUTES les transactions d'un user
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

      // Optionnel: Récupérer les données pour le graphique de perf du portefeuille
    public function getPortfolioValueHistory($userId) {
         // C'est compliqué ça. Faut sommer la valeur des actifs à différents moments.
         // Pour simplifier, on pourrait juste retourner les dernières transactions.
         // Ou utiliser les données Wallet/Currency.
         // Je retourne un truc vide pour l'instant.
        return ['labels' => [], 'values' => []];
    }

    /**
     * Je calcule le coût de base FIFO pour la quantité actuelle d'une devise.
     *
     * @param int $userId ID de l'utilisateur.
     * @param int $currencyId ID de la devise.
     * @return float Le coût de base total en CAD pour les actifs détenus.
     */
    public function calculateCurrentFIFOBaseCost(int $userId, int $currencyId): float {
        // 1. Je prends toutes les transactions d'ACHAT (les plus vieilles d'abord)
        $buySql = "SELECT quantity, total_amount_cad FROM transactions
                   WHERE user_id = :user_id AND currency_id = :currency_id AND type = 'buy'
                   ORDER BY timestamp ASC";
        $buyStmt = $this->db->prepare($buySql);
        $buyStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $buyStmt->bindParam(':currency_id', $currencyId, PDO::PARAM_INT);
        $buyStmt->execute();
        $buyTransactions = $buyStmt->fetchAll();

        // 2. Je prends toutes les transactions de VENTE (les plus vieilles d'abord)
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
            // Je stocke la quantité restante et le coût par unité pour chaque lot d'achat
            $remainingBuys[] = [
                'remaining_quantity' => (float)$buy['quantity'],
                'cost_per_unit' => (float)$buy['quantity'] > 0 ? (float)$buy['total_amount_cad'] / (float)$buy['quantity'] : 0
            ];
        }

        // 3. Je simule les ventes en FIFO
        foreach ($sellTransactions as $sell) {
            $sellQuantity = (float)$sell['quantity'];
            foreach ($remainingBuys as $key => &$buyLot) { // J'utilise une référence pour modifier le tableau
                if ($sellQuantity <= 0) break; // Cette vente est complètement prise en compte

                if ($buyLot['remaining_quantity'] > 0) {
                    $deductAmount = min($sellQuantity, $buyLot['remaining_quantity']);
                    $buyLot['remaining_quantity'] -= $deductAmount;
                    $sellQuantity -= $deductAmount;
                }
            }
            unset($buyLot); // Je supprime la référence après la boucle
        }

        // 4. Je calcule le coût de base à partir des lots d'achat restants
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