<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Exceptions\HttpExceptionInterface;
use Throwable;

/**
 * A simple Response class for sending an HTTP response.
 * Also includes a static helper to create a Response from an exception.
 */
class Response
{
    public function __construct(
        private string $content = '',
        private int $statusCode = 200,
        private array $headers = []
    ) {
    }
    /**
     * Set the HTTP status code.
     *
     * @param int $statusCode
     * @return self
     */
    public function withStatus(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    /**
     * Add or update a header.
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set the response body.
     *
     * @param string $body
     * @return self
     */
    public function withBody(string $body): self
    {
        $this->content = $body;
        return $this;
    }

    /**
     * Sets the response content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Gets the response content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the status code for the response (e.g. 200, 404, 500).
     */
    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    /**
     * Gets the current status code for the response.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets headers as an associative array: ['Content-Type' => 'text/html'].
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Adds or overrides a single header.
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Retrieves all headers as an associative array.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Check if specific header exists
     */
    public function hasHeader(string $headerName): bool
    {
        return isset($this->headers[$headerName]);
    }

    /**
     * Get specific header value
     */
    public function getHeader(string $headerName): ?string
    {
        return $this->headers[$headerName] ?? null;
    }

    /**
     * Comprehensive native headers debugging method
     */
    public function debugNativeHeaders(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "NATIVE HEADERS DEBUGGING\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // 1. Check if headers have been sent
        echo "1. HEADERS SENT STATUS:\n";
        $sent = headers_sent($file, $line);
        echo "Headers sent: " . ($sent ? 'YES' : 'NO') . "\n";
        if ($sent) {
            echo "Sent from file: " . $file . "\n";
            echo "Sent at line: " . $line . "\n";
        }
        echo "\n";
        
        // 2. Request headers (what client sent)
        echo "2. REQUEST HEADERS:\n";
        if (function_exists('getallheaders')) {
            $requestHeaders = getallheaders();
            if (empty($requestHeaders)) {
                echo "(no request headers found)\n";
            } else {
                foreach ($requestHeaders as $name => $value) {
                    echo "$name: $value\n";
                }
            }
        } else {
            echo "getallheaders() not available\n";
        }
        echo "\n";
        
        // 3. Server headers (from $_SERVER)
        echo "3. SERVER HEADERS (\$_SERVER):\n";
        $serverHeaders = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $serverHeaders[$key] = $value;
            }
        }
        if (empty($serverHeaders)) {
            echo "(no server headers found)\n";
        } else {
            foreach ($serverHeaders as $key => $value) {
                echo "$key: $value\n";
            }
        }
        echo "\n";
        
        // 4. Sent headers (what we've sent to browser)
        echo "4. SENT HEADERS:\n";
        $sentHeaders = headers_list();
        if (empty($sentHeaders)) {
            echo "(no headers sent yet)\n";
        } else {
            foreach ($sentHeaders as $header) {
                echo "$header\n";
            }
        }
        echo "\n";
        
        // 5. CORS headers specifically
        echo "5. CORS HEADERS:\n";
        
        // Check CORS headers that are set to be sent
        echo "CORS Headers Set to be Sent:\n";
        $corsHeaders = [];
        foreach ($this->headers as $name => $value) {
            if (strpos($name, 'Access-Control-') === 0) {
                $corsHeaders[$name] = $value;
            }
        }
        if (empty($corsHeaders)) {
            echo "(no CORS headers set to be sent)\n";
        } else {
            foreach ($corsHeaders as $name => $value) {
                echo "$name: $value\n";
            }
        }
        
        // Check CORS headers that have actually been sent to browser
        echo "\nCORS Headers Actually Sent to Browser:\n";
        $sentCorsHeaders = [];
        $sentHeaders = headers_list();
        foreach ($sentHeaders as $header) {
            if (stripos($header, 'Access-Control-') === 0) {
                $parts = explode(':', $header, 2);
                $name = trim($parts[0] ?? '');
                $value = trim($parts[1] ?? '');
                $sentCorsHeaders[$name] = $value;
            }
        }
        if (empty($sentCorsHeaders)) {
            echo "(no CORS headers sent to browser yet)\n";
        } else {
            foreach ($sentCorsHeaders as $name => $value) {
                echo "$name: $value\n";
            }
        }
        
        // Compare and show differences
        echo "\nCORS Headers Comparison:\n";
        $setButNotSent = array_diff_key($corsHeaders, $sentCorsHeaders);
        $sentButNotSet = array_diff_key($sentCorsHeaders, $corsHeaders);
        
        if (!empty($setButNotSent)) {
            echo "Set but not yet sent: " . implode(', ', array_keys($setButNotSent)) . "\n";
        }
        if (!empty($sentButNotSet)) {
            echo "Sent but not in our headers: " . implode(', ', array_keys($sentButNotSet)) . "\n";
        }
        if (empty($setButNotSent) && empty($sentButNotSet) && !empty($corsHeaders)) {
            echo "All CORS headers match between set and sent\n";
        }
        
        echo "\n";
        
        // 6. Additional info
        echo "6. ADDITIONAL INFO:\n";
        echo "Status Code: " . $this->statusCode . "\n";
        echo "Content Length: " . strlen($this->content) . " bytes\n";
        echo "Headers Count: " . count($this->headers) . "\n";
        echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
        echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
        echo "Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? 'not set') . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Output buffering level: " . ob_get_level() . "\n";
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }

    /**
     * Sends the response to the client (headers + content).
     */
    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        // Debug native headers after sending headers
        // $this->debugNativeHeaders();

        // Output content
        echo $this->content;
    }

    /**
     * Create a Response from a given Throwable.
     * If it's an HttpExceptionInterface, use its status code and headers.
     * Otherwise, treat it as 500 Internal Server Error.
     */
    public static function fromException(Throwable $e): self
    {
        // Default to HTTP 500
        $statusCode = 500;
        $headers = [];
        $message = $e->getMessage();

        // If it's an HTTP exception, extract status code & headers
        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
            $headers = $e->getHeaders();
            // Possibly set a default message for the code
            $message = sprintf(' | %s', self::getDefaultStatusText($statusCode));
        }
        // Attempt to load a custom error view if it exists
        $content = self::renderErrorPage($statusCode, $message);
        return new self($content, $statusCode, $headers);
    }
    private static function renderErrorPage(int $statusCode, string $message): string
    {
        // Path to your error templates
        $errorDir = __DIR__ . '/../templates/errors';

        // Build the filename: e.g., "404.php"
        $filePath = $errorDir . '/' . 'exception.php';

        if (is_readable($filePath)) {
            // Capture output buffering
            ob_start();
            // Make $message available inside the template
            include $filePath;
            return ob_get_clean() ?: '';
        }

        // Fallback if no custom file found
        return "Error $statusCode: " . htmlspecialchars($message);
    }

    /**
     * Create a redirect response.
     *
     * @param string $url The URL to redirect to.
     * @param int $statusCode The HTTP status code (default is 302).
     * @return self
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }


    /**
     * Optionally, map a status code to a default message like "Not Found" or "Internal Server Error".
     */
    private static function getDefaultStatusText(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            410 => 'Gone',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Unknown Error',
        };
    }
}
