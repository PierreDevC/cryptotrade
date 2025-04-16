<?php
// /cryptotrade/app/Controllers/DashboardController.php
namespace App\Controllers;

use App\Core\Session;
use App\Utils\AuthGuard;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Currency;
use App\Models\Transaction;
use PDO;

class DashboardController {
    private $db;
    private $userModel;
    private $walletModel;
    private $currencyModel;
    private $transactionModel;


    public function __construct(PDO $db) {
        $this->db = $db;
        $this->userModel = new User($db);
        $this->walletModel = new Wallet($db);
        $this->currencyModel = new Currency($db);
        $this->transactionModel = new Transaction($db);

    }

    // Serve the main dashboard page
    public function index() {
        AuthGuard::protect(); // Ensure user is logged in
        // *** CHANGED to require dashboard.php ***
        require 'dashboard.php'; // Serve the view file
        exit;
    }

    // API Endpoint: Get data for the dashboard via AJAX
    public function getDashboardData() {
        AuthGuard::protect(); // Ensure user is logged in
        $userId = AuthGuard::user();

        $user = $this->userModel->findById($userId);
        $holdingsRaw = $this->walletModel->findByUserWithDetails($userId);
        $recentTransactionsRaw = $this->transactionModel->findByUser($userId, 5); // Fetch last 5 transactions

        // Calculate Portfolio Value, Changes, and Format Holdings
        $portfolioValueCryptoCAD = 0; // Value of crypto assets only
        $portfolioChange24hCAD = 0;   // Change in crypto assets value over 24h
        $holdingsFormatted = [];
        // TODO: Get from config or API (e.g., App\Config::get('rates.usd_to_cad'))

        foreach ($holdingsRaw as $holding) {
            // Treat DB price as CAD now
            $currentPriceCAD = (float)$holding['current_price_usd']; // Treat the DB value as CAD
            $currentValueCAD = $holding['quantity'] * $currentPriceCAD;
            $portfolioValueCryptoCAD += $currentValueCAD;

            // Fetch full currency details to get change %
            $currencyDetails = $this->currencyModel->findById($holding['currency_id']);
            $change24hPercent = $currencyDetails ? $currencyDetails['change_24h_percent'] : 0;

            // Calculate the change in CAD value for this specific holding over 24h
            // Price 24h ago = current_price / (1 + change_24h_percent / 100)
            // Calculate previous price assuming current price is CAD
            $price24hAgoCAD = $change24hPercent != -100 ? $currentPriceCAD / (1 + $change24hPercent / 100) : 0;
            $value24hAgoCAD = $holding['quantity'] * $price24hAgoCAD;
            $changeValueCAD = $currentValueCAD - $value24hAgoCAD;
            $portfolioChange24hCAD += $changeValueCAD;

            $holdingsFormatted[] = [
                'name' => $holding['name'],
                'symbol' => $holding['symbol'],
                'currency_id' => (int)$holding['currency_id'],
                'quantity_raw' => (float)$holding['quantity'],
                'quantity' => number_format($holding['quantity'], 8, '.', ''), // Raw quantity might be useful too
                // Add raw price for JS and format the CAD price
                'current_price_cad_raw' => $currentPriceCAD, // Send raw CAD price
                'current_price_cad_formatted' => number_format($currentPriceCAD, 2, '.', ',') . '$ CAD',
                'total_value_cad_formatted' => '$' . number_format($currentValueCAD, 2, '.', ','),
                'total_value_cad_raw' => $currentValueCAD,
                'currency_change_24h_percent' => $change24hPercent, // Renamed for clarity
                'currency_change_24h_value_cad_formatted' => ($changeValueCAD >= 0 ? '+' : '') . number_format($changeValueCAD, 2, '.', ',') . '$', // Change for this holding
                // TODO: Centralize this logic (e.g., new Utility class or Trait, or move to Currency model)
                'image_url' => $this->getCryptoImageUrl($holding['symbol'])
            ];
        }

        // Calculate overall portfolio 24h change percentage
        $portfolioValueCrypto24hAgo = $portfolioValueCryptoCAD - $portfolioChange24hCAD;
        $portfolioChange24hPercent = ($portfolioValueCrypto24hAgo > 0)
            ? ($portfolioChange24hCAD / $portfolioValueCrypto24hAgo) * 100
            : 0; // Avoid division by zero if portfolio was empty 24h ago


        // Generate dummy portfolio chart data (Replace with real logic if needed)
        // This simulation now ends closer to the *total* account value
        $totalAccountValueCAD = $user['balance_cad'] + $portfolioValueCryptoCAD;
        $simulatedPreviousTotal = $totalAccountValueCAD * 0.9; // Just a guess for previous points
        $portfolioChartData = [
             'labels' => ['T-5', 'T-4', 'T-3', 'T-2', 'T-1', 'Maintenant'], // More generic labels
             'values' => [
                 round($simulatedPreviousTotal * 0.85),
                 round($simulatedPreviousTotal * 0.90),
                 round($simulatedPreviousTotal * 0.98),
                 round($simulatedPreviousTotal * 0.95),
                 round($simulatedPreviousTotal * 1.02),
                 round($totalAccountValueCAD) // End with current total value
             ]
        ];

        // Format recent transactions
        $recentTransactionsFormatted = [];
        foreach($recentTransactionsRaw as $tx) {
             $recentTransactionsFormatted[] = [
                  'type' => ucfirst($tx['type']),
                  'symbol' => $tx['currency_symbol'],
                  'quantity' => number_format($tx['quantity'], 6, '.', ''),
                  'total_amount_cad' => number_format($tx['total_amount_cad'], 2, '.', ','),
                  // Add price per unit (assuming it's stored as USD but represents CAD now)
                  'price_per_unit_cad' => (float)$tx['price_per_unit_usd'],
                  'timestamp' => date('Y-m-d H:i', strtotime($tx['timestamp']))
             ];
        }


         $data = [
             'account' => [
                 'type' => $user['is_admin'] ? 'Admin' : 'Standard', // Changed 'Premium' to 'Standard'
                 'status' => ucfirst($user['status']),
                 'last_login' => $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Jamais',
                 'balance_cad_raw' => $user['balance_cad'],
                 'balance_cad_formatted' => number_format($user['balance_cad'], 2, '.', ',') . '$ CAD',
                 // 'weekly_gain_percent' => 3.2 // Placeholder - consider removing if not implemented
                 'is_admin' => (bool)$user['is_admin'], // NEW: Expose admin status
                 'fullname' => $user['fullname'], // NEW: Expose fullname
                 'email' => $user['email']     // NEW: Expose email
             ],
             'portfolio' => [
                 'total_value_cad_raw' => $totalAccountValueCAD,
                 'total_value_cad_formatted' => number_format($totalAccountValueCAD, 2, '.', ',') . '$ CAD',
                 'crypto_value_cad_raw' => $portfolioValueCryptoCAD,
                 'crypto_value_cad_formatted' => number_format($portfolioValueCryptoCAD, 2, '.', ',') . '$ CAD',
                 'change_24h_cad_formatted' => ($portfolioChange24hCAD >= 0 ? '+' : '') . number_format($portfolioChange24hCAD, 2, '.', ','),
                 'change_24h_percent_formatted' => ($portfolioChange24hPercent >= 0 ? '+' : '') . number_format($portfolioChange24hPercent, 2, '.', ',') . '%',
                 // TODO: Implement actual P/L calculation based on transaction history
                 // 'total_profit_loss_cad_formatted' => '+$1,234.56',
                 // 'total_profit_loss_percent_formatted' => '+15.67%',
             ],
             'holdings' => $holdingsFormatted,
             'portfolioChart' => $portfolioChartData,
             'recent_transactions' => $recentTransactionsFormatted // Added recent transactions
         ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // API Endpoint: Get data for the Allocation Stats Tab
    public function getAllocationData() {
        // Instantiate Transaction model here if not already done in constructor
        // (Assuming $this->transactionModel is available via constructor)
        if (!$this->transactionModel) { 
             $this->transactionModel = new Transaction($this->db);
        }

        AuthGuard::protect(); // Ensure user is logged in
        $userId = AuthGuard::user();

        $user = $this->userModel->findById($userId);
        $holdingsRaw = $this->walletModel->findByUserWithDetails($userId);

        $allocationData = [];
        $totalCryptoValueCAD = 0;
        $totalCostBasisCAD = 0; // NEW: Track total cost basis

        // Calculate total value first
        foreach ($holdingsRaw as $holding) {
            $currentPriceCAD = (float)$holding['current_price_usd']; // Treat DB price as CAD
            $currentValueCAD = $holding['quantity'] * $currentPriceCAD;
            $totalCryptoValueCAD += $currentValueCAD;
        }

        // Calculate percentage for each holding
        foreach ($holdingsRaw as $holding) {
            $currentPriceCAD = (float)$holding['current_price_usd'];
            $currentValueCAD = $holding['quantity'] * $currentPriceCAD;
            $percentage = ($totalCryptoValueCAD > 0) ? ($currentValueCAD / $totalCryptoValueCAD) * 100 : 0;

            // Calculate Cost Basis and Unrealized P/L for this holding
            $costBasisCAD = $this->transactionModel->calculateCurrentFIFOBaseCost($userId, (int)$holding['currency_id']);
            $unrealizedPlCAD = $currentValueCAD - $costBasisCAD;
            $totalCostBasisCAD += $costBasisCAD; // Add to total cost basis

            $allocationData[] = [
                'symbol' => $holding['symbol'],
                'name' => $holding['name'], // Include name for potential tooltips
                'value_cad' => $currentValueCAD,
                'percentage' => round($percentage, 2),
                'cost_basis_cad' => round($costBasisCAD, 2), // Add cost basis
                'unrealized_pl_cad' => round($unrealizedPlCAD, 2) // Add unrealized P/L
            ];
        }

        // Calculate total unrealized P/L
        $totalUnrealizedPlCAD = $totalCryptoValueCAD - $totalCostBasisCAD;

        $data = [
            'allocation' => $allocationData,
            'total_crypto_value_cad' => round($totalCryptoValueCAD, 2),
            'user_balance_cad' => round($user['balance_cad'], 2),
            'total_unrealized_pl_cad' => round($totalUnrealizedPlCAD, 2) // Add total unrealized P/L
        ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

     // Helper to get image URL (like in index.html table)
     private function getCryptoImageUrl($symbol) {
        $map = [
            'BTC' => 'https://assets.coingecko.com/coins/images/1/thumb/bitcoin.png',
            'ETH' => 'https://assets.coingecko.com/coins/images/279/thumb/ethereum.png',
            'SOL' => 'https://assets.coingecko.com/coins/images/4128/thumb/solana.png',
             'SIM' => 'https://via.placeholder.com/20/09f/fff.png?text=S',
             'SYM' => 'https://via.placeholder.com/20/777/fff.png?text=SYM',
             'USDT' => 'https://assets.coingecko.com/coins/images/325/thumb/Tether.png',
             'BNB' => 'https://assets.coingecko.com/coins/images/825/thumb/bnb-icon2_2x.png',
             'XRP' => 'https://assets.coingecko.com/coins/images/44/thumb/xrp-symbol-white-128.png',
             'ADA' => 'https://assets.coingecko.com/coins/images/975/thumb/cardano.png',
             'DOGE' => 'https://assets.coingecko.com/coins/images/5/thumb/dogecoin.png',
             'DOT' => 'https://assets.coingecko.com/coins/images/12171/thumb/polkadot.png',
             'LINK' => 'https://assets.coingecko.com/coins/images/877/thumb/chainlink-new-logo.png',
        ];
        return $map[strtoupper($symbol)] ?? 'https://via.placeholder.com/20';
    }
}
?>