<?php

declare(strict_types=1);

namespace App\Core\App;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Providers\AppServiceProvider;
use App\Core\Providers\AuthServiceProvider;
use App\Core\Providers\CsrfServiceProvider;
use App\Core\Providers\DatabaseConnectionProvider;
use App\Core\Providers\ExceptionServiceProvider;
use App\Core\Routes\Route;
use App\Core\System\Config;
use App\Core\System\ConfigurationsLoader;
use App\Core\System\Event;
use App\Core\System\Extensions\Plugin;
use App\Core\System\Session;
use App\Core\View\View;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Throwable;

/**
 * The Kernel is responsible for:
 * 1. Managing the IoC Container (Illuminate\Container\Container).
 * 2. Registering/booting providers (where your bindings and config live).
 * 3. Handling the HTTP Request, including middleware, routing, and error handling.
 */
class Kernel
{
    /**
     * The global IoC Container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * An array of providers (like Laravel's service providers) to register.
     *
     * @var string[]
     */
    protected array $providers = [
        DatabaseConnectionProvider::class,
        AppServiceProvider::class,
        ExceptionServiceProvider::class,
        AuthServiceProvider::class,
        CsrfServiceProvider::class
    ];

    /**
     * An array of middleware classes that should be run on every request.
     *
     * @var string[]
     */
    protected array $globalMiddleware = [
         \App\Core\Middlewares\GlobalMiddleware::class,
    ];

    protected array $responseMiddleware = [
        \App\Core\Middlewares\CorsMiddleware::class,
    ];

    public function __construct()
    {
        // Get the global instance of the container (Singleton).
        $this->container = Container::getInstance();

        // Optionally set the instance to this container:
        Container::setInstance($this->container);

        // Perform the bootstrapping tasks.
        $this->bootstrap();
    }

    /**
     * Bootstraps the application:
     * 1. Register service providers (where your bindings, singletons, etc. are defined).
     * 2. Load config if needed.
     */
    protected function bootstrap(): void
    {
        // Load config (if you have a config repository, load it here).
        $this->loadConfiguration();


        // Register core services as singletons
        $this->container->singleton(Request::class, function () {
            return new Request($this->container);
        });

        $this->container->singleton(Response::class, function () {
            return new Response();
        });

        $this->container->singleton(Session::class, function () {
            return new Session();
        });

        $this->container->singleton(View::class, function () {
            return new View();
        });

        $this->container->singleton(Route::class, function () {
            return new Route();
        });
        $this->container->singleton(Plugin::class, function (){
            return new Plugin();
        });

        $this->loadPlugins();

        $this->loadProvidersList();

        // Register all providers. Each provider can bind or singleton classes in the container.
        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass($this->container);
            $provider->register();
        }
        $this->registerRoutes();

