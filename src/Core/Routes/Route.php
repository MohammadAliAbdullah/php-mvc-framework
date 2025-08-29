<?php

declare(strict_types=1);

namespace App\Core\Routes;


use App\Core\Controllers\Api\ApiClientController;
use App\Core\Controllers\AuthController;
use App\Core\Controllers\DashboardController;
use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Http\Request;
use App\Core\Middlewares\WebAuthMiddleware;
use App\Core\System\Event;


/**
 * A simple router that allows you to define routes via addRoute()
 * and matches an incoming Request to a controller and action.
 */
class Route
{
    /**
     * @var array<int,array{method:string,uri:string,controller:string,action:string}>
     */
    private array $routes = [];
    private array $currentRoute = [];

    /**
     * @var array<int,array{string, string, string, string, array, string}>
     */
    public array $routesList = [
        ['GET',  '/auth/login', AuthController::class, 'showLoginForm'],
        ['POST', '/auth/login', AuthController::class, 'login'],
        ['GET',  '/auth/register', AuthController::class, 'showRegistrationForm'],
        ['POST', '/auth/register', AuthController::class, 'register'],
        ['GET', '/auth/logout', AuthController::class, 'logout'],
        ['POST', '/api/register/client', ApiClientController::class, 'createClient',  [WebAuthMiddleware::class]],
        ['GET', '/dashboard', DashboardController::class, 'index',  [WebAuthMiddleware::class]]
    ];

    /**
     * Registers a new route in our internal list.
     *
     * @param string $method      HTTP verb (GET, POST, etc.)
     * @param string $uri         The path or endpoint (e.g., "/auth/login")
     * @param string $controller  Controller class (e.g., App\Controllers\AuthController::class)
     * @param string $action      Method on the controller (e.g., "login")
     */
    public function addRoute(
        string $method,
        string $uri,
        string $controller,
        string $action,
        array $middlewares = [],
        string | null $plugin = null
    ): void {
        $method = strtoupper($method);

        // Fetch the routePrefix dynamically from the controller
        $routePrefix = defined("$controller::routePrefix")
            ? $controller::routePrefix
            : '';

        // Concatenate the prefix and URI
        $fullUri = '/' . trim($routePrefix . '/' . $uri, '/');

        $this->routes[] = [
            'method'      => $method,
            'uri'         => rtrim($uri, '/'),
            'controller'  => $controller,
            'action'      => $action,
            'middlewares' => $middlewares,
            'plugin'      => $plugin
        ];
    }

    /**
     * Attempt to match the request to a stored route.
     * If no match, throw NotFoundHttpException.
     *
     * @return array{controller:string, action:string, middlewares:string[]}
     */
    public function match(Request $request): array
    {
        $requestMethod = $request->getMethod(); // e.g. "GET"
        $requestUri    = $request->getUri();    // e.g. "/auth/login"
        
        // For OPTIONS requests, we need to find a matching route for the actual method
        // that the preflight request is asking about
        if($requestMethod === 'OPTIONS') {
            $actualMethod = $request->header("Access-Control-Request-Method");
            if ($actualMethod) {
                $requestMethod = $actualMethod;
            }
        }

        if ($requestUri !== '/') {
            $requestUri = rtrim($requestUri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                // Convert route URI to regex
                $routePattern = preg_replace('/\{(\w+)\}/', '(?<$1>[^/]+)', $route['uri']);
                $routePattern = str_replace('/', '\/', $routePattern);
                if($requestUri === '/') $requestUri = '';
                $routePattern = '/^' . $routePattern . '$/';

                if (preg_match($routePattern, $requestUri, $matches)) {
                    $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $this->currentRoute = $route;
                    $this->currentRoute['parameters'] = $parameters;
                    return $this->currentRoute;
                }
            }
        }

        throw new NotFoundHttpException("No route found for [{$requestMethod} {$requestUri}]");
    }

    /**
     * Example usage to define all your routes.
     * Now we can pass an array of middlewares for specific routes.
     */
    public function registerRoutes(): void
    {
        // Auth related routes
        $routes = Event::trigger(Route::class, 'add-routes', $this->routesList);
        if(count($routes)) $this->routesList = $routes[0];
        foreach($this->routesList as $route) {
            $this->addRoute(...$route);
        }
    }

    /**
     * Get the module (controller) name from the current route.
     *
     * @return string
     */
    public function getModule(): string {
        return $this->currentRoute['controller'] ?? '';
    }

    /**
     * Get the action name from the current route.
     *
     * @return string
     */
    public function getAction(): string {
        return $this->currentRoute['action'] ?? '';
    }

    /**
     * Get the current route URI.
     *
     * @return string
     */
    public function getCurrent(): string {
        return $this->currentRoute['uri'] ?? '';
    }
}
