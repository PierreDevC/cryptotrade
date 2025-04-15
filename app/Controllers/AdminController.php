<?php
// /cryptotrade/app/Controllers/AdminController.php
namespace App\Controllers;

use App\Core\Session;
use App\Utils\AuthGuard;
use PDO;

class AdminController {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // Example Admin Page
    public function index() {
        // Ensure the user is logged in AND is an admin
        AuthGuard::protectAdmin();

        // If the script reaches here, the user is an admin.
        // You can now load an admin view, fetch admin-specific data, etc.

        echo "<h1>Admin Panel</h1>";
        echo "<p>Welcome, administrator!" . (Session::has('user_fullname') ? ' (' . htmlspecialchars(Session::get('user_fullname')) . ')' : '') . "</p>";
        echo '<p><a href="' . BASE_URL . '/dashboard">Back to Dashboard</a></p>';
        // Example: Load an admin view file instead of echoing
        // require 'path/to/admin/view.php';
        exit;
    }

    // Add other admin-specific methods here (e.g., listUsers, manageSettings)

    // --- Currency CRUD API Methods ---

    // GET /api/admin/currencies - Fetch all currencies for admin management dropdown
    public function getCurrenciesForAdmin() {
        AuthGuard::protectAdmin();
        $currencyModel = new \App\Models\Currency($this->db);
        $currencies = $currencyModel->findAll(); // Fetch all, including potentially inactive ones if you add a status later
        // Format minimally for dropdown
        $formatted = array_map(function($c) {
            return [
                'id' => (int)$c['id'],
                'name' => $c['name'],
                'symbol' => $c['symbol']
            ];
        }, $currencies);
        $this->jsonResponse(true, 'Currencies fetched', $formatted);
    }

     // GET /api/admin/currency/{id} - Fetch full details for a specific currency
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
        // Cast types for consistency
        $currency['id'] = (int)$currency['id'];
        $currency['current_price_usd'] = (float)$currency['current_price_usd'];
        $currency['change_24h_percent'] = (float)$currency['change_24h_percent'];
        $currency['market_cap_usd'] = (float)$currency['market_cap_usd'];
        $currency['base_volatility'] = (float)$currency['base_volatility'];
        $currency['base_trend'] = (float)$currency['base_trend'];

