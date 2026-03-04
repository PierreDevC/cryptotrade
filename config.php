<?php
// /cryptotrade/config.php

/**
 * Développeur assignés(s) : Pierre
 * Entité : Fichier de configuration
 */

// Config BDD - Auto-detect Railway > Production (InfinityFree/generic) > Local
if (isset($_ENV['MYSQL_URL'])) {
    // Railway deployment - parse MySQL URL
    $db_url = parse_url($_ENV['MYSQL_URL']);
    define('DB_HOST', $db_url['host']);
    define('DB_NAME', ltrim($db_url['path'], '/'));
    define('DB_USER', $db_url['user']);
    define('DB_PASS', $db_url['pass']);
    define('DB_PORT', $db_url['port'] ?? 3307);

    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} elseif (getenv('APP_ENV') === 'production') {
    // Production deployment (InfinityFree or other shared hosting)
    // Set DB_HOST, DB_NAME, DB_USER, DB_PASS via .htaccess SetEnv directives
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'cryptotrade_db');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    // Local XAMPP development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'cryptotrade_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', 3307);

    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Config App - Auto-detect Railway > Production > Local
if (isset($_ENV['RAILWAY_PUBLIC_DOMAIN'])) {
    // Railway deployment
    define('BASE_URL', 'https://' . $_ENV['RAILWAY_PUBLIC_DOMAIN']);
} elseif (getenv('APP_BASE_URL')) {
    // Production deployment - set APP_BASE_URL via .htaccess SetEnv
    define('BASE_URL', rtrim(getenv('APP_BASE_URL'), '/'));
} else {
    // Local development
    define('BASE_URL', 'http://localhost/cryptotrade');
}

// Coût Hachage Mdp (plus haut = plus sûr mais lent)
define('PASSWORD_COST', 10);

// Helper function for asset URLs
function asset_url($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}