<?php
// /cryptotrade/app/Controllers/DashboardController.php
namespace App\Controllers;

/**
 * Développeur(s) assigné(s) : Pierre
 * Entité : Classe 'DashboardController' de la couche Controllers
 */

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

    // Je sers la page principale du tableau de bord
    public function index() {
        AuthGuard::protect(); // Je m'assure que l'utilisateur est connecté
        // *** CHANGÉ pour require dashboard.php ***
        require 'dashboard.php'; // Je sers le fichier de vue
        exit;
    }

    // Point d'API : J'obtiens les données pour le tableau de bord via AJAX
    public function getDashboardData() {
        AuthGuard::protect(); // Je m'assure que l'utilisateur est connecté
        $userId = AuthGuard::user();

        $user = $this->userModel->findById($userId);
        $holdingsRaw = $this->walletModel->findByUserWithDetails($userId);
        $recentTransactionsRaw = $this->transactionModel->findByUser($userId, 5); // Je récupère les 5 dernières transactions

        // Je calcule la valeur du portefeuille, les changements et je formate les avoirs
        $portfolioValueCryptoCAD = 0; // Valeur des actifs crypto uniquement
        $portfolioChange24hCAD = 0;   // Changement de la valeur des actifs crypto sur 24h
        $holdingsFormatted = [];
        // TODO : À récupérer depuis la config ou une API (par ex., App\Config::get('rates.usd_to_cad'))

        foreach ($holdingsRaw as $holding) {
            // Je traite le prix de la BDD comme étant en CAD maintenant
            $currentPriceCAD = (float)$holding['current_price_usd']; // Je traite la valeur de la BDD comme du CAD
            $currentValueCAD = $holding['quantity'] * $currentPriceCAD;
            $portfolioValueCryptoCAD += $currentValueCAD;

            // Je récupère les détails complets de la devise pour obtenir le % de changement
            $currencyDetails = $this->currencyModel->findById($holding['currency_id']);
            $change24hPercent = $currencyDetails ? $currencyDetails['change_24h_percent'] : 0;

            // Je calcule le changement de valeur en CAD pour cet avoir spécifique sur 24h
            // Prix il y a 24h = prix_actuel / (1 + changement_24h_percent / 100)
            // Je calcule le prix précédent en supposant que le prix actuel est en CAD
            $price24hAgoCAD = $change24hPercent != -100 ? $currentPriceCAD / (1 + $change24hPercent / 100) : 0;
            $value24hAgoCAD = $holding['quantity'] * $price24hAgoCAD;
            $changeValueCAD = $currentValueCAD - $value24hAgoCAD;
            $portfolioChange24hCAD += $changeValueCAD;

            $holdingsFormatted[] = [
                'name' => $holding['name'],
                'symbol' => $holding['symbol'],
                'currency_id' => (int)$holding['currency_id'],
                'quantity_raw' => (float)$holding['quantity'],
                'quantity' => number_format($holding['quantity'], 8, '.', ''), // La quantité brute pourrait être utile aussi
                // J'ajoute le prix brut pour JS et je formate le prix en CAD
                'current_price_cad_raw' => $currentPriceCAD, // J'envoie le prix brut en CAD
                'current_price_cad_formatted' => number_format($currentPriceCAD, 2, '.', ',') . '$ CAD',
                'total_value_cad_formatted' => '$' . number_format($currentValueCAD, 2, '.', ','),
                'total_value_cad_raw' => $currentValueCAD,
                'currency_change_24h_percent' => $change24hPercent, // Renommé pour plus de clarté
                'currency_change_24h_value_cad_formatted' => ($changeValueCAD >= 0 ? '+' : '') . number_format($changeValueCAD, 2, '.', ',') . '$', // Changement pour cet avoir
                // TODO : Centraliser cette logique (par ex., nouvelle classe Utility ou Trait, ou déplacer vers le modèle Currency)
                'image_url' => $this->getCryptoImageUrl($holding['symbol'])
            ];
        }

        // Je calcule le pourcentage global de changement du portefeuille sur 24h
        $portfolioValueCrypto24hAgo = $portfolioValueCryptoCAD - $portfolioChange24hCAD;
        $portfolioChange24hPercent = ($portfolioValueCrypto24hAgo > 0)
            ? ($portfolioChange24hCAD / $portfolioValueCrypto24hAgo) * 100
            : 0; // J'évite la division par zéro si le portefeuille était vide il y a 24h


        // Je génère des données factices pour le graphique du portefeuille (À remplacer par une logique réelle si besoin)
        // Cette simulation se termine maintenant plus près de la valeur *totale* du compte
        $totalAccountValueCAD = $user['balance_cad'] + $portfolioValueCryptoCAD;
        $simulatedPreviousTotal = $totalAccountValueCAD * 0.9; // Juste une estimation pour les points précédents
        $portfolioChartData = [
             'labels' => ['T-5', 'T-4', 'T-3', 'T-2', 'T-1', 'Maintenant'], // Étiquettes plus génériques
             'values' => [
                 round($simulatedPreviousTotal * 0.85),
                 round($simulatedPreviousTotal * 0.90),
                 round($simulatedPreviousTotal * 0.98),
                 round($simulatedPreviousTotal * 0.95),
                 round($simulatedPreviousTotal * 1.02),
                 round($totalAccountValueCAD) // Je termine avec la valeur totale actuelle
             ]
        ];

        // Je formate les transactions récentes
        $recentTransactionsFormatted = [];
        foreach($recentTransactionsRaw as $tx) {
             $recentTransactionsFormatted[] = [
                  'type' => ucfirst($tx['type']),
                  'symbol' => $tx['currency_symbol'],
                  'quantity' => number_format($tx['quantity'], 6, '.', ''),
                  'total_amount_cad' => number_format($tx['total_amount_cad'], 2, '.', ','),
                  // J'ajoute le prix par unité (en supposant qu'il est stocké en USD mais représente du CAD maintenant)
                  'price_per_unit_cad' => (float)$tx['price_per_unit_usd'],
                  'timestamp' => date('Y-m-d H:i', strtotime($tx['timestamp']))
             ];
        }


         $data = [
             'account' => [
                 'type' => $user['is_admin'] ? 'Admin' : 'Standard', // J'ai changé 'Premium' en 'Standard'
                 'status' => ucfirst($user['status']),
                 'last_login' => $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Jamais',
                 'balance_cad_raw' => $user['balance_cad'],
                 'balance_cad_formatted' => number_format($user['balance_cad'], 2, '.', ',') . '$ CAD',
                 // 'weekly_gain_percent' => 3.2 // Placeholder - à envisager de supprimer si non implémenté
                 'is_admin' => (bool)$user['is_admin'], // NOUVEAU : J'expose le statut admin
                 'fullname' => $user['fullname'], // NOUVEAU : J'expose le nom complet
                 'email' => $user['email']     // NOUVEAU : J'expose l'email
             ],
             'portfolio' => [
                 'total_value_cad_raw' => $totalAccountValueCAD,
                 'total_value_cad_formatted' => number_format($totalAccountValueCAD, 2, '.', ',') . '$ CAD',
                 'crypto_value_cad_raw' => $portfolioValueCryptoCAD,
                 'crypto_value_cad_formatted' => number_format($portfolioValueCryptoCAD, 2, '.', ',') . '$ CAD',
                 'change_24h_cad_formatted' => ($portfolioChange24hCAD >= 0 ? '+' : '') . number_format($portfolioChange24hCAD, 2, '.', ','),
                 'change_24h_percent_formatted' => ($portfolioChange24hPercent >= 0 ? '+' : '') . number_format($portfolioChange24hPercent, 2, '.', ',') . '%',
                 // TODO : Implémenter le calcul réel P/L basé sur l'historique des transactions
                 // 'total_profit_loss_cad_formatted' => '+$1,234.56',
                 // 'total_profit_loss_percent_formatted' => '+15.67%',
             ],
             'holdings' => $holdingsFormatted,
             'portfolioChart' => $portfolioChartData,
             'recent_transactions' => $recentTransactionsFormatted // J'ai ajouté les transactions récentes
         ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    // Point d'API : J'obtiens les données pour l'onglet Statistiques d'Allocation
    public function getAllocationData() {
        // J'instancie le modèle Transaction ici s'il n'est pas déjà fait dans le constructeur
        // (En supposant que $this->transactionModel est disponible via le constructeur)
        if (!$this->transactionModel) {
             $this->transactionModel = new Transaction($this->db);
        }

        AuthGuard::protect(); // Je m'assure que l'utilisateur est connecté
        $userId = AuthGuard::user();

        $user = $this->userModel->findById($userId);
        $holdingsRaw = $this->walletModel->findByUserWithDetails($userId);

        $allocationData = [];
        $totalCryptoValueCAD = 0;
        $totalCostBasisCAD = 0; // NOUVEAU : Je suis le coût de base total

        // Je calcule d'abord la valeur totale
        foreach ($holdingsRaw as $holding) {
            $currentPriceCAD = (float)$holding['current_price_usd']; // Je traite le prix de la BDD comme du CAD
            $currentValueCAD = $holding['quantity'] * $currentPriceCAD;
            $totalCryptoValueCAD += $currentValueCAD;
        }

        // Je calcule le pourcentage pour chaque avoir
        foreach ($holdingsRaw as $holding) {
            $currentPriceCAD = (float)$holding['current_price_usd'];
            $currentValueCAD = $holding['quantity'] * $currentPriceCAD;
            $percentage = ($totalCryptoValueCAD > 0) ? ($currentValueCAD / $totalCryptoValueCAD) * 100 : 0;

            // Je calcule le Coût de Base et le P/L non réalisé pour cet avoir
            $costBasisCAD = $this->transactionModel->calculateCurrentFIFOBaseCost($userId, (int)$holding['currency_id']);
            $unrealizedPlCAD = $currentValueCAD - $costBasisCAD;
            $totalCostBasisCAD += $costBasisCAD; // J'ajoute au coût de base total

            $allocationData[] = [
                'symbol' => $holding['symbol'],
                'name' => $holding['name'], // J'inclus le nom pour d'éventuelles infobulles
                'value_cad' => $currentValueCAD,
                'percentage' => round($percentage, 2),
                'cost_basis_cad' => round($costBasisCAD, 2), // J'ajoute le coût de base
                'unrealized_pl_cad' => round($unrealizedPlCAD, 2) // J'ajoute le P/L non réalisé
            ];
        }

        // Je calcule le P/L non réalisé total
        $totalUnrealizedPlCAD = $totalCryptoValueCAD - $totalCostBasisCAD;

        $data = [
            'allocation' => $allocationData,
            'total_crypto_value_cad' => round($totalCryptoValueCAD, 2),
            'user_balance_cad' => round($user['balance_cad'], 2),
            'total_unrealized_pl_cad' => round($totalUnrealizedPlCAD, 2) // J'ajoute le P/L non réalisé total
        ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

     // Aide d'assets pour obtenir l'URL de l'image (comme dans la table index.html)
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