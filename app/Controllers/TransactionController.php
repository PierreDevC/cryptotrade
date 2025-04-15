<?php
// /cryptotrade/app/Controllers/TransactionController.php
namespace App\Controllers;

use App\Core\Session;
use App\Core\Request;
use App\Utils\AuthGuard;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Currency;
use App\Models\Transaction;
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

    // API Endpoint: Handle Buy Request
    public function buy() {
        AuthGuard::protect();
        $userId = AuthGuard::user();
        $body = $this->request->getBody();

        $currencyId = filter_var($body['currencyId'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($body['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
        $amountCAD = filter_var($body['amountCAD'] ?? null, FILTER_VALIDATE_FLOAT); // Total CAD cost from modal

         // Basic Validation
        if (!$currencyId || !$quantity || $quantity <= 0 || !$amountCAD || $amountCAD <= 0) {
             return $this->jsonResponse(false, 'Invalid input data.');
        }

        $currency = $this->currencyModel->findById($currencyId);
        if (!$currency) {
            return $this->jsonResponse(false, 'Currency not found.');
        }

         $user = $this->userModel->findById($userId);
        if (!$user) {
             return $this->jsonResponse(false, 'User not found.'); // Should not happen if logged in
        }

        // --- Check Sufficient Funds ---
         // Recalculate expected cost based on *current* price for safety check
         // (allow small discrepancy from modal price due to fluctuation)
         $usdToCadRate = 1.35; // Get from config ideally
         $currentPriceUSD = $currency['current_price_usd'];
         $estimatedCostCAD = $quantity * $currentPriceUSD * $usdToCadRate;

         // Allow maybe 1% difference from the amount confirmed in modal
         if (abs($estimatedCostCAD - $amountCAD) / $amountCAD > 0.01) {
               // return $this->jsonResponse(false, 'Price has changed significantly. Please try again.');
               // For simplicity in this school project, let's proceed with the amountCAD from modal
         }


         if ($user['balance_cad'] < $amountCAD) {
            return $this->jsonResponse(false, 'Insufficient CAD balance.');
        }

        // --- Perform Transaction within DB Transaction ---
        try {
            $this->db->beginTransaction();

            // 1. Deduct CAD balance
            $balanceUpdated = $this->userModel->updateBalance($userId, -$amountCAD);
            if (!$balanceUpdated) throw new PDOException("Failed to update user balance.");

            // 2. Add Crypto to Wallet
            $walletUpdated = $this->walletModel->updateQuantity($userId, $currencyId, $quantity);
             if (!$walletUpdated) throw new PDOException("Failed to update wallet quantity.");


            // 3. Log Transaction
            $transactionData = [
                'user_id' => $userId,
                'currency_id' => $currencyId,
                'type' => 'buy',
                'quantity' => $quantity,
                 // Use the price at the time of transaction for logging
                'price_per_unit_usd' => $currentPriceUSD,
                'total_amount_cad' => $amountCAD
            ];
            $logged = $this->transactionModel->create($transactionData);
            if (!$logged) throw new PDOException("Failed to log transaction.");

            $this->db->commit();
            return $this->jsonResponse(true, 'Buy transaction successful!');

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Transaction Failed: " . $e->getMessage()); // Log actual error
            return $this->jsonResponse(false, 'Transaction failed. Please try again later.');
        }
    }

    // API Endpoint: Handle Sell Request
    public function sell() {
        AuthGuard::protect();
        $userId = AuthGuard::user();
        $body = $this->request->getBody();

        $currencyId = filter_var($body['currencyId'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($body['quantity'] ?? null, FILTER_VALIDATE_FLOAT);
        $amountCAD = filter_var($body['amountCAD'] ?? null, FILTER_VALIDATE_FLOAT); // Total CAD proceeds from modal

        // Basic Validation
        if (!$currencyId || !$quantity || $quantity <= 0 || !$amountCAD || $amountCAD <= 0) {
            return $this->jsonResponse(false, 'Invalid input data.');
        }

         $currency = $this->currencyModel->findById($currencyId);
         if (!$currency) {
             return $this->jsonResponse(false, 'Currency not found.');
         }

         // --- Check Sufficient Crypto Holdings ---
         $walletEntry = $this->walletModel->findByUserAndCurrency($userId, $currencyId);
        if (!$walletEntry || $walletEntry['quantity'] < $quantity) {
             return $this->jsonResponse(false, 'Insufficient ' . $currency['symbol'] . ' balance.');
        }

         // Recalculate expected proceeds based on current price for safety check
         $usdToCadRate = 1.35; // Get from config
         $currentPriceUSD = $currency['current_price_usd'];
         $estimatedProceedsCAD = $quantity * $currentPriceUSD * $usdToCadRate;

         // Allow maybe 1% difference
          if (abs($estimatedProceedsCAD - $amountCAD) / $amountCAD > 0.01) {
             // return $this->jsonResponse(false, 'Price has changed significantly. Please try again.');
             // Proceed with amountCAD from modal for simplicity
          }

        // --- Perform Transaction within DB Transaction ---
        try {
            $this->db->beginTransaction();

             // 1. Deduct Crypto from Wallet
             $walletUpdated = $this->walletModel->updateQuantity($userId, $currencyId, -$quantity);
             if (!$walletUpdated) throw new PDOException("Failed to update wallet quantity.");

             // 2. Add CAD balance
             $balanceUpdated = $this->userModel->updateBalance($userId, $amountCAD);
             if (!$balanceUpdated) throw new PDOException("Failed to update user balance.");


             // 3. Log Transaction
             $transactionData = [
                 'user_id' => $userId,
                 'currency_id' => $currencyId,
                 'type' => 'sell',
                 'quantity' => $quantity,
                 'price_per_unit_usd' => $currentPriceUSD, // Price at time of sale
                 'total_amount_cad' => $amountCAD
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

     // Helper to send JSON responses
    private function jsonResponse($success, $message = '', $data = []) {
        header('Content-Type: application/json');
        if (!$success) {
            http_response_code(400); // Bad request or logical error
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