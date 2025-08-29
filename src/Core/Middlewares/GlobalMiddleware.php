<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use App\Core\Http\Request;
use App\Core\Repositories\Auth\AccessTokenRepository;
use App\Core\Repositories\Auth\ClientRepositoryInterface;
use App\Core\Repositories\UserRepositoryInterface;


class GlobalMiddleware
{
    protected ClientRepositoryInterface $clientRepository;
    protected AccessTokenRepository $accessTokenRepository;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        ClientRepositoryInterface $clientRepository,
        AccessTokenRepository $accessTokenRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->clientRepository = $clientRepository;
        $this->accessTokenRepository = $accessTokenRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Handle the incoming request and set `user` and `oauth_scopes` attributes.
     *
     * @param Request $request
     * @param callable $next
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed
    {
        $request->setAttribute('user', null);
        $request->setAttribute('oauth_scopes', null);
        // 1. Check if the session contains user_id and oauth_scopes
        $session = $request->getAttribute('session');
        if ($session && isset($session['user_id'], $session['oauth_scopes'])) {
            $user = $this->userRepository->find($session['user_id']);
            if ($user) {
                $scopes = $this->userRepository->getUserScopes($user->id);
                $request->setAttribute('user', $user);
                $request->setAttribute('oauth_scopes', $scopes);
                return $next($request);
            }
        }

        // 2. Check server-to-server request using ClientRepository
        $authHeader = $request->header('Authorization');
        $accessToken = null;
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7); // Remove "Bearer " prefix
            $accessToken = $this->accessTokenRepository->validateAccessToken($token);
        }
        if(!$accessToken){
            $queryToken = $request->query('access_token');
            if ($queryToken) {
                $accessToken = $this->accessTokenRepository->validateAccessToken($queryToken);
            }
        }
        if ($accessToken) {
            if(isset($accessToken['user_id'])){
                $user = $this->userRepository->find($accessToken['user_id']);
                if ($user) {
                    $scopes = $this->userRepository->getUserScopes($user->id);
                    $request->setAttribute('user', $user);
                    $request->setAttribute('oauth_scopes', $scopes);
                    return $next($request);
                }
            }
            if(isset($accessToken['client_id'])){
                $client = $this->clientRepository->getClientEntity($accessToken['client_id']);
                if ($client) {
                    $scopes = $client->getScopes();
                    $request->setAttribute('client', $client);
                    $request->setAttribute('oauth_scopes', $scopes);
                    return $next($request);
                }
            }
        }

        // If all checks fail, continue without setting `user` or `oauth_scopes`
        return $next($request);
    }
}
