<?php
// /cryptotrade/app/Core/Router.php
namespace App\Core;

class Router {
    protected $routes = [
        'GET' => [],
        'POST' => []
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

    private function normalizeUri($uri) {
        return trim($uri, '/');
    }

    public function dispatch() {
        $uri = $this->request->getUri();
        $method = $this->request->getMethod();

        $matchedRoute = null;
        $params = [];

        foreach ($this->routes[$method] as $routeUri => $controllerAction) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $routeUri);
            $pattern = '#^' . $pattern . '$#'; // Add start/end anchors

            if (preg_match($pattern, $uri, $matches)) {
                // Check if the route definition had parameters
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $routeUri, $paramNames);

                // Remove full match ($matches[0])
                array_shift($matches);

                // Map matched values to parameter names
                if (!empty($paramNames[1])) {
                   if(count($matches) == count($paramNames[1])) {
                        $params = array_combine($paramNames[1], $matches);
                   } else {
                       // Handle mismatch if necessary, maybe log an error
                       continue; // Or skip this route
                   }
                } else {
                     $params = [];
                }


                $matchedRoute = $controllerAction;
                break; // Found a match, stop searching
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
            // It's a closure
            call_user_func_array($controllerAction, $params);
        } elseif (is_string($controllerAction) && strpos($controllerAction, '@') !== false) {
             // It's 'Controller@method'
             list($controller, $method) = explode('@', $controllerAction);

             // Basic check if class exists
             if (!class_exists($controller)) {
                 error_log("Controller class not found: {$controller}");
                 $this->handleNotFound(); // Or a server error
                 return;
             }

             $controllerInstance = new $controller(Database::getInstance()->getConnection()); // Inject DB connection

             // Basic check if method exists
             if (!method_exists($controllerInstance, $method)) {
                 error_log("Method not found: {$controller}@{$method}");
                 $this->handleNotFound(); // Or a server error
                 return;
             }

             // Call the controller method, passing parameters
             call_user_func_array([$controllerInstance, $method], [$params]);

         } else {
              error_log("Invalid route action defined: " . print_r($controllerAction, true));
              $this->handleServerError();
         }
    }

    protected function handleNotFound() {
        http_response_code(404);
        // You could render a nice 404 page here
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