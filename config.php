<?php
// /cryptotrade/config.php

/**
 * Développeur assignés(s) : Pierre
 * Entité : Fichier de configuration
 */

// Config BDD
define('DB_HOST', 'localhost');      // localhost' avec XAMPP
define('DB_NAME', 'cryptotrade_db');
define('DB_USER', 'root');          // User XAMPP par défaut
define('DB_PASS', '');              // Mdp XAMPP par défaut 

// Config App
define('BASE_URL', 'http://localhost/cryptotrade'); // À ajuster si besoin

// Coût Hachage Mdp (plus haut = plus sûr mais lent)
define('PASSWORD_COST', 10);

// Rapports d'erreurs (Dev vs Prod)
// Pour le dev :
error_reporting(E_ALL);
ini_set('display_errors', '1');
// Pour la prod :
// error_reporting(0);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1); // Je log les erreurs dans un fichier
?>