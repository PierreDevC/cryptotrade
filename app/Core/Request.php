<?php
// /cryptotrade/app/Core/Request.php
namespace App\Core;

class Request {
    public function getMethod() {
        return strtoupper($_SERVER["REQUEST_METHOD"]);
    }

    public function getUri() {
        $uri = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
        // Remove base directory if needed (adjust "cryptotrade" if your folder name is different)
        $baseDir = trim(parse_url(BASE_URL, PHP_URL_PATH), "/");
        if ($baseDir && strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }
        return trim($uri, "/");
    }

    // Get POST data or JSON body
    public function getBody() {
        $method = $this->getMethod();
        $body = [];

        // Handle standard POST form data first
        if ($method === "POST" && isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/x-www-form-urlencoded") !== false) {
            foreach($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        // Handle JSON input for POST, PUT, PATCH, DELETE etc.
        // Check if Content-Type indicates JSON
        if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
            $jsonInput = file_get_contents("php://input");
            $decoded = json_decode($jsonInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Merge JSON data with any existing form data (useful if both could co-exist, though unlikely with pure JSON APIs)
                // Using array_merge ensures JSON values overwrite potential POST values with the same key if CONTENT_TYPE is JSON.
                $body = array_merge($body, $decoded);
            }
        }
        // For GET requests, primarily return query parameters
        // (Note: getBody() might not be the ideal name for GET, but kept for consistency)
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
         // Note: This specifically checks $_POST, might not get JSON body data.
         // Consider using getBody()[$key] if you need data regardless of source.
         return isset($_POST[$key]) ? filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS) : $default;
     }
}
?>