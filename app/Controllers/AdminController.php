<?php
// /cryptotrade/app/Controllers/AdminController.php
namespace App\Controllers;

/**
 * Développeur assignés(s) : Seydina
 * Entité : Classe 'AdminController' de la couche Controllers
 */

use App\Core\Session;
use App\Core\Request;
use App\Utils\AuthGuard;
use App\Utils\Csrf;
use PDO;

class AdminController {
    private $db;
    private $request;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->request = new Request();
    }

    // Page admin d'exemple
    public function index() {
        // Je vérifie si l'user est connecté ET admin
        AuthGuard::protectAdmin();

        // Si on arrive ici, c'est qu'on est admin.
        // Maintenant, je peux charger une vue admin, récupérer des données, etc.

        echo "<h1>Admin Panel</h1>";
        echo "<p>Welcome, administrator!" . (Session::has('user_fullname') ? ' (' . htmlspecialchars(Session::get('user_fullname')) . ')' : '') . "</p>";
        echo '<p><a href="' . BASE_URL . '/dashboard">Back to Dashboard</a></p>';
        // Ex: Charger un fichier de vue admin au lieu d'echo.
        // require 'path/to/admin/view.php';
        exit;
    }

    // Ajouter d'autres méthodes admin ici (ex: lister users, gérer paramètres)

    // --- API CRUD pour les devises ---

    // GET /api/admin/currencies - Je prends toutes les devises pour le dropdown admin
    public function getCurrenciesForAdmin() {
        AuthGuard::protectAdmin();
        $currencyModel = new \App\Models\Currency($this->db);
        $currencies = $currencyModel->findAll(); // Je prends tout, même les inactives (si j'ajoute un statut plus tard).
        // Format minimal pour le dropdown.
        $formatted = array_map(function($c) {
            return [
                'id' => (int)$c['id'],
                'name' => $c['name'],
                'symbol' => $c['symbol'],
                // Optionnel: Ajouter prix/cap CAD.
                // 'current_price_cad' => (float)$c['current_price_usd'],
                // 'market_cap_cad' => (float)$c['market_cap_usd']
            ];
        }, $currencies);
        $this->jsonResponse(true, 'Currencies fetched', $formatted);
    }

     // GET /api/admin/currency/{id} - Je prends les détails d'une devise.
    public function getCurrencyDetails($params) {
        AuthGuard::protectAdmin();
        $currencyId = $params['id'] ?? null;
        if (!$currencyId) {
            return $this->jsonResponse(false, 'Currency ID is required.', null, 400);
        }

        $currencyModel = new \App\Models\Currency($this->db);
        $currency = $currencyModel->findById($currencyId);

        if (!$currency) {
             return $this->jsonResponse(false, 'Currency not found.', null, 404);
        }
        // Je convertis les types pour être cohérent.
        $currency['id'] = (int)$currency['id'];
        $currency['current_price_usd'] = (float)$currency['current_price_usd'];
        $currency['change_24h_percent'] = (float)$currency['change_24h_percent'];
        $currency['market_cap_usd'] = (float)$currency['market_cap_usd'];
        $currency['base_volatility'] = (float)$currency['base_volatility'];
        $currency['base_trend'] = (float)$currency['base_trend'];

        // J'ajoute des champs _cad clairs (valeur de _usd).
        $currency['current_price_cad'] = (float)$currency['current_price_usd'];
        $currency['market_cap_cad'] = (float)$currency['market_cap_usd'];

        $this->jsonResponse(true, 'Currency details fetched', $currency);
    }

    // POST /api/admin/currency/add - J'ajoute une devise.
    public function addCurrency() {
        AuthGuard::protectAdmin();
        Csrf::protect($this->request);

        $data = $this->request->getBody();

        // Validation de base (à améliorer si besoin).
        if (empty($data['name']) || empty($data['symbol']) || !isset($data['current_price_cad']) || !isset($data['change_24h_percent']) || !isset($data['market_cap_cad']) || !isset($data['base_volatility']) || !isset($data['base_trend'])) {
            return $this->jsonResponse(false, 'Missing required fields.', null, 400);
        }

        // Je nettoie/valide les types.
        $currencyData = [
            'name' => filter_var($data['name'], FILTER_SANITIZE_STRING),
            'symbol' => strtoupper(filter_var($data['symbol'], FILTER_SANITIZE_STRING)),
            'current_price_cad' => filter_var($data['current_price_cad'], FILTER_VALIDATE_FLOAT),
            'change_24h_percent' => filter_var($data['change_24h_percent'], FILTER_VALIDATE_FLOAT),
            'market_cap_cad' => filter_var($data['market_cap_cad'], FILTER_VALIDATE_FLOAT),
            'base_volatility' => filter_var($data['base_volatility'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'base_trend' => filter_var($data['base_trend'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        ];

         // Je vérifie si la validation a raté (retourne false).
        foreach ($currencyData as $key => $value) {
            if ($value === false && !in_array($key, ['change_24h_percent', 'base_trend'])) { // Zéro ok pour ceux-là.
                 return $this->jsonResponse(false, "Invalid data format for {$key}.", null, 400);
            }
        }

        $currencyModel = new \App\Models\Currency($this->db);

        // Je vérifie si le symbole existe déjà.
        if ($currencyModel->findBySymbol($currencyData['symbol'])) {
            return $this->jsonResponse(false, 'Currency symbol already exists.', null, 409); // 409 Conflict
        }

        try {
            if ($currencyModel->create($currencyData)) {
                $this->jsonResponse(true, 'Currency added successfully.');
            } else {
                $this->jsonResponse(false, 'Failed to add currency.', null, 500);
            }
        } catch (\PDOException $e) {
            error_log("DB Error Add Currency: " . $e->getMessage());
            $this->jsonResponse(false, 'Database error adding currency.', null, 500);
        }
    }

    // POST /api/admin/currency/update/{id} - Je mets à jour une devise.
    public function updateCurrency($params) {
        AuthGuard::protectAdmin();
        Csrf::protect($this->request);

        $data = $this->request->getBody();
        $currencyId = $params['id'] ?? null;

        if (!$currencyId) {
            return $this->jsonResponse(false, 'Currency ID is required.', null, 400);
        }

        // Validation de base.
        if (empty($data['name']) || empty($data['symbol']) || !isset($data['current_price_cad']) || !isset($data['change_24h_percent']) || !isset($data['market_cap_cad']) || !isset($data['base_volatility']) || !isset($data['base_trend'])) {
            return $this->jsonResponse(false, 'Missing required fields.', null, 400);
        }

        // Je nettoie/valide.
        $currencyData = [
            'name' => filter_var($data['name'], FILTER_SANITIZE_STRING),
            'symbol' => strtoupper(filter_var($data['symbol'], FILTER_SANITIZE_STRING)),
            'current_price_cad' => filter_var($data['current_price_cad'], FILTER_VALIDATE_FLOAT),
            'change_24h_percent' => filter_var($data['change_24h_percent'], FILTER_VALIDATE_FLOAT),
            'market_cap_cad' => filter_var($data['market_cap_cad'], FILTER_VALIDATE_FLOAT),
            'base_volatility' => filter_var($data['base_volatility'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'base_trend' => filter_var($data['base_trend'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        ];

        foreach ($currencyData as $key => $value) {
             if ($value === false && !in_array($key, ['change_24h_percent', 'base_trend'])) {
                 return $this->jsonResponse(false, "Invalid data format for {$key}.", null, 400);
             }
         }

        $currencyModel = new \App\Models\Currency($this->db);

        // Je vérifie si la devise existe.
        if (!$currencyModel->findById($currencyId)) {
            return $this->jsonResponse(false, 'Currency not found.', null, 404);
        }

         // Je vérifie si le NOUVEAU symbole existe déjà ailleurs.
        $existingSymbol = $currencyModel->findBySymbol($currencyData['symbol']);
        if ($existingSymbol && (int)$existingSymbol['id'] !== (int)$currencyId) {
            return $this->jsonResponse(false, 'Another currency with this symbol already exists.', null, 409);
        }

        try {
            if ($currencyModel->update($currencyId, $currencyData)) {
                $this->jsonResponse(true, 'Currency updated successfully.');
            } else {
                // Peut arriver si rien n'a changé (données identiques).
                // On pourrait quand même retourner succès ou un message spécifique.
                $this->jsonResponse(true, 'Currency data was not changed.');
            }
        } catch (\PDOException $e) { // Attraper erreurs BDD spécifiques d'abord si besoin.
            error_log("DB Error Update Currency (PDO): " . $e->getMessage());
            $this->jsonResponse(false, 'Database error during update.', null, 500);
        } catch (\Exception $e) { // Attraper toute autre exception.
            error_log("General Error Update Currency: " . $e->getMessage());
            $this->jsonResponse(false, 'An unexpected error occurred during update.', null, 500);
        }
    }

    // DELETE /api/admin/currency/delete/{id} - Je supprime une devise.
    public function deleteCurrency($params) {
        AuthGuard::protectAdmin();
        Csrf::protect($this->request);

        $currencyId = $params['id'] ?? null;

        if (!$currencyId) {
            return $this->jsonResponse(false, 'Currency ID is required.', null, 400);
        }

        $currencyModel = new \App\Models\Currency($this->db);

        // Je vérifie si la devise existe avant de supprimer.
        if (!$currencyModel->findById($currencyId)) {
            return $this->jsonResponse(false, 'Currency not found.', null, 404);
        }

        // Ajouter une vérif ici : des portefeuilles ont cette devise ?
        // $walletModel = new \App\Models\Wallet($this->db);
        // if ($walletModel->holdingsExistForCurrency($currencyId)) {
        //    return $this->jsonResponse(false, 'Cannot delete currency held by users.', null, 409); // Conflict
        // }

        try {
            if ($currencyModel->deleteById($currencyId)) {
                $this->jsonResponse(true, 'Currency deleted successfully.');
            } else {
                $this->jsonResponse(false, 'Failed to delete currency.', null, 500);
            }
        } catch (\PDOException $e) {
             // Attraper erreurs de clé étrangère (si portefeuilles liés).
            if ($e->getCode() == '23000') { // Integrity constraint violation
                 error_log("DB Error Delete Currency Constraint: " . $e->getMessage());
                 return $this->jsonResponse(false, 'Cannot delete currency. It might be referenced in wallets or transactions.', null, 409); // Conflict
            }
            error_log("DB Error Delete Currency: " . $e->getMessage());
            $this->jsonResponse(false, 'Database error deleting currency.', null, 500);
        }
    }

    // Fonction aide pour réponses JSON (réutilisée).
    private function jsonResponse($success, $message = '', $data = null, $statusCode = null) {
        header('Content-Type: application/json');
        if ($statusCode === null) {
            $statusCode = $success ? 200 : 400; // Codes statut par défaut.
        }
        http_response_code($statusCode);

        $response = [
            'success' => $success,
            'message' => $message
        ];
        // Inclure 'data' seulement si non null.
        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }
}