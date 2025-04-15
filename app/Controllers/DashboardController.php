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

        // Calculate Portfolio Value and Format Holdings
        $portfolioValueCryptoCAD = 0; // Value of crypto assets only
        $holdingsFormatted = [];
        $usdToCadRate = 1.35; // TODO: Get from config or API

        foreach ($holdingsRaw as $holding) {
            $currentValueUSD = $holding['quantity'] * $holding['current_price_usd'];
            $currentValueCAD = $currentValueUSD * $usdToCadRate;
            $portfolioValueCryptoCAD += $currentValueCAD;

             $currencyDetails = $this->currencyModel->findById($holding['currency_id']);
             $profit_loss_percent = $currencyDetails ? $currencyDetails['change_24h_percent'] : 0;

            $holdingsFormatted[] = [
                'name' => $holding['name'],
                'symbol' => $holding['symbol'],
                'quantity' => number_format($holding['quantity'], 8, '.', ''),
                'current_price_usd' => number_format($holding['current_price_usd'], 2, '.', ','),
                'current_price_cad' => number_format($holding['current_price_usd'] * $usdToCadRate, 2, '.', ','),
                'total_value_cad' => number_format($currentValueCAD, 2, '.', ','),
                'profit_loss_percent' => $profit_loss_percent,
                'image_url' => $this->getCryptoImageUrl($holding['symbol'])
            ];
        }

        // Generate dummy portfolio chart data (Replace with real logic if needed)
        $portfolioChartData = [
             'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
             'values' => [
                 round($user['balance_cad'] * 0.8 + $portfolioValueCryptoCAD * 0.7), // Simulating past values
                 round($user['balance_cad'] * 0.85 + $portfolioValueCryptoCAD * 0.75),
                 round($user['balance_cad'] * 0.95 + $portfolioValueCryptoCAD * 0.85),
                 round($user['balance_cad'] * 0.9 + $portfolioValueCryptoCAD * 0.8),
                 round($user['balance_cad'] * 1.05 + $portfolioValueCryptoCAD * 0.95),
                 round($user['balance_cad'] + $portfolioValueCryptoCAD) // End with current total value (liquid + crypto)
             ]
        ];

         // Calculate total account value (Liquid CAD + Crypto Value in CAD)
         $totalAccountValueCAD = $user['balance_cad'] + $portfolioValueCryptoCAD;

         $data = [
             'account' => [
                 'type' => $user['is_admin'] ? 'Admin' : 'Premium',
                 'status' => ucfirst($user['status']),
                 'last_login' => $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never',
                 'balance_cad_raw' => $user['balance_cad'], // Liquid balance
                 'balance_cad_formatted' => number_format($user['balance_cad'], 2, '.', ',') . '$ CAD', // Liquid balance formatted
                 'weekly_gain_percent' => 3.2 // Placeholder
             ],
             'holdings' => $holdingsFormatted,
             'portfolioValueCAD' => number_format($totalAccountValueCAD, 2, '.', ','), // Total value formatted
             'portfolioChart' => $portfolioChartData
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