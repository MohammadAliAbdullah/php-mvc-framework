<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Exceptions\AccessDeniedHttpException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;

class AdminApiAuthMiddleware
{
    private ResourceServer $resourceServer;

    public function __construct(ResourceServer $resourceServer)
    {
        $this->resourceServer = $resourceServer;
    }

    /**
     * Handle an incoming request.
     * 1) Convert your custom Request to a Guzzle PSR-7 ServerRequest.
     * 2) Let ResourceServer validate the token.
     * 3) Check that the "admin" scope is present. If not, deny access.
     */
    public function handle(Request $request, callable $next): ?Response
    {
        // 1) Build a Guzzle ServerRequest from your framework's Request.

        // Basic approach: method + URI
        // (If your $request object differs, adapt these steps.)
        $psrRequest = new ServerRequest($request->getMethod(), $request->getUri());

        // If you have body content or files, you'd set them here:
        // $psrRequest = $psrRequest->withBody(Stream_for($request->getBody()));
        // etc.

        // Copy headers from your custom Request to the PSR-7 request
        // Assume $request->getHeaders() returns an associative array of name => string|array
        foreach ($request->getHeaders() as $name => $value) {
            // If $value is an array, set each. If it's a single string, set directly.
            // For demonstration, assume it's a single string:
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        // 2) Validate the request via ResourceServer (checks token signature, expiry, revocation, etc.)
        try {
            // If token is invalid, it throws OAuthServerException
            $validRequest = $this->resourceServer->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException $e) {
            return new Response('Invalid or expired token', 401);
        }

        // 3) Check for "admin" scope. The validated request typically has "oauth_scopes" in its attributes.
        $scopes = $validRequest->getAttribute('oauth_scopes', []);
        if (!in_array('admin', $scopes, true)) {
            // If the "admin" scope is missing, throw a 403 or AccessDenied exception
            throw new AccessDeniedHttpException('Admin scope required to access this resource.');
        }

        // 4) If the token includes 'admin', allow the request to continue to the next step/controller
        return $next($request);
    }
}
