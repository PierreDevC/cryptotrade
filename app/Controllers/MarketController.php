<?php
// /cryptotrade/app/Controllers/MarketController.php
namespace App\Controllers;

/**
 * Développeur assignés(s) : Moses
 * Entité : Classe 'MarketController' de la couche Controllers
 */

use App\Core\Session;
use App\Utils\AuthGuard;
use App\Models\Currency;
use App\Utils\MarketSimulator;
use PDO;

class MarketController {
    private $db;
    private $currencyModel;
    private const UPDATE_INTERVAL = 60; // Mise à jour prix: 60s
    private const STORAGE_PATH = __DIR__ . '/../../storage'; // Dossier stockage
    private const LAST_UPDATE_FILE = self::STORAGE_PATH . '/last_update.txt';

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->currencyModel = new Currency($db);
    }

    // Point API: Liste des cryptos
    public function getCryptoList() {
        AuthGuard::protect(); // Doit être connecté

        $this->attemptPriceUpdate(); // Mise à jour prix (si intervalle ok)

        $currencies = $this->currencyModel->findAll();

        // Formatage pour tableau
        $formattedList = [];
        $rank = 1;
        foreach ($currencies as $currency) {
             $formattedList[] = [
                 'id' => (int)$currency['id'],
                 'rank' => $rank++,
                 'name' => $currency['name'],
                 'symbol' => $currency['symbol'],
                 'price_cad_raw' => (float)$currency['current_price_usd'],
                 'price_cad_formatted' => number_format((float)$currency['current_price_usd'], 2, '.', ',') . '$ CAD',
                 'change_24h' => number_format((float)$currency['change_24h_percent'], 2) . '%',
                 'change_24h_raw' => (float)$currency['change_24h_percent'],
                 'market_cap' => $this->formatMarketCap((float)$currency['market_cap_usd']),
                 'market_cap_cad_raw' => (float)$currency['market_cap_usd'],
                 'image_url' => $this->getCryptoImageUrl($currency['symbol'])
             ];
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $formattedList]);
        exit;
    }

    // Point API: Données graphiques crypto
    public function getCryptoChartData($params) {
        AuthGuard::protect();
        $currencyId = $params['id'] ?? null;

        if (!$currencyId) {
             header('Content-Type: application/json');
             http_response_code(400); // Mauvaise requête
             echo json_encode(['success' => false, 'message' => 'Currency ID is required.']);
             exit;
        }

        $currency = $this->currencyModel->findById($currencyId);

        if (!$currency) {
            header('Content-Type: application/json');
            http_response_code(404); // Non trouvé
            echo json_encode(['success' => false, 'message' => 'Currency not found.']);
            exit;
        }

        // Génération données simulées
        $chartData = MarketSimulator::generateHistoricalData(
            $currency['current_price_usd'],
            $currency['base_volatility'],
            $currency['base_trend'],
            30 // Nombre de périodes (ex: jours)
        );

         // Préparation pour ChartJS
        $response = [
            'labels' => $chartData['labels'],
            'datasets' => [[
                'label' => 'Price (' . $currency['symbol'] . ' - CAD)',
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

     // Formatage market cap
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

     // Réutilisation aide image URL
    private function getCryptoImageUrl($symbol) {
         // Mapping simple
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

    // NOUVEAU : Tentative mise à jour prix
    private function attemptPriceUpdate() {
        // Vérif dossier stockage (existe/écrit)
        if (!is_dir(self::STORAGE_PATH)) {
            if (!mkdir(self::STORAGE_PATH, 0775, true)) {
                 error_log("MarketController: Failed to create storage directory: " . self::STORAGE_PATH);
                 return; // Impossible de continuer sans stockage
            }
        }
        if (!is_writable(self::STORAGE_PATH)) {
             error_log("MarketController: Storage directory not writable: " . self::STORAGE_PATH);
             // Tentative chmod (peut échouer)
             // chmod(self::STORAGE_PATH, 0775);
             // Option : retourner si toujours pas accessible
        }

        $lastUpdateTime = 0;
        if (file_exists(self::LAST_UPDATE_FILE)) {
            $lastUpdateTime = (int)file_get_contents(self::LAST_UPDATE_FILE);
        }

        $currentTime = time();

        if (($currentTime - $lastUpdateTime) > self::UPDATE_INTERVAL) {
            try {
                $allCurrencies = $this->currencyModel->findAll();
                $this->db->beginTransaction(); // Je démarre une transaction

                foreach ($allCurrencies as $currency) {
                    // Je simule le changement de prix
                    $currentPrice = (float)$currency['current_price_usd'];
                    $volatility = (float)$currency['base_volatility'];
                    $trend = (float)$currency['base_trend'];

                    // Facteur aléatoire simple (-1 à 1)
                    $randomInfluence = (mt_rand(-1000, 1000) / 1000.0);

                    // Calcul nouveau prix : actuel * (1+tendance) * (1+volatilité*aléatoire)
                    $newPrice = $currentPrice * (1 + $trend) * (1 + $volatility * $randomInfluence);
                    $newPrice = max(0.00001, $newPrice); // Prix > 0

                    // Simulation fluctuation % (simple)
                    $currentChange = (float)$currency['change_24h_percent'];
                    // Le changement fluctue légèrement
                    $changeFluctuation = $randomInfluence * 5; // Ajuster multiplicateur (sensibilité)
                    $newChangePercent = $currentChange + $changeFluctuation;
                    // Changement entre -90% et +500%
                    $newChangePercent = max(-90, min(500, $newChangePercent));

                    // Mise à jour BDD
                    $this->currencyModel->updatePriceAndChange(
                        (int)$currency['id'],
                        $newPrice,
                        $newChangePercent
                    );
                }

                $this->db->commit(); // Je valide toutes les mises à jour

                // Je mets à jour le fichier timestamp
                if (@file_put_contents(self::LAST_UPDATE_FILE, $currentTime) === false) {
                    error_log("MarketController: Failed to write last update time to " . self::LAST_UPDATE_FILE);
                }
                 error_log("MarketController: Prices updated successfully at " . $currentTime); // Log succès

            } catch (\PDOException $e) {
                $this->db->rollBack();
                error_log("MarketController: Failed to update prices: " . $e->getMessage());
            } catch (\Exception $e) {
                error_log("MarketController: General error during price update: " . $e->getMessage());
                 // Rollback potentiel si transaction BDD non commitée
                 if ($this->db->inTransaction()) {
                     $this->db->rollBack();
                 }
            }
        } else {
            // Optionnel : Log si màj sautée (intervalle)
             // error_log("MarketController: Price update skipped, interval not reached.");
        }
    }
}
?>