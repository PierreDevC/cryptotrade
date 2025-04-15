<?php
// /cryptotrade/app/Utils/AuthGuard.php
namespace App\Utils;

use App\Core\Session;

class AuthGuard {
    public static function check() {
        return Session::has('user_id');
    }

    public static function user() {
        // Returns user ID, or you could fetch the full user object here if needed often
        return Session::get('user_id');
    }

    public static function isAdmin() {
        return Session::get('is_admin') === true; // Check specifically for true
    }

    // Redirects if not logged in
    public static function protect() {
        if (!self::check()) {
            // Optionally store intended URL: Session::set('intended_url', $_SERVER['REQUEST_URI']);
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

     // Redirects if not admin
    public static function protectAdmin() {
        self::protect(); // Must be logged in first
        if (!self::isAdmin()) {
            http_response_code(403); // Forbidden
            echo "403 Forbidden: Admin access required.";
            // Or redirect to dashboard: header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }
}
?>