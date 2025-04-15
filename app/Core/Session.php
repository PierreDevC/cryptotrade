<?php
// /cryptotrade/app/Core/Session.php
namespace App\Core;

class Session {
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function has($key) {
         return isset($_SESSION[$key]);
    }

    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        session_destroy();
    }

    // Optional: Flash messages (show once then remove)
    public static function flash($key, $message = null) {
        if ($message !== null) {
            self::set('flash_' . $key, $message);
        } elseif (self::has('flash_' . $key)) {
            $message = self::get('flash_' . $key);
            self::remove('flash_' . $key);
            return $message;
        }
        return null;
    }
}
// Initialize session when this file is included
Session::init();
?>