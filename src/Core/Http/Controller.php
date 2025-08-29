<?php

namespace App\Core\Http;

use App\Core\Exceptions\AccessDeniedHttpException;
use App\Core\Exceptions\BadRequestHttpException;
use App\Core\Exceptions\ConflictHttpException;
use App\Core\Exceptions\GoneHttpException;
use App\Core\Exceptions\HttpExceptionInterface;
use App\Core\Exceptions\InternalServerErrorHttpException;
use App\Core\Exceptions\MethodNotAllowedHttpException;
use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Exceptions\ServiceUnavailableHttpException;
use App\Core\Exceptions\TooManyRequestsHttpException;
use App\Core\Exceptions\UnauthorizedHttpException;
use App\Core\Exceptions\ValidationException;
use App\Core\Routes\Route;
use App\Core\System\Session;
use App\Core\View\View;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use function App\Core\System\utils\app;

abstract class Controller {
    protected Request $request;
    protected Response $response;
    protected Session $session;
    protected View $view;
    protected Route $route;

    /**
     * @throws BindingResolutionException
     */
    public function __construct() {
        $this->view = app(View::class);
        $controller = strtolower(basename(str_replace('\\', '/',$this::class)));
        $this->view->controller = preg_replace('/controller/', '', $controller);
        $this->request = app(Request::class);
        $this->response = app(Response::class);
        $this->session = app(Session::class);
        $this->route = app(Route::class);
    }

    /**
     * Get the current module name.
     *
     * @return string
     */
    protected function getModuleName(): string {
        return $this->route->getModule();
    }

    /**
     * Get the current action name.
     *
     * @return string
     */
    protected function getActionName(): string {
        return $this->route->getAction();
    }

    /**
     * Render a view and return the content as a string.
     *
     * @param string $template The template name.
     * @param array $variables Variables to pass to the view.
     * @return string Rendered HTML content.
     * @throws Exception
     */
    protected function renderView(string $template, array $variables = []): string {
        return $this->view->render($template, $variables);
    }

    /**
     * Redirect to a specified URI.
     *
     * @param string $url The URL to redirect to.
     * @param int $statusCode HTTP status code for the redirection.
     * @return Response The redirect response.
     */
    protected function redirect(string $url, int $statusCode = 302): Response {
        return $this->response->withHeader('Location', $url)->withStatus($statusCode);
    }

    /**
     * Handle a 404 Not Found scenario.
     *
     * @return Response The 404 response.
     * @throws Exception
     */
    protected function notFound(): Response {
        $content = $this->view->render('errors/404');
        return $this->response->withStatus(404)->withBody($content);
    }

    /**
     * Call a specific action dynamically with arguments.
     *
     * @param string $action The action method name.
     * @param array $args Arguments to pass to the method.
     * @return Response The result of the action call.
     * @throws Exception
     */
    protected function callAction(string $action, array $args = []): Response
    {
        if (!method_exists($this, $action)) {
            throw new Exception("Action $action does not exist.");
        }

        return call_user_func_array([$this, $action], $args);
    }

    /**
     * Get the current route URI.
     *
     * @return string
     */
    protected function getRoute(): string {
        return $this->route->getCurrent();
    }


    protected function handleException(Exception $e, string $defaultMessage = 'An error occurred.'): Response
    {
        die($e);
        $statusCode = 500; // Default to Internal Server Error
        $exception = $e;
        // Check for HttpExceptionInterface and handle accordingly
        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
            $headers = $e->getHeaders();
            $message = $e->getMessage() ?: $defaultMessage;

            // Create the response with appropriate headers
            $response = $this->response
                ->withStatus($statusCode)
                ->withBody($message);

            foreach ($headers as $name => $value) {
                $response = $response->withHeader($name, $value);
            }

            return $response;
        }else if(method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage() ?: $defaultMessage;

            // Determine the exception class based on the status code
            $exception = match ($statusCode) {
                400 => new BadRequestHttpException($message, $e, $statusCode),
                401 => new UnauthorizedHttpException($message, $e),
                403 => new AccessDeniedHttpException($message, $e),
                404 => new NotFoundHttpException($message, $e),
                405 => new MethodNotAllowedHttpException([], $message, $e),
                409 => new ConflictHttpException($message, $e),
                410 => new GoneHttpException($message, $e),
                422 => new ValidationException(json_decode($message, true), $message, $statusCode),
                429 => new TooManyRequestsHttpException($message, $e),
                503 => new ServiceUnavailableHttpException(0, $message, $e),
                default => new InternalServerErrorHttpException($message, $e),
            };
        }
        return $this->response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(json_encode([
                'message' => $exception->getMessage()??$defaultMessage,
                'status' => $statusCode,
                'details' => method_exists($exception, 'getHeaders') ? $exception->getHeaders() : [],
            ]));
    }

    /**
     * Render a view and create a response.
     */
    protected function renderResponse(string $template, array $variables = []): Response
    {
        $plugin = $this->request->getAttribute('plugin')??'';
        try {
            $content = $this->view->render($template, $variables, $plugin);
            return $this->response
                ->withStatus(200)
                ->withBody($content);
        } catch (Exception $e) {
           return $this->handleException($e);
        }
    }
}
