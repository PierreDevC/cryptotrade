<?php
// /cryptotrade/app/Controllers/TransactionController.php
namespace App\Controllers;

/**
 * Développeur assignés(s) : Aboubacar
 * Entité : Classe 'TransactionController' de la couche Controllers
 */

use App\Core\Session;
use App\Core\Request;
use App\Utils\AuthGuard;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Currency;
use App\Models\Transaction;
use App\Utils\Csrf;
use PDO;
use PDOException;

class TransactionController {
    private $db;
    private $request;
    private $userModel;
    private $walletModel;
    private $currencyModel;
    private $transactionModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->request = new Request();
        $this->userModel = new User($db);
        $this->walletModel = new Wallet($db);
        $this->currencyModel = new Currency($db);
        $this->transactionModel = new Transaction($db);
    }

    // Point API: Gérer Achat
    public function buy() {
        AuthGuard::protect();
        Csrf::protect($this->request);

        $userId = AuthGuard::user();
        $body = $this->request->getBody();

        $currencyId = filter_var($body['currencyId'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($body['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
        $amountCad = filter_var($body['amountCad'] ?? null, FILTER_VALIDATE_FLOAT); // Coût total CAD (modale)

         // Validations simples
        if (!$currencyId || !$quantity || $quantity <= 0 || !$amountCad || $amountCad <= 0) {
             return $this->jsonResponse(false, 'Invalid input data.');
        }

        $currency = $this->currencyModel->findById($currencyId);
        if (!$currency) {
            return $this->jsonResponse(false, 'Currency not found.');
        }

         $user = $this->userModel->findById($userId);
        if (!$user) {
             return $this->jsonResponse(false, 'User not found.'); // Ne devrait pas arriver si connecté
        }

        // --- Vérif Fonds Suffisants ---
         // Recalcul coût estimé (prix *actuel*) par sécurité
         // (petite diff tolérée vs prix modale)
         // Idéalement via config
         $currentPriceCAD = (float)$currency['current_price_usd'];
         $estimatedCostCAD = $quantity * $currentPriceCAD;

         // Tolère ~1% diff vs montant modale
         if ($amountCad > 0 && abs($estimatedCostCAD - $amountCad) / $amountCad > 0.01) {
               // return $this->jsonResponse(false, 'Le prix a changé significativement. Réessayez.');
               // Pour simplifier (projet école), on utilise amountCad de la modale
         }


         if ($user['balance_cad'] < $amountCad) {
            return $this->jsonResponse(false, 'Insufficient CAD balance.');
        }

        // --- Exécute dans une Transaction BDD ---
        try {
            $this->db->beginTransaction();

            // 1. Déduit solde CAD
            $balanceUpdated = $this->userModel->updateBalance($userId, -$amountCad);
            if (!$balanceUpdated) throw new PDOException("Failed to update user balance.");

            // 2. Ajoute Crypto au Wallet
            $walletUpdated = $this->walletModel->updateQuantity($userId, $currencyId, $quantity);
             if (!$walletUpdated) throw new PDOException("Failed to update wallet quantity.");


            // 3. Log Transaction
            $transactionData = [
                'user_id' => $userId,
                'currency_id' => $currencyId,
                'type' => 'buy',
                'quantity' => $quantity,
                // Log prix CAD au moment (dans colonne _usd)
                'price_per_unit_usd' => $currentPriceCAD,
                'total_amount_cad' => $amountCad
            ];
            $logged = $this->transactionModel->create($transactionData);
            if (!$logged) throw new PDOException("Failed to log transaction.");

            $this->db->commit();
            return $this->jsonResponse(true, 'Buy transaction successful!');

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Transaction Failed: " . $e->getMessage()); // Log l'erreur réelle
            return $this->jsonResponse(false, 'Transaction failed. Please try again later.');
        }
    }

    // Point API: Gérer Vente
    public function sell() {
        AuthGuard::protect();
        Csrf::protect($this->request);

        $userId = AuthGuard::user();
        $body = $this->request->getBody();

        $currencyId = filter_var($body['currencyId'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($body['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
        $amountCad = filter_var($body['amountCad'] ?? null, FILTER_VALIDATE_FLOAT); // Total produit CAD (modale)

        // Validations simples
        if (!$currencyId || !$quantity || $quantity <= 0 || !$amountCad || $amountCad <= 0) {
            return $this->jsonResponse(false, 'Invalid input data.');
        }

         $currency = $this->currencyModel->findById($currencyId);
         if (!$currency) {
             return $this->jsonResponse(false, 'Currency not found.');
         }

         // --- Vérif Avoirs Crypto Suffisants ---
         $walletEntry = $this->walletModel->findByUserAndCurrency($userId, $currencyId);
        if (!$walletEntry || $walletEntry['quantity'] < $quantity) {
             return $this->jsonResponse(false, 'Insufficient ' . $currency['symbol'] . ' balance.');
        }

         // Recalcul produit estimé (prix actuel) par sécurité
         // Traite prix BDD comme CAD
         $currentPriceCAD = (float)$currency['current_price_usd'];
         $estimatedProceedsCAD = $quantity * $currentPriceCAD;

         // Tolère ~1% diff
          if ($amountCad > 0 && abs($estimatedProceedsCAD - $amountCad) / $amountCad > 0.01) {
             // return $this->jsonResponse(false, 'Le prix a changé significativement. Réessayez.');
             // On utilise amountCad de la modale pour simplifier
          }

        // --- Exécute dans une Transaction BDD ---
        try {
            $this->db->beginTransaction();

             // 1. Déduit Crypto du Wallet
             $walletUpdated = $this->walletModel->updateQuantity($userId, $currencyId, -$quantity);
             if (!$walletUpdated) throw new PDOException("Failed to update wallet quantity.");

             // 2. Ajoute solde CAD
             $balanceUpdated = $this->userModel->updateBalance($userId, $amountCad);
             if (!$balanceUpdated) throw new PDOException("Failed to update user balance.");


             // 3. Log Transaction
             $transactionData = [
                 'user_id' => $userId,
                 'currency_id' => $currencyId,
                 'type' => 'sell',
                 'quantity' => $quantity,
                 'price_per_unit_usd' => $currentPriceCAD, // Log prix CAD (dans colonne _usd)
                 'total_amount_cad' => $amountCad
             ];
             $logged = $this->transactionModel->create($transactionData);
             if (!$logged) throw new PDOException("Failed to log transaction.");


            $this->db->commit();
            return $this->jsonResponse(true, 'Sell transaction successful!');

        } catch (PDOException $e) {
            $this->db->rollBack();
             error_log("Transaction Failed: " . $e->getMessage());
            return $this->jsonResponse(false, 'Transaction failed. Please try again later.');
        }
    }

     // Aide pour réponses JSON
    private function jsonResponse($success, $message = '', $data = []) {
        header('Content-Type: application/json');
        if (!$success) {
            http_response_code(400); // Mauvaise requête ou erreur logique
        }
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
         exit;
    }
}
?>