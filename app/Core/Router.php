<?php
// /cryptotrade/app/Core/Router.php
namespace App\Core;

/**
 * Développeur assignés(s) : Pierre
 * Entité : Classe 'Router' de la couche Core
 */

class Router {
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'DELETE' => []
    ];
    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function get($uri, $controllerAction) {
        $this->routes['GET'][$this->normalizeUri($uri)] = $controllerAction;
    }

    public function post($uri, $controllerAction) {
        $this->routes['POST'][$this->normalizeUri($uri)] = $controllerAction;
    }

    public function delete($uri, $controllerAction) {
        $this->routes['DELETE'][$this->normalizeUri($uri)] = $controllerAction;
    }

    private function normalizeUri($uri) {
        return trim($uri, '/');
    }

    public function dispatch() {
        $uri = $this->request->getUri();
        $method = $this->request->getMethod();

        if (!isset($this->routes[$method])) {
            $this->handleNotFound();
            return;
        }

        $matchedRoute = null;
        $params = [];

        foreach ($this->routes[$method] as $routeUri => $controllerAction) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $routeUri);
            $pattern = '#^' . $pattern . '$#'; // J'ajoute les ancres de début/fin

            if (preg_match($pattern, $uri, $matches)) {
                // Je vérifie si la route attendait des paramètres
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $routeUri, $paramNames);

                // J'enlève la correspondance complète ($matches[0])
                array_shift($matches);

                // J'associe les valeurs trouvées aux noms des paramètres
                if (!empty($paramNames[1])) {
                   if(count($matches) == count($paramNames[1])) {
                        $params = array_combine($paramNames[1], $matches);
                   } else {
                       // Si ça colle pas, je pourrais loguer une erreur
                       continue; // Ou je zappe cette route
                   }
                } else {
                     $params = [];
                }


                $matchedRoute = $controllerAction;
                break; // Trouvé, j'arrête de chercher
            }
        }


        if ($matchedRoute) {
            $this->callAction($matchedRoute, $params);
        } else {
            $this->handleNotFound();
        }
    }

    protected function callAction($controllerAction, $params = []) {
         if (is_callable($controllerAction)) {
            // C'est une closure
            call_user_func_array($controllerAction, $params);
        } elseif (is_string($controllerAction) && strpos($controllerAction, '@') !== false) {
             // C'est 'Controller@method'
             list($controller, $method) = explode('@', $controllerAction);

             // Petite vérif si la classe existe
             if (!class_exists($controller)) {
                 error_log("Controller class not found: {$controller}");
                 $this->handleNotFound(); // Ou une erreur serveur
                 return;
             }

             $controllerInstance = new $controller(Database::getInstance()->getConnection()); // J'injecte la connexion BDD

             // Petite vérif si la méthode existe
             if (!method_exists($controllerInstance, $method)) {
                 error_log("Method not found: {$controller}@{$method}");
                 $this->handleNotFound(); // Ou une erreur serveur
                 return;
             }

             // J'appelle la méthode du contrôleur avec les params
             call_user_func_array([$controllerInstance, $method], [$params]);

         } else {
              error_log("Invalid route action defined: " . print_r($controllerAction, true));
              $this->handleServerError();
         }
    }

    protected function handleNotFound() {
        http_response_code(404);
        // Ici, je pourrais afficher une jolie page 404
        echo "404 Not Found";
        exit;
    }

     protected function handleServerError() {
        http_response_code(500);
        echo "500 Internal Server Error";
        exit;
    }
}
?>