<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Exceptions\ValidationException;
use Illuminate\Container\Container;
use PDO;
use PDOException;

/**
 * A basic Request class that encapsulates data from the global
 * PHP superglobals. It allows easy retrieval of HTTP method, URI,
 * query parameters, POST data, files, etc.
 */
class Request
{
    /**
     * Arbitrary data storage for middleware/controllers.
     *
     * @var array<string,mixed>
     */
    public array $attributes = [];

    /**
     * @var array<string,mixed> $query   Typically from $_GET
     */
    private array $query;
    /**
     * @var array<string,mixed> $post    Typically from $_POST
     */
    private array $post;
    /**
     * @var array<string,mixed> $server  Typically from $_SERVER
     */
    private array $server;
    /**
     * @var array<string,mixed> $cookies Typically from $_COOKIE
     */
    private array $cookies;
    /**
     * @var array<string,mixed> $files   Typically from $_FILES
     */
    private array $files;

    /**
     * @var array<string,mixed> $request   Typically from $_REQUEST
     */
    public array $request;

    protected static self $instance;

    private Container $container;

    public function __construct(
        Container $container
    ) {
        $this->query     = &$_GET;
        $this->post    = &$_POST;
        $this->server = &$_SERVER;
        $this->cookies  = &$_COOKIE;
        $this->files   = &$_FILES;
        $this->request  = &$_REQUEST;
        $this->container = $container;
        if ($this->header('Content-Type') === 'application/json') {
            $jsonInput = file_get_contents('php://input');
            $decodedJson = json_decode($jsonInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->post = array_merge($this->post, $decodedJson);
            }
        }
    }

    public static function getInstance(): Request
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Factory method to create a Request from global PHP superglobals.
     */
    public static function capture(): self
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Retrieve the HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Retrieve the request URI (path only, without query string).
     */
    public function getUri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        // Remove any query string part (e.g. '?foo=bar')
        $uri = explode('?', $uri)[0];
        // Ensure we have a leading slash
        if ($uri === '') {
            $uri = '/';
        }
        return $uri;
    }

    /**
     * Retrieve a POST input value by key, with optional default.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Return an associative array of all POST data.
     *
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->post;
    }

    /**
     * Retrieve a query (GET) parameter by key, with optional default.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    // modify abdullah 29/08/2025 string to ?string (nullable string)
    public function query(?string $key = null, mixed $default = null): mixed 
    {
        return $key ? $this->query[$key] ?? $default : $this->query;
    }

    /**
     * Return all query parameters as an associative array.
     *
     * @return array<string,mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Retrieve a cookie value by key, with optional default.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Retrieve a header by name (e.g., "Content-Type").
     * Note: HTTP headers are typically in $_SERVER as 'HTTP_<NAME>'.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function header(string $name, mixed $default = null): mixed
    {
        // Convert a header name like "Content-Type" to "HTTP_CONTENT_TYPE"
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? $default;
    }

    /**
     * Retrieve a file array (if present).
     *
     * @param  string $key
     * @return array<string,mixed>|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): ?array
    {
        return $this->files;
    }

    /**
     * Retrieve the raw server array if needed.
     *
     * @return array<string,mixed>
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * Return an associative array of all HTTP headers.
     * E.g. ['Host' => 'example.com', 'Authorization' => 'Bearer xyz', ...].
     */
    public function getHeaders(): array
    {
        $headers = [];

        // Loop through $this->server looking for HTTP_ keys.
        foreach ($this->server as $key => $value) {
            // Common approach: check if it starts with "HTTP_" (typical for HTTP headers in $_SERVER).
            if (str_starts_with($key, 'HTTP_')) {
                // Convert HTTP_HEADER_NAME to Header-Name
                $headerName = str_replace(
                    ' ',
                    '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($key, 5))))
                );
                $headers[$headerName] = $value;
            }
        }

        // Handle a few special cases like "CONTENT_TYPE" and "CONTENT_LENGTH", which
        // don't come prefixed with "HTTP_"
        if (isset($this->server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $this->server['CONTENT_TYPE'];
        }
        if (isset($this->server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $this->server['CONTENT_LENGTH'];
        }

        return $headers;
    }
    /**
     * Get an attribute by name.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Set an attribute by name.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Validates the request input against the given rules.
     *
     * @param array<string, string> $rules Validation rules for the input data.
     * @return array<string, mixed> The validated data.
     * @throws ValidationException If validation fails.
     */
    public function validate(array $rules): array
    {
        $validatedData = [];
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $this->input($field);
            $rulesArray = explode('|', $ruleString);

            foreach ($rulesArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;

                $isValid = match ($ruleName) {
                    'required' => $value !== null && $value !== "",
                    'string' => is_string($value) || $this->canSkipValidation($field, $rulesArray),
                    'int' => filter_var($value, FILTER_VALIDATE_INT) !== false || $this->canSkipValidation($field, $rulesArray),
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false || $this->canSkipValidation($field, $rulesArray),
                    'url' => filter_var($value, FILTER_VALIDATE_URL) !== false || $this->canSkipValidation($field, $rulesArray),
                    'min' => is_string($value) && strlen($value) >= (int)$ruleParam || $this->canSkipValidation($field, $rulesArray),
                    'same' => $value === $this->input($ruleParam) || $this->canSkipValidation($field, $rulesArray),
                    'array' => is_array($value) || $this->canSkipValidation($field, $rulesArray),
                    'exists' => $this->exists($value, $ruleParam) || $this->canSkipValidation($field, $rulesArray),
                    default => true, // Unknown rule, ignore
                };

                if (!$isValid) {
                    $errors[$field][] = "The $field field failed the $ruleName validation.";
                }
            }

            if (!isset($errors[$field]) && array_key_exists($field, $this->post)) {
                $validatedData[$field] = $value;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validatedData;
    }
    /**
     * Check if a value exists in a table.
     *
     * @param string|int|mixed $value The value to check.
     * @param string $table The table to check.
     * @return bool True if the value exists, false otherwise.
     * @throws PDOException If there is an error executing the query.
     */
    public function exists(mixed $value, string $ruleParam): bool
    {
        [$table, $column] = explode(',', $ruleParam);
        // Sanitize table and column names to prevent SQL injection
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        try {
            $db = $this->container->make(PDO::class);
            $query = "SELECT COUNT(*) FROM $table WHERE $column = ?";
            // Prepare the statement
            $stmt = $db->prepare($query);

            // Bind the parameter
            $stmt->bindParam(1, $value);

            // Execute the query
            $stmt->execute();

            // Fetch the result
            $result = $stmt->fetchColumn();

            return $result > 0;
        } catch (PDOException $e) {
            // Log the error or handle it as needed
            error_log($e->getMessage());
            return false;
        }
    }
    public function canSkipValidation(string $field, array $rulesArray): bool
    {
        return (!in_array('required', $rulesArray) && !array_key_exists($field, $this->post))
            || (in_array('nullable', $rulesArray) && array_key_exists($field, $this->post));
    }
}
