<?php
// /cryptotrade/app/Core/Request.php
namespace App\Core;

class Request {
    public function getMethod() {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getUri() {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        // Remove base directory if needed (adjust 'cryptotrade' if your folder name is different)
        $baseDir = trim(parse_url(BASE_URL, PHP_URL_PATH), '/');
        if ($baseDir && strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }
        return trim($uri, '/');
       // return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }

    // Get POST data or JSON body
    public function getBody() {
        if ($this->getMethod() === 'POST') {
             // Handle form data
             $body = [];
             foreach($_POST as $key => $value) {
                 $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
             }

             // Handle JSON input if Content-Type is application/json
             if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
                 $jsonInput = file_get_contents('php://input');
                 $decoded = json_decode($jsonInput, true);
                 if (json_last_error() === JSON_ERROR_NONE) {
                     // Merge or replace based on your preference. Merging is safer.
                     $body = array_merge($body, $decoded);
                 }
             }
             return $body;
        }
         // For GET requests, return query parameters
         if ($this->getMethod() === 'GET') {
             $body = [];
             foreach($_GET as $key => $value) {
                  $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
             }
             return $body;
         }
        return []; // Return empty for other methods or if no data
    }

     public function getQueryParam($key, $default = null) {
         return isset($_GET[$key]) ? filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS) : $default;
     }

      public function getPostParam($key, $default = null) {
         return isset($_POST[$key]) ? filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS) : $default;
     }
}
?>