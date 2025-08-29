<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Services\AuthService;

class AuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $user = $request->getAttribute('user');

        // Check for user authentication
        if (!$user) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7); // Remove "Bearer " prefix
                try {
                    $this->authService->validateToken($token);
                }catch (\Exception $exception){
                    return $this->unauthorizedResponse('Invalid or expired token.');
                }
            } elseif (!$this->authService->isLoggedIn()) {
                return $this->unauthorizedResponse('Not authenticated.');
            }
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