        $this->jsonResponse(true, 'Currency details fetched', $currency);
    }

    // POST /api/admin/currency/add - Add a new currency
    public function addCurrency() {
        AuthGuard::protectAdmin();
        $request = new \App\Core\Request();
        $data = $request->getBody();

        // Basic Validation (Add more robust validation as needed)
        if (empty($data['name']) || empty($data['symbol']) || !isset($data['current_price_usd']) || !isset($data['change_24h_percent']) || !isset($data['market_cap_usd']) || !isset($data['base_volatility']) || !isset($data['base_trend'])) {
            return $this->jsonResponse(false, 'Missing required fields.', null, 400);
        }

        // Sanitize/Validate data types
        $currencyData = [
            'name' => filter_var($data['name'], FILTER_SANITIZE_STRING),
            'symbol' => strtoupper(filter_var($data['symbol'], FILTER_SANITIZE_STRING)),
            'current_price_usd' => filter_var($data['current_price_usd'], FILTER_VALIDATE_FLOAT),
            'change_24h_percent' => filter_var($data['change_24h_percent'], FILTER_VALIDATE_FLOAT),
            'market_cap_usd' => filter_var($data['market_cap_usd'], FILTER_VALIDATE_FLOAT),
            'base_volatility' => filter_var($data['base_volatility'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'base_trend' => filter_var($data['base_trend'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        ];

         // Check for failed validation (returns false)
        foreach ($currencyData as $key => $value) {
            if ($value === false && !in_array($key, ['change_24h_percent', 'base_trend'])) { // Allow zero for these
                 return $this->jsonResponse(false, "Invalid data format for {$key}.", null, 400);
            }
        }

        $currencyModel = new \App\Models\Currency($this->db);

        // Check if symbol already exists
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

    // POST /api/admin/currency/update/{id} - Update an existing currency
    public function updateCurrency($params) {
        AuthGuard::protectAdmin();
        $request = new \App\Core\Request();
        $data = $request->getBody();
        $currencyId = $params['id'] ?? null;

        if (!$currencyId) {
            return $this->jsonResponse(false, 'Currency ID is required.', null, 400);
        }

        // Basic Validation
        if (empty($data['name']) || empty($data['symbol']) || !isset($data['current_price_usd']) || !isset($data['change_24h_percent']) || !isset($data['market_cap_usd']) || !isset($data['base_volatility']) || !isset($data['base_trend'])) {
            return $this->jsonResponse(false, 'Missing required fields.', null, 400);
        }

        // Sanitize/Validate
        $currencyData = [
            'name' => filter_var($data['name'], FILTER_SANITIZE_STRING),
            'symbol' => strtoupper(filter_var($data['symbol'], FILTER_SANITIZE_STRING)),
            'current_price_usd' => filter_var($data['current_price_usd'], FILTER_VALIDATE_FLOAT),
            'change_24h_percent' => filter_var($data['change_24h_percent'], FILTER_VALIDATE_FLOAT),
            'market_cap_usd' => filter_var($data['market_cap_usd'], FILTER_VALIDATE_FLOAT),
            'base_volatility' => filter_var($data['base_volatility'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'base_trend' => filter_var($data['base_trend'], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        ];

        foreach ($currencyData as $key => $value) {
             if ($value === false && !in_array($key, ['change_24h_percent', 'base_trend'])) {
                 return $this->jsonResponse(false, "Invalid data format for {$key}.", null, 400);
             }
         }

        $currencyModel = new \App\Models\Currency($this->db);

        // Check if currency exists
        if (!$currencyModel->findById($currencyId)) {
            return $this->jsonResponse(false, 'Currency not found.', null, 404);
        }

         // Check if NEW symbol conflicts with ANOTHER existing currency
        $existingSymbol = $currencyModel->findBySymbol($currencyData['symbol']);
        if ($existingSymbol && (int)$existingSymbol['id'] !== (int)$currencyId) {
            return $this->jsonResponse(false, 'Another currency with this symbol already exists.', null, 409);
        }

        try {
            if ($currencyModel->update($currencyId, $currencyData)) {
                $this->jsonResponse(true, 'Currency updated successfully.');
            } else {
                // This might happen if no rows were affected (data identical)
                // Consider returning success still or a specific message
                $this->jsonResponse(true, 'Currency data was not changed.');
            }
        } catch (\PDOException $e) {
            error_log("DB Error Update Currency: " . $e->getMessage());
            $this->jsonResponse(false, 'Database error updating currency.', null, 500);
        }
    }

    // DELETE /api/admin/currency/delete/{id} - Delete a currency
    public function deleteCurrency($params) {
        AuthGuard::protectAdmin();
        $currencyId = $params['id'] ?? null;

        if (!$currencyId) {
            return $this->jsonResponse(false, 'Currency ID is required.', null, 400);
        }

        $currencyModel = new \App\Models\Currency($this->db);

        // Check if currency exists before attempting delete
        if (!$currencyModel->findById($currencyId)) {
            return $this->jsonResponse(false, 'Currency not found.', null, 404);
        }

        // Add check here: Are there any wallets holding this currency?
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
             // Catch foreign key constraint errors if wallets depend on it
            if ($e->getCode() == '23000') { // Integrity constraint violation
                 error_log("DB Error Delete Currency Constraint: " . $e->getMessage());
                 return $this->jsonResponse(false, 'Cannot delete currency. It might be referenced in wallets or transactions.', null, 409); // Conflict
            }
            error_log("DB Error Delete Currency: " . $e->getMessage());
            $this->jsonResponse(false, 'Database error deleting currency.', null, 500);
        }
    }

    // Helper to send JSON responses (moved from TransactionController for reuse)
    private function jsonResponse($success, $message = '', $data = null, $statusCode = null) {
        header('Content-Type: application/json');
        if ($statusCode === null) {
            $statusCode = $success ? 200 : 400; // Default status codes
        }
        http_response_code($statusCode);

        $response = [
            'success' => $success,
            'message' => $message
        ];
        // Only include data key if data is not null
        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }
}
?> 