<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Exceptions\AccessDeniedHttpException;
use App\Core\Http\Request;
use App\Core\Http\Response;


class GuardMiddleware
{
    /**
     * @param Request      $request
     * @param callable     $next
     * @param string|null  $requiredScope e.g. "admin", "customer", ...
     * @return Response|null
     */
    public function handle(Request $request, callable $next, ?string $requiredScope = null): ?Response
    {
        // If no scope was specified, do nothing
        if (!$requiredScope) {
            return $next($request);
        }

        // Retrieve scopes from $request->attributes
        $scopes = $request->getAttribute('oauth_scopes', []);

        // Check if the required scope is in the user’s scope list
        if (!in_array($requiredScope, $scopes, true)) {
            // If missing, we throw or return a 403
            throw new AccessDeniedHttpException(
                sprintf('Scope "%s" is required to access this resource.', $requiredScope)
            );
        }

        // Otherwise, proceed
        return $next($request);
    }
}
