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
    private const UPDATE_INTERVAL = 60; // Update prices every 60 seconds
    private const STORAGE_PATH = __DIR__ . '/../../storage'; // Path to storage directory
    private const LAST_UPDATE_FILE = self::STORAGE_PATH . '/last_update.txt';

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->currencyModel = new Currency($db);
    }

    // API Endpoint: Get list of all available cryptos
    public function getCryptoList() {
        AuthGuard::protect(); // Should be logged in to see market data

        $this->attemptPriceUpdate(); // Attempt to update prices based on interval

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

    // NEW: Method to attempt price update
    private function attemptPriceUpdate() {
        // Ensure storage directory exists and is writable
        if (!is_dir(self::STORAGE_PATH)) {
            if (!mkdir(self::STORAGE_PATH, 0775, true)) {
                 error_log("MarketController: Failed to create storage directory: " . self::STORAGE_PATH);
                 return; // Cannot proceed without storage
            }
        }
        if (!is_writable(self::STORAGE_PATH)) {
             error_log("MarketController: Storage directory not writable: " . self::STORAGE_PATH);
             // Attempt to make it writable (might not work depending on permissions)
             // chmod(self::STORAGE_PATH, 0775);
             // return; // Optionally return if still not writable
        }

        $lastUpdateTime = 0;
        if (file_exists(self::LAST_UPDATE_FILE)) {
            $lastUpdateTime = (int)file_get_contents(self::LAST_UPDATE_FILE);
        }

        $currentTime = time();

        if (($currentTime - $lastUpdateTime) > self::UPDATE_INTERVAL) {
            try {
                $allCurrencies = $this->currencyModel->findAll();
                $this->db->beginTransaction(); // Start transaction for multiple updates

                foreach ($allCurrencies as $currency) {
                    // Simulate price change
                    $currentPrice = (float)$currency['current_price_usd'];
                    $volatility = (float)$currency['base_volatility'];
                    $trend = (float)$currency['base_trend'];

                    // Simple random factor between -1 and 1
                    $randomInfluence = (mt_rand(-1000, 1000) / 1000.0);

                    // Calculate new price: current * (1 + trend) * (1 + volatility * random)
                    $newPrice = $currentPrice * (1 + $trend) * (1 + $volatility * $randomInfluence);
                    $newPrice = max(0.00001, $newPrice); // Don't let price be zero or negative

                    // Simulate change % fluctuation (simplified)
                    $currentChange = (float)$currency['change_24h_percent'];
                    // Make change fluctuate slightly based on the same random influence
                    $changeFluctuation = $randomInfluence * 5; // Adjust multiplier for sensitivity
                    $newChangePercent = $currentChange + $changeFluctuation;
                    // Keep change within a plausible range (e.g., -90% to +500%)
                    $newChangePercent = max(-90, min(500, $newChangePercent));

                    // Update DB
                    $this->currencyModel->updatePriceAndChange(
                        (int)$currency['id'],
                        $newPrice,
                        $newChangePercent
                    );
                }

                $this->db->commit(); // Commit all updates

                // Update timestamp file
                if (file_put_contents(self::LAST_UPDATE_FILE, $currentTime) === false) {
                    error_log("MarketController: Failed to write last update time to " . self::LAST_UPDATE_FILE);
                }
                 error_log("MarketController: Prices updated successfully at " . $currentTime); // Log success

            } catch (\PDOException $e) {
                $this->db->rollBack();
                error_log("MarketController: Failed to update prices: " . $e->getMessage());
            } catch (\Exception $e) {
                error_log("MarketController: General error during price update: " . $e->getMessage());
                 // Potentially rollback if DB transaction was started and commit didn't happen
                 if ($this->db->inTransaction()) {
                     $this->db->rollBack();
                 }
            }
        } else {
            // Optional: Log that update was skipped due to interval
             // error_log("MarketController: Price update skipped, interval not reached.");
        }
    }
}
?>