        //Hook response middleware through event. 
        //The response middleware will be executed after the route middleware and the controller but before the response is sent to the client.
        // Event::trigger(Kernel::class, 'add-response-middleware', $this->responseMiddleware);
    }

    /**
     */
    protected function loadPlugins(): void
    {
        try {
            $pluginSystem = $this->container->make(Plugin::class);
            $pluginSystem->loadAllActivePlugins();
        } catch (BindingResolutionException $e) {
        }
    }
    /**
     * Load environment variables from .env file
     */
    protected function loadEnvironmentVariables(): void
    {   
        // Load environment variables from .env file
        $envFile = ROOT_DIR . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments and empty lines
                if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
                    continue;
                }
                
                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                        $value = $matches[2];
                    }
                    
                    // Set environment variable if not already set
                    if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                        putenv("$key=$value");
                    }
                }
            }
        }
    }
    /**
     * Load any configuration files (placeholder).
     */
    protected function loadConfiguration(): void
    {
          // Load .env variables first
        $this->loadEnvironmentVariables();

        $loader = new ConfigurationsLoader();
        $configurations = $loader->loadConfiguration();

          // Merge environment variables into configurations
        $configurations = array_merge($configurations, $_ENV);

        // Bind the entire configuration array for convenience
        $this->container->instance('config', $configurations);

        // Inject the Config instance into the container for usage across the application
        $this->container->singleton(Config::class, function () {
            return Config::getInstance();
        });
    }

    /**
     * Load service provider from plugins
     */
    protected function loadProvidersList(): void
    {
        $providers = Event::trigger(Kernel::class, 'add-providers', $this->providers);
        if(count($providers)) $this->providers = $providers[0];
    }

    protected function registerRoutes(): void
    {
        try {
            $router = $this->container->make(Route::class);
            $router->registerRoutes();
        } catch (BindingResolutionException $e) {

        }
    }

    /**
     * Handle the incoming Request, run global middleware,
     * dispatch to a Route, and return the Response.
     *
     * @return Response
     */
    public function handle(): Response
    {
        $request = null;
        try {
            $request = $this->container->make(Request::class);
            // 1. Run global middleware stack. Each middleware can manipulate the Request or stop the pipeline.
            $response = $this->runGlobalMiddleware($request);
            if ($response) {
                // If a middleware returned a Response early, short-circuit.
                return $this->runResponseMiddleware($request, $response, $this->responseMiddleware);
            }

            // 2. Resolve and run the router. The router decides which controller@method to call.
            /** @var Route $router */
            $router = $this->container->make(Route::class);
            $routeInfo = $router->match($request);

            // 3. Resolve the chosen controller from the container (autowiring).
            $controllerClass = $routeInfo['controller'];
            $controllerMethod = $routeInfo['action'];
            $routeMiddlewares= $routeInfo['middlewares']; // <--- route-specific middlewares
            $routePlugin= $routeInfo['plugin']; // <--- route-specific middlewares
            if($routePlugin) $request->setAttribute('plugin', $routePlugin);

            // 3. Run route-specific middleware
            $response = $this->runRouteMiddleware($request, $routeMiddlewares);
            if ($response) {
                return $this->runResponseMiddleware($request, $response, $this->responseMiddleware); // short-circuit if middleware returns a Response
            }

            $controllerInstance = $this->container->make($controllerClass);

            // 4. Invoke the controller method with the Request, capturing a Response.
            /** @var Response $response */
            $response = \call_user_func([$controllerInstance, $controllerMethod], $request, ...$routeInfo['parameters']);
            $response = $this->runResponseMiddleware($request, $response, $this->responseMiddleware);

            return $response;
        } catch (BindingResolutionException $e) {
            // Here you can gracefully handle resolution errors:
            error_log($e->getMessage());
            $response = new Response('Dependency resolution error: '.$e->getMessage(), 500);
            return $this->runResponseMiddleware($request, $response, $this->responseMiddleware);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        } catch(\PDOException $e){
            return $this->handleException($e, $request);
        }catch(\Exception $e){
            return $this->handleException($e, $request);
        }
    }

    /**
     * Runs each of the global middleware classes in sequence.
     * If any middleware returns a Response, we stop and return that Response immediately.
     *
     * @param Request $request
     * @return Response|null
     */
    protected function runGlobalMiddleware(Request $request): ?Response
    {
        foreach ($this->globalMiddleware as $middlewareClass) {
            $middleware = $this->container->make($middlewareClass);
            if (!method_exists($middleware, 'handle')) {
                continue;
            }

            /** @var Response|null $possibleResponse */
            $possibleResponse = $middleware->handle($request, function (Request $request) {
                // The "next" callback is effectively a no-op here, since we continue in the loop
                return null;
            });

            // If a middleware returns a Response, short-circuit
            if ($possibleResponse instanceof Response) {
                return $possibleResponse;
            }
        }

        return null;
    }
    /**
     * Run each route-specific middleware in sequence.
     *
     * @param Request $request
     * @param string[] $routeMiddlewares Array of middleware class names
     * @return Response|null
     */
    protected function runRouteMiddleware(Request $request, array $routeMiddlewares): ?Response
    {
        foreach ($routeMiddlewares as $middlewareClass) {
            $middleware = $this->container->make($middlewareClass);

            // We expect the middleware to have a ->handle($request, $next) signature
            if (!method_exists($middleware, 'handle')) {
                continue; // or throw an exception
            }

            $possibleResponse = $middleware->handle($request, function (Request $req) {
                // This "next" callback doesn't chain the next route middleware,
                // so we keep it minimal or restructure for a pipeline.
                return null;
            });

            // If a middleware returns a Response, short-circuit.
            if ($possibleResponse instanceof Response) {
                return $possibleResponse;
            }
        }

        return null;
    }

    protected function runResponseMiddleware(?Request $request, Response $response, array $middlewares): Response
    {
        foreach ($middlewares as $middlewareClass) {
            $middleware = $this->container->make($middlewareClass);

            // We expect the middleware to have a ->handle($request, $response, $next) signature
            if (!method_exists($middleware, 'handle')) {
                continue; // or throw an exception
            }

            $response = $middleware->handle($request, $response, function (?Request $req, Response $res) {
                // Return the response so middleware can continue processing
                return $res;
            });
        }

        return $response;
    }

    /**
     * Global exception handling. You can log the error, display a custom error view, etc.
     *
     * @param Throwable $e
     * @param Request|null $request
     * @return Response
     */
    protected function handleException(Throwable $e, ?Request $request): Response
    {
        // Log the exception for debugging
        error_log("Exception in Kernel::handleException: " . $e->getMessage());
        error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Create a new Response instance
        $response = new Response();
        
        // Handle PDOException specifically
        if ($e instanceof \PDOException) {
            error_log("PDOException caught: " . $e->getMessage());
            
            if ($this->isDebugMode()) {
                $exceptionDetails = '<h1>Database Error</h1>';
                $exceptionDetails .= '<strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
                $exceptionDetails .= '<strong>Code:</strong> ' . $e->getCode() . '<br>';
                $exceptionDetails .= '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' (Line ' . $e->getLine() . ')<br>';
                $exceptionDetails .= '<strong>Stack Trace:</strong><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';

                $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'text/html; charset=UTF-8')
                    ->withBody($exceptionDetails);
            } else {
                $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(json_encode([
                        'error' => 'Database Error',
                        'message' => 'A database error occurred'
                    ]));
            }
        } else {
            // Handle other exceptions
            if ($this->isDebugMode()) {
                $exceptionDetails = '<h1>Exception Occurred</h1>';
                $exceptionDetails .= '<strong>Class:</strong> ' . get_class($e) . '<br>';
                $exceptionDetails .= '<strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
                $exceptionDetails .= '<strong>Code:</strong> ' . $e->getCode() . '<br>';
                $exceptionDetails .= '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' (Line ' . $e->getLine() . ')<br>';
                $exceptionDetails .= '<strong>Stack Trace:</strong><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';

                $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'text/html; charset=UTF-8')
                    ->withBody($exceptionDetails);
            } else {
                $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(json_encode([
                        'error' => 'Internal Server Error',
                        'message' => 'Something went wrong'
                    ]));
            }
        }

        // Always apply response middleware to ensure CORS headers are set
        // This is crucial - it ensures CORS headers are added to error responses
        $response = $this->runResponseMiddleware($request, $response, $this->responseMiddleware);
        
        return $response;
    }

    /**
     * Determine if the application is in debug mode.
     *
     * @return bool
     */
    protected function isDebugMode(): bool
    {
        return  true;
        // Example: Check an environment variable or configuration setting
//        return getenv('APP_DEBUG') === 'true';
    }
}
