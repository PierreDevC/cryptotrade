<?php
// /cryptotrade/app/Utils/Csrf.php
namespace App\Utils;

/**
 * Développeur assignés(s) : Pierre
 * Entité : Classe 'Csrf' de la couche Utils
 */

use App\Core\Session;
use App\Core\Request; // Ajout pour récupérer le token de la requête

class Csrf {
    private static $tokenName = '_csrf_token';

    // Je génère un token CSRF si besoin et le retourne.
    public static function generateToken(): string {
        if (!Session::has(self::$tokenName)) {
            $token = bin2hex(random_bytes(32)); // Je génère un token sécurisé
            Session::set(self::$tokenName, $token);
        }
        return Session::get(self::$tokenName);
    }

    // Je retourne le token actuel (pour les formulaires/JS), sans en créer un nouveau.
    public static function getToken(): ?string {
        return Session::get(self::$tokenName);
    }

    // Je valide le token soumis (POST ou JSON) avec celui en session.
    public static function validateToken(Request $request): bool {
        $submittedToken = null;
        $sessionToken = self::getToken();

        // Je récupère le token du body (formulaire ou JSON)
        $body = $request->getBody();
        if (isset($body[self::$tokenName])) {
            $submittedToken = $body[self::$tokenName];
        }

        if (!$submittedToken || !$sessionToken) {
            error_log("CSRF Validation Failed: Token missing in session or request.");
            return false; // Token manquant
        }

        if (!hash_equals($sessionToken, $submittedToken)) {
             error_log("CSRF Validation Failed: Token mismatch. Session: " . $sessionToken . " | Submitted: " . $submittedToken);
             return false; // Les tokens correspondent pas
        }


        return true; // Le token est valide
    }

    // Middleware : je protège les actions sensibles. Coupe si invalide (403).
    public static function protect(Request $request): void {
        if (!self::validateToken($request)) {
            http_response_code(403); // Interdit
            // Optionnel : réponse JSON pour l'API
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
            } else {
                // Pour les formulaires HTML
                Session::flash('error', 'Erreur de sécurité (CSRF). Veuillez réessayer.');
                // Je redirige vers la page d'avant si possible
                $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/login';
                 // J'évite une boucle
                if (strpos($referer, $_SERVER['REQUEST_URI']) === false) {
                   header('Location: ' . $referer);
                } else {
                   header('Location: ' . BASE_URL . '/login'); // Au cas où
                }
            }
            exit;
        }
    }

     // Je retourne le nom du champ du token
     public static function getTokenName(): string {
         return self::$tokenName;
     }
}
?> 