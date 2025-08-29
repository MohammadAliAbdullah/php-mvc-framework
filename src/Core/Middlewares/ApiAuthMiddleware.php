<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Services\AuthService;

class ApiAuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an API request with token authentication.
     */
    public function handle(Request $request, callable $next): Response
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse('Missing or invalid Authorization header.');
        }

        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        if (!$this->authService->validateToken($token)) {
            return $this->unauthorizedResponse('Invalid or expired token.');
        }

        return $next($request);
    }

    /**
     * Return a 401 Unauthorized response.
     */
    private function unauthorizedResponse(string $message): Response
    {
        $body = json_encode(['error' => 'unauthorized', 'message' => $message]);
        return new Response($body, 401, ['Content-Type' => 'application/json']);
    }
}
