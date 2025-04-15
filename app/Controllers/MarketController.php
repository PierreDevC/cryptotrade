<?php
// /cryptotrade/app/Controllers/MarketController.php
namespace App\Controllers;

use App\Core\Session;
use App\Utils\AuthGuard;
use App\Models\Currency;
use App\Utils\MarketSimulator;
use PDO;

class MarketController {
    private $db;
    private $currencyModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->currencyModel = new Currency($db);
    }

    // API Endpoint: Get list of all available cryptos
    public function getCryptoList() {
        AuthGuard::protect(); // Should be logged in to see market data

        $currencies = $this->currencyModel->findAll();

        // Format data for the table in dashboard.html
        $formattedList = [];
        $rank = 1;
        foreach ($currencies as $currency) {
             $formattedList[] = [
                 'id' => (int)$currency['id'],
                 'rank' => $rank++,
                 'name' => $currency['name'],
                 'symbol' => $currency['symbol'],
                 'price_usd' => number_format((float)$currency['current_price_usd'], 2, '.', ','),
                 'price_usd_raw' => (float)$currency['current_price_usd'],
                 'change_24h' => number_format((float)$currency['change_24h_percent'], 2) . '%',
                 'change_24h_raw' => (float)$currency['change_24h_percent'],
                 'market_cap' => $this->formatMarketCap((float)$currency['market_cap_usd']),
                 'market_cap_raw' => (float)$currency['market_cap_usd'],
                 'image_url' => $this->getCryptoImageUrl($currency['symbol'])
             ];
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $formattedList]);
        exit;
    }

    // API Endpoint: Get historical chart data for a specific crypto
    public function getCryptoChartData($params) {
        AuthGuard::protect();
        $currencyId = $params['id'] ?? null;

        if (!$currencyId) {
             header('Content-Type: application/json');
             http_response_code(400); // Bad Request
             echo json_encode(['success' => false, 'message' => 'Currency ID is required.']);
             exit;
        }

        $currency = $this->currencyModel->findById($currencyId);

        if (!$currency) {
            header('Content-Type: application/json');
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Currency not found.']);
            exit;
        }

        // Generate simulated data using the simulator
        $chartData = MarketSimulator::generateHistoricalData(
            $currency['current_price_usd'],
            $currency['base_volatility'],
            $currency['base_trend'],
            30 // Number of periods (e.g., days)
        );

         // Prepare data for ChartJS
        $response = [
            'labels' => $chartData['labels'],
            'datasets' => [[
                'label' => 'Price (' . $currency['symbol'] . ' - USD)',
                'data' => $chartData['prices'],
                 'borderColor' => '#2d3a36',
                 'backgroundColor' => 'rgba(45, 58, 54, 0.1)',
                 'tension' => 0.3,
                 'fill' => true
            ]]
        ];


        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $response]);
        exit;
    }

     // Helper to format large market cap numbers
    private function formatMarketCap($number) {
        if ($number >= 1e12) {
            return '$' . number_format($number / 1e12, 1) . 'T';
        } elseif ($number >= 1e9) {
            return '$' . number_format($number / 1e9, 1) . 'B';
        } elseif ($number >= 1e6) {
             return '$' . number_format($number / 1e6, 1) . 'M';
        } elseif ($number >= 1e3) {
             return '$' . number_format($number / 1e3, 1) . 'K';
         }
        return '$' . number_format($number, 0);
    }

     // Re-use the image URL helper
    private function getCryptoImageUrl($symbol) {
         // Simple mapping - expand as needed
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