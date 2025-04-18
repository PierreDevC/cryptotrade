<?php
// /cryptotrade/app/Core/Request.php
namespace App\Core;

/**
 * Développeur assignés(s) : Pierre
 * Entité : Classe 'Request' de la couche Core
 */

class Request {
    public function getMethod() {
        return strtoupper($_SERVER["REQUEST_METHOD"]);
    }

    public function getUri() {
        $uri = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
        // J'enlève le répertoire de base si besoin (ajuster "cryptotrade" si nécessaire)
        $baseDir = trim(parse_url(BASE_URL, PHP_URL_PATH), "/");
        if ($baseDir && strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }
        return trim($uri, "/");
    }

    // Je récupère les données POST ou le corps JSON
    public function getBody() {
        $method = $this->getMethod();
        $body = [];

        // D'abord, je gère les données de formulaire POST classiques
        if ($method === "POST" && isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/x-www-form-urlencoded") !== false) {
            foreach($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        // Je gère l'entrée JSON pour POST, PUT, PATCH, DELETE, etc.
        // Je vérifie si Content-Type est JSON
        if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
            $jsonInput = file_get_contents("php://input");
            $decoded = json_decode($jsonInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Je fusionne les données JSON (écrase les clés POST si Content-Type est JSON)
                $body = array_merge($body, $decoded);
            }
        }
        // Pour GET, je renvoie les paramètres de la query (getBody() n'est peut-être pas le meilleur nom ici)
        elseif ($method === "GET") {
            foreach($_GET as $key => $value) {
                 $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        return $body;
    }

     public function getQueryParam($key, $default = null) {
         return isset($_GET[$key]) ? filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS) : $default;
     }

      public function getPostParam($key, $default = null) {
         // Attention: ceci vérifie $_POST spécifiquement.
         // Utiliser getBody()[$key] si besoin des données peu importe la source.
         return isset($_POST[$key]) ? filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS) : $default;
     }
}
?>