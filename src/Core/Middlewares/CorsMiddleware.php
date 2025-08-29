<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Http\Request;
use App\Core\Http\Response;

class CorsMiddleware
{
    private array $config;
    private bool $enabled;

    public function __construct(array $config = [])
    {
        // Load default configuration from config file
        $defaultConfig = $this->loadConfig();
        
        $this->enabled = $config['enabled'] ?? $defaultConfig['enabled'] ?? true;
        $this->config = array_merge($defaultConfig, $config);
    }

    /**
     * Load CORS configuration from config file
     */
    private function loadConfig(): array
    {
        $configFile = __DIR__ . '/../config/cors.php';
        
        if (file_exists($configFile)) {
            $config = include $configFile;
            
            // Handle allowed_origins if it's a string (comma-separated)
            if (isset($config['allowed_origins']) && is_string($config['allowed_origins'])) {
                $config['allowed_origins'] = array_map('trim', explode(',', $config['allowed_origins']));
            }
            
            return $config;
        }
        
        // Fallback configuration
        return [
            'allowed_origins' => $this->getAllowedOrigins(),
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
            'allowed_headers' => [
                'Content-Type',
                'Authorization',
                'X-Requested-With',
                'Accept',
                'Cache-Control',
                'X-CSRF-TOKEN',
                'X-API-Key'
            ],
            'exposed_headers' => [],
            'allow_credentials' => true,
            'max_age' => 86400, // 24 hours
        ];
    }

    /**
     * Enable or disable CORS
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if CORS is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function handle(?Request $request, Response $response, callable $next): Response
    {
        // If CORS is disabled, just continue without adding headers
        if (!$this->enabled) {
            return $next($request, $response);
        }

        // Get origin from request or fallback to $_SERVER
        $origin = null;
        if ($request) {
            $origin = $request->header('Origin');
        }
        
        // Fallback to $_SERVER if request is null or origin not found
        if (!$origin) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        }
        
        // Handle preflight requests
        if ($request && $request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($response, $origin);
        }

        // Add CORS headers to the response
        $response = $this->addCorsHeaders($response, $origin);

        // Continue with the request processing
        return $next($request, $response);
    }

    private function handlePreflightRequest(Response $response, ?string $origin): Response
    {
        $response = $response->withStatus(200);
        
        if ($origin && $this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']))
            ->withHeader('Access-Control-Max-Age', (string) $this->config['max_age']);

        if ($this->config['allow_credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->config['exposed_headers'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
        }

        return $response;
    }

    private function addCorsHeaders(Response $response, ?string $origin): Response
    {
        if ($origin && $this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']));

        if ($this->config['allow_credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->config['exposed_headers'])) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
        }

        return $response;
    }

    private function isOriginAllowed(?string $origin): bool
    {
        if (!$origin) {
            return false;
        }

        foreach ($this->config['allowed_origins'] as $allowedOrigin) {
            if ($allowedOrigin === '*' || $allowedOrigin === $origin) {
                return true;
            }
            
            // Support for wildcard subdomains (e.g., *.example.com)
            if (strpos($allowedOrigin, '*') === 0) {
                $domain = substr($allowedOrigin, 2); // Remove '*.'
                if (str_ends_with($origin, $domain)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getAllowedOrigins(): array
    {
        // Try to get from environment variable first
        $envOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? $_SERVER['CORS_ALLOWED_ORIGINS'] ?? null;
        
        if ($envOrigins) {
            return array_map('trim', explode(',', $envOrigins));
        }

        // Fallback to common development origins
        return [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:8080',
        ];
    }
} 