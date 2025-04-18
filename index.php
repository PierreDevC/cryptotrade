<?php
// /cryptotrade/index.php - Contrôleur frontal

/**
 * Développeur assignés(s) : Seydina
 * Entité : Classe 'index.php' de la couche Front Controller
 */

// Types stricts et rapport d'erreurs (dev)
declare(strict_types=1);
ini_set("display_errors", 1);
error_reporting(E_ALL);

// --- Includes Core ---
require_once "config.php";
require_once "app/Core/Database.php";
require_once "app/Core/Session.php"; // Je m'assure que la session est démarrée
require_once "app/Core/Request.php";
require_once "app/Core/Router.php";
require_once "app/Utils/AuthGuard.php"; // Utile pour la définition des routes

// --- Autoloader Basique ---
spl_autoload_register(function ($class_name) {
    // Je convertis le namespace en chemin (App\Core\Database -> app/Core/Database.php)
    $file = str_replace("\\", "/", $class_name) . ".php";
    // J'ajuste le chemin de base si besoin
    $base_dir = __DIR__ . "/";
    $full_path = $base_dir . $file;

    if (file_exists($full_path)) {
        require_once $full_path;
    } else {
         $parts = explode("\\", $class_name);
         $class_file = end($parts) . ".php";
         $possible_paths = [
            "app/Controllers/" . $class_file, "app/Models/" . $class_file,
            "app/Utils/" . $class_file, "app/Core/" . $class_file
         ];
          foreach ($possible_paths as $path) {
               if (file_exists($base_dir . $path)) { require_once $base_dir . $path; return; }
          }
         error_log("Autoloader: Classe {$class_name} non trouvée via {$full_path} ou les chemins alternatifs.");
    }
});

// --- Bootstrap de l'App ---
$dbInstance = App\Core\Database::getInstance();
$request = new App\Core\Request();
$router = new App\Core\Router($request);

// --- Définition des Routes ---

// Route Homepage *** MODIFIÉ pour require home.php ***
$router->get("/", function() { require "home.php"; });

// Routes d'Authentification *** MODIFIÉ pour require les .php ***
$router->get("/login", function() {
    if (App\Utils\AuthGuard::check()) {
        header("Location: " . BASE_URL . "/dashboard"); exit;
    }
    require "login.php"; // J'utilise le fichier renommé
});
$router->get("/signup", function() {
    if (App\Utils\AuthGuard::check()) {
        header("Location: " . BASE_URL . "/dashboard"); exit;
    }
    require "signup.php"; // J'utilise le fichier renommé
});

$router->post("/login", "App\Controllers\AuthController@login");
$router->post("/signup", "App\Controllers\AuthController@signup");
$router->post("/logout", "App\Controllers\AuthController@logout");

// Route Dashboard (Protégée) - Le contrôleur gère le require
$router->get("/dashboard", "App\Controllers\DashboardController@index");

// Routes API (Protégées si nécessaire)
$router->get("/api/dashboard/data", "App\Controllers\DashboardController@getDashboardData");
$router->get("/api/crypto/list", "App\Controllers\MarketController@getCryptoList");
$router->get("/api/crypto/chart/{id}", "App\Controllers\MarketController@getCryptoChartData");
$router->post("/api/transaction/buy", "App\Controllers\TransactionController@buy");
$router->post("/api/transaction/sell", "App\Controllers\TransactionController@sell");

// --- Route API pour l'onglet Stats (Allocation) ---
$router->get("/api/stats/allocation", "App\Controllers\DashboardController@getAllocationData");

// --- Route API pour màj Profil Utilisateur (Protégée) ---
$router->post("/api/user/update", "App\Controllers\UserController@updateProfile");

// --- Route API pour Historique Transactions Utilisateur (Protégée) ---
$router->get("/api/user/transactions", "App\Controllers\UserController@getUserTransactions");

// --- Routes API pour Téléchargement Transactions (Protégée) ---
$router->get("/api/user/transactions/csv", "App\Controllers\UserController@downloadTransactionsCsv");
$router->get("/api/user/transactions/pdf", "App\Controllers\UserController@downloadTransactionsPdf"); // J'ajoute la route PDF

// --- Exemple Route Admin (Protégée) ---
$router->get("/admin", "App\Controllers\AdminController@index");

// --- Routes API pour Gestion Devises Admin (Protégée) ---
$router->get("/api/admin/currencies", "App\Controllers\AdminController@getCurrenciesForAdmin");
$router->get("/api/admin/currency/{id}", "App\Controllers\AdminController@getCurrencyDetails");
$router->post("/api/admin/currency/add", "App\Controllers\AdminController@addCurrency");
$router->post("/api/admin/currency/update/{id}", "App\Controllers\AdminController@updateCurrency");
$router->delete("/api/admin/currency/delete/{id}", "App\Controllers\AdminController@deleteCurrency"); // J'utilise DELETE pour l'API, mais POST est courant pour les formulaires HTML

// --- Dispatch ---
try {
    $router->dispatch();
} catch (Exception $e) {
     error_log("Exception non gérée: " . $e->getMessage());
     http_response_code(500);
     echo "Une erreur inattendue s'est produite.";
}

?>