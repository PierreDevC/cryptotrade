<?php
// /cryptotrade/config.php

// Database Configuration
define('DB_HOST', 'localhost');      // Usually 'localhost' for XAMPP
define('DB_NAME', 'cryptotrade_db');
define('DB_USER', 'root');          // Default XAMPP user
define('DB_PASS', '');              // Default XAMPP password (often empty)

// Application Configuration
define('BASE_URL', 'http://localhost/cryptotrade'); // Adjust if needed

// Password Hashing Cost (Higher is more secure but slower)
define('PASSWORD_COST', 10);

// Error Reporting (Development vs Production)
// For development:
error_reporting(E_ALL);
ini_set('display_errors', 1);
// For production:
// error_reporting(0);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1); // Log errors to a file instead
?>