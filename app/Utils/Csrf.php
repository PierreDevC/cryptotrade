<?php
// /cryptotrade/app/Utils/Csrf.php
namespace App\Utils;

use App\Core\Session;
use App\Core\Request; // Ajout pour récupérer le token de la requête

class Csrf {
    private static $tokenName = '_csrf_token';

    /**
     * Génère un token CSRF s'il n'existe pas déjà en session, et le retourne.
     *
     * @return string Le token CSRF.
     */
    public static function generateToken(): string {
        if (!Session::has(self::$tokenName)) {
            $token = bin2hex(random_bytes(32)); // Génère un token sécurisé
            Session::set(self::$tokenName, $token);
        }
        return Session::get(self::$tokenName);
    }

    /**
     * Retourne le token CSRF actuel sans en générer un nouveau.
     * Utile pour l'injecter dans les formulaires/JS.
     *
     * @return string|null Le token CSRF ou null s'il n'existe pas.
     */
    public static function getToken(): ?string {
        return Session::get(self::$tokenName);
    }

    /**
     * Valide le token CSRF soumis dans la requête (POST ou JSON body)
     * contre celui stocké en session.
     *
     * @param Request $request L'objet Request pour accéder aux données soumises.
     * @return bool True si le token est valide, False sinon.
     */
    public static function validateToken(Request $request): bool {
        $submittedToken = null;
        $sessionToken = self::getToken();

        // Récupérer le token depuis le corps de la requête (formulaire ou JSON)
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
             return false; // Les tokens ne correspondent pas
        }


        return true; // Le token est valide
    }

    /**
     * Middleware de validation à appeler au début des méthodes de contrôleur sensibles.
     * Interrompt l'exécution avec un 403 si la validation échoue.
     *
     * @param Request $request L'objet Request.
     */
    public static function protect(Request $request): void {
        if (!self::validateToken($request)) {
            http_response_code(403); // Forbidden
            // Optionnel: Renvoyer une réponse JSON pour les requêtes API
            if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
                 header('Content-Type: application/json');
                 echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
            } else {
                // Pour les requêtes non-API (formulaire HTML)
                Session::flash('error', 'Erreur de sécurité (CSRF). Veuillez réessayer.');
                // Tenter de rediriger vers la page précédente si possible, sinon une page par défaut.
                $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/login';
                 // Basic loop prevention
                if (strpos($referer, $_SERVER['REQUEST_URI']) === false) {
                   header('Location: ' . $referer);
                } else {
                   header('Location: ' . BASE_URL . '/login'); // Fallback
                }
            }
            exit;
        }
    }

     /**
      * Retourne le nom du champ/clé utilisé pour le token CSRF.
      *
      * @return string
      */
     public static function getTokenName(): string {
         return self::$tokenName;
     }
}
?> 