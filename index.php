<?php
// /cryptotrade/index.php - Front Controller

// Strict types and error reporting (dev)
declare(strict_types=1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Core Includes ---
require_once 'config.php';
require_once 'app/Core/Database.php';
require_once 'app/Core/Session.php'; // Ensures session is started
require_once 'app/Core/Request.php';
require_once 'app/Core/Router.php';
require_once 'app/Utils/AuthGuard.php'; // Needed for route definitions potentially

// --- Basic Autoloader ---
spl_autoload_register(function ($class_name) {
    // Convert namespace to path (App\Core\Database -> app/Core/Database.php)
    $file = str_replace('\\', '/', $class_name) . '.php';
    // Adjust base path if needed
    $base_dir = __DIR__ . '/';
    $full_path = $base_dir . $file;

    if (file_exists($full_path)) {
        require_once $full_path;
    } else {
         $parts = explode('\\', $class_name);
         $class_file = end($parts) . '.php';
         $possible_paths = [
            'app/Controllers/' . $class_file, 'app/Models/' . $class_file,
            'app/Utils/' . $class_file, 'app/Core/' . $class_file
         ];
          foreach ($possible_paths as $path) {
               if (file_exists($base_dir . $path)) { require_once $base_dir . $path; return; }
          }
         error_log("Autoloader: Class {$class_name} not found via path {$full_path} or fallbacks.");
    }
});

// --- Application Bootstrap ---
$dbInstance = App\Core\Database::getInstance();
$request = new App\Core\Request();
$router = new App\Core\Router($request);

// --- Define Routes ---

// Homepage Route *** CHANGED to require home.php ***
$router->get('/', function() { require 'home.php'; });

// Authentication Routes *** CHANGED to require .php files ***
$router->get('/login', function() {
    if (App\Utils\AuthGuard::check()) {
        header('Location: ' . BASE_URL . '/dashboard'); exit;
    }
    require 'login.php'; // Use the renamed file
});
$router->get('/signup', function() {
    if (App\Utils\AuthGuard::check()) {
        header('Location: ' . BASE_URL . '/dashboard'); exit;
    }
    require 'signup.php'; // Use the renamed file
});

$router->post('/login', 'App\Controllers\AuthController@login');
$router->post('/signup', 'App\Controllers\AuthController@signup');
$router->get('/logout', 'App\Controllers\AuthController@logout');

// Dashboard Route (Protected) - Controller handles the require
$router->get('/dashboard', 'App\Controllers\DashboardController@index');

// API Routes (Protected where necessary)
$router->get('/api/dashboard/data', 'App\Controllers\DashboardController@getDashboardData');
$router->get('/api/crypto/list', 'App\Controllers\MarketController@getCryptoList');
$router->get('/api/crypto/chart/{id}', 'App\Controllers\MarketController@getCryptoChartData');
$router->post('/api/transaction/buy', 'App\Controllers\TransactionController@buy');
$router->post('/api/transaction/sell', 'App\Controllers\TransactionController@sell');

// --- API Route for Stats Tab (Allocation) ---
$router->get('/api/stats/allocation', 'App\Controllers\DashboardController@getAllocationData');

// --- API Route for User Profile Update (Protected) ---
$router->post('/api/user/update', 'App\Controllers\UserController@updateProfile');

// --- API Route for User Transaction History (Protected) ---
$router->get('/api/user/transactions', 'App\Controllers\UserController@getUserTransactions');

// --- API Routes for Transaction Download (Protected) ---
$router->get('/api/user/transactions/csv', 'App\Controllers\UserController@downloadTransactionsCsv');
$router->get('/api/user/transactions/pdf', 'App\Controllers\UserController@downloadTransactionsPdf'); // Add PDF route

// --- Admin Route Example (Protected) ---
$router->get('/admin', 'App\Controllers\AdminController@index');

// --- API Routes for Admin Currency Management (Protected) ---
$router->get('/api/admin/currencies', 'App\Controllers\AdminController@getCurrenciesForAdmin');
$router->get('/api/admin/currency/{id}', 'App\Controllers\AdminController@getCurrencyDetails');
$router->post('/api/admin/currency/add', 'App\Controllers\AdminController@addCurrency');
$router->post('/api/admin/currency/update/{id}', 'App\Controllers\AdminController@updateCurrency');
$router->delete('/api/admin/currency/delete/{id}', 'App\Controllers\AdminController@deleteCurrency'); // Using POST for delete in HTML forms is common, but DELETE is semantically correct for APIs

// --- Dispatch ---
try {
    $router->dispatch();
} catch (Exception $e) {
     error_log("Unhandled Exception: " . $e->getMessage());
     http_response_code(500);
     echo "An unexpected error occurred.";
}

?>