<?php
// /cryptotrade/config.php

/**
 * Développeur assignés(s) : Pierre
 * Entité : Fichier de configuration
 */

// Config BDD - Auto-detect Railway vs Local
if (isset($_ENV['MYSQL_URL'])) {
    // Railway deployment - parse MySQL URL
    $db_url = parse_url($_ENV['MYSQL_URL']);
    define('DB_HOST', $db_url['host']);
    define('DB_NAME', ltrim($db_url['path'], '/'));
    define('DB_USER', $db_url['user']);
    define('DB_PASS', $db_url['pass']);
    define('DB_PORT', $db_url['port'] ?? 3307);
    
    // Production mode on Railway
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
    
    // Development mode locally
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Config App - Auto-detect Railway vs Local
if (isset($_ENV['RAILWAY_PUBLIC_DOMAIN'])) {
    // Railway deployment
    define('BASE_URL', 'https://' . $_ENV['RAILWAY_PUBLIC_DOMAIN']);
} else {
    // Local development
    define('BASE_URL', 'http://localhost/cryptotrade');
}

// Coût Hachage Mdp (plus haut = plus sûr mais lent)
define('PASSWORD_COST', 10);

// Helper function for asset URLs
function asset_url($path) {
    if (isset($_ENV['RAILWAY_PUBLIC_DOMAIN'])) {
        // Railway deployment - assets are served from root
        return '/' . ltrim($path, '/');
    } else {
        // Local development - include cryptotrade subdirectory
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

// Rapports d'erreurs (Dev vs Prod)
// Pour le dev :
error_reporting(E_ALL);
ini_set('display_errors', '1');
// Pour la prod :
// error_reporting(0);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1); // Je log les erreurs dans un fichier