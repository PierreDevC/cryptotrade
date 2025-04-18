<?php
// /cryptotrade/app/Utils/AuthGuard.php
namespace App\Utils;

/**
 * Développeur assignés(s) : Pierre
 * Entité : Classe 'AuthGuard' de la couche Utils
 */

use App\Core\Session;

class AuthGuard {
    public static function check() {
        return Session::has("user_id");
    }

    public static function user() {
        // Renvoie l'ID user, ou je pourrais récupérer l'objet user complet ici
        return Session::get("user_id");
    }

    public static function isAdmin() {
        return Session::get("is_admin") === true; // Je vérifie que c'est bien true
    }

    // Je redirige si pas connecté
    public static function protect() {
        if (!self::check()) {
            // Optionnel : je stocke l'URL voulue : Session::set('intended_url', $_SERVER['REQUEST_URI']);
            header("Location: " . BASE_URL . "/login");
            exit;
        }
    }

     // Je redirige si pas admin
    public static function protectAdmin() {
        self::protect(); // Faut être connecté d'abord
        if (!self::isAdmin()) {
            http_response_code(403); // Interdit
            echo "403 Forbidden: Admin access required.";
            // Ou je redirige vers le dashboard : header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }
}
?>