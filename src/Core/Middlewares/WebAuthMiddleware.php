<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Services\AuthService;

class WebAuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request. If the user is not logged in or session is expired,
     * redirect them to login (or throw an Unauthorized).
     */
    public function handle(Request $request, callable $next): ?Response
    {
        if (!$this->authService->isLoggedIn()) {
            // Option 1: Throw an exception
            // throw new UnauthorizedHttpException('Session expired or not logged in.');

            // Option 2: Redirect to login
            return new Response(
                '',    // No body
                302,
                ['Location' => '/auth/login']
            );
        }

        // Otherwise, user is valid => proceed
        return $next($request);
    }
}
