<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Models\User;
use App\Core\Repositories\Auth\AccessTokenRepositoryInterface;
use App\Core\Repositories\Auth\ClientRepositoryInterface;
use App\Core\Repositories\Auth\RefreshTokenRepositoryInterface;
use App\Core\Repositories\Auth\ScopeRepositoryInterface;
use App\Core\Repositories\UserRepositoryInterface;
use Exception;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;

// from guzzlehttp/psr7
// from guzzlehttp/psr7

class AuthService
{
    // 1) Our locally hosted OAuth2 AuthorizationServer (for password + client credentials).
    private AuthorizationServer $authorizationServer;

    // 2) A reference to your local user repository for session-based lookups or password checks.
    private UserRepositoryInterface $userRepository;

    // 3) Third-party OAuth2 providers (Google, Facebook, etc.). You could store them in an array keyed by provider name.
    private Google $googleProvider;
    private Facebook $facebookProvider;

    // Optional default session duration, e.g. 30 mins
    private int $defaultSessionDuration = 1800;
    private ClientRepositoryInterface $clientRepository;
    private RefreshTokenRepositoryInterface $refreshTokenRepository;
    private ScopeRepositoryInterface $scopeRepository;
    private AccessTokenRepositoryInterface $accessTokenRepository;

    public function __construct(
        AuthorizationServer $authorizationServer,
        UserRepositoryInterface $userRepository,
        ClientRepositoryInterface $clientRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        ScopeRepositoryInterface $scopeRepository,
        AccessTokenRepositoryInterface $accessTokenRepository,
        Google $googleProvider,
        Facebook $facebookProvider
    ) {
        $this->authorizationServer = $authorizationServer;
        $this->userRepository      = $userRepository;
        $this->googleProvider      = $googleProvider;
        $this->facebookProvider    = $facebookProvider;

        // In a real app, you'd have configured your $authorizationServer with
        // the appropriate grants (PasswordGrant, ClientCredentialsGrant),
        // RSA keys, etc.
        $this->clientRepository = $clientRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->scopeRepository = $scopeRepository;
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * Register a new user and assign the default scope.
     */
    public function registerUser(string $name, string $email, string $password, array $scopes = []): array
    {
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            throw new Exception('User already exists');
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Create the user
        $userId = $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
        ]);

        if (!$userId) {
            throw new Exception('Failed to create user');
        }

        //Need to write code to add other scopes such as admin, customer, vendor etc.
        $scopes = array_merge($scopes, ['user']);

        // Assign default scope
        $scope = $this->scopeRepository->create([
            'user_id' => $userId,
            'scopes' => $scopes, // Default scope
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
        return ['userId' => $userId, 'scope' => $scope];
    }

    /* --------------------------------------------------------
     * 1) Username/Password Flow -> Issue JWT from OAuth2 Server
     * -------------------------------------------------------- */
    public function loginWithPasswordGrant(string $email, string $password): ?array
    {
        // We'll create a server-side request representing the token request:
        $request = (new ServerRequest('POST', '/token'))
            ->withParsedBody([
                'grant_type'    => 'password',
                'username'      => $email,
                'password'      => $password,
                // Possibly add 'client_id' and 'client_secret' if your server requires them
                'client_id'     => $_ENV['OAUTH_CLIENT_ID'] ?? 'your-client-id',
                'client_secret' => $_ENV['OAUTH_CLIENT_SECRET'] ?? 'your-client-secret',
            ]);

        // Create an empty PSR-7 response to hold the output
        $response = new Psr7Response();

        try {
            // The AuthorizationServer will validate credentials via your UserRepository
            $response = $this->authorizationServer->respondToAccessTokenRequest($request, $response);

            $data = json_decode((string)$response->getBody(), true);
            if (isset($data['access_token'])) {
                // Return the entire token response, e.g. { access_token, token_type, expires_in, ...}
                return $data;
            }
            return null;
        } catch (Exception $e) {
            // Log or handle error
            return null;
        }
    }

    /* --------------------------------------------------------
     * 2) Client Credentials -> Issue Token for Server-to-Server
     * -------------------------------------------------------- */
    /**
     * Register a new client.
     */
    public function registerClient(string $name, array $scopes, ?string $redirectUri = null): array
    {
        $secret = bin2hex(random_bytes(32));
        $hashedSecret = password_hash($secret, PASSWORD_BCRYPT);

        $clientId = $this->clientRepository->create([
            'name' => $name,
            'secret' => $hashedSecret,
            'scopes' => json_encode($scopes),
            'redirect_uri' => $redirectUri,
            'is_confidential' => true,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        return [
            'client_id' => $clientId,
            'client_secret' => $secret,
        ];
    }

    /**
     * Get a client token using client credentials.
     */
    public function getClientToken(string $clientId, string $clientSecret, array $scopes = []): array|ResponseInterface
    {
        $client = $this->clientRepository->find((int) $clientId);

        if (!$client || !password_verify($clientSecret, $client['secret'])) {
            throw new \Exception('Invalid client credentials');
        }

        return $this->authorizationServer->respondToAccessTokenRequest(
            $this->createClientCredentialsRequest($clientId, $clientSecret, $scopes),
            new \GuzzleHttp\Psr7\Response()
        );
    }

    /**
     * Validate an access token.
     *
     * @param string $token
     * @return array|null Returns token details or null if invalid.
     * @throws Exception If the token is invalid or revoked.
     */
    public function validateToken(string $token): ?array
    {
        // Validate the token via the repository
        $accessToken = $this->accessTokenRepository->validateAccessToken($token);

        if (!$accessToken) {
            throw new Exception('Invalid or expired token.');
        }

        // Check if the token is associated with a user
        if (isset($accessToken['user_id'])) {
            $user = $this->userRepository->find($accessToken['user_id']);
            if (!$user) {
                throw new Exception('User associated with the token not found.');
            }

            return [
                'type' => 'user',
                'entity' => $user,
                'scopes' => $accessToken['scopes'],
            ];
        }

        // Check if the token is associated with a client
        if (isset($accessToken['client_id'])) {
            $client = $this->clientRepository->getClientEntity($accessToken['client_id']);
            if (!$client) {
                throw new Exception('Client associated with the token not found.');
            }

            return [
                'type' => 'client',
                'entity' => $client,
                'scopes' => $accessToken['scopes'],
            ];
        }

        // If no user or client is associated with the token, it is invalid
        throw new Exception('Invalid token.');
    }

    /**
     * Refresh a client token.
     */
    public function refreshToken(string $refreshToken): array|ResponseInterface
    {
        $tokenData = $this->refreshTokenRepository->findValidToken($refreshToken);

        if (!$tokenData) {
            throw new \Exception('Invalid or expired refresh token');
        }

        $client = $this->clientRepository->find($tokenData['client_id']);

        if (!$client) {
            throw new \Exception('Client not found');
        }

        return $this->authorizationServer->respondToAccessTokenRequest(
            $this->createRefreshTokenRequest($refreshToken),
            new \GuzzleHttp\Psr7\Response()
        );
    }

    private function createClientCredentialsRequest(string $clientId, string $clientSecret, array $scopes): \Psr\Http\Message\ServerRequestInterface
    {
        return (new \GuzzleHttp\Psr7\ServerRequest('POST', '/token'))
            ->withParsedBody([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => implode(' ', $scopes),
            ]);
    }

    private function createRefreshTokenRequest(string $refreshToken): \Psr\Http\Message\ServerRequestInterface
    {
        return (new \GuzzleHttp\Psr7\ServerRequest('POST', '/token'))
            ->withParsedBody([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);
    }

    /* --------------------------------------------------------
     * 3) Session-Based Authentication (Local Database)
     * -------------------------------------------------------- */

    /**
     * Attempt to log in the user via local session. If success, store user info in $_SESSION.
     */
    public function loginSession(string $email, string $password, ?int $sessionDuration = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check the user in DB
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            return false;
        }

        // Verify password
        if (!password_verify($password, $user->getPassword())) {
            return false;
        }

        // If valid, store user ID + timestamps
        $_SESSION['user_id']      = $user->getId();
        $_SESSION['logged_in_at'] = time();
        $duration = $sessionDuration ?? $this->defaultSessionDuration;
        $_SESSION['expire_at']    = time() + $duration;

        return true;
    }

    public function logoutSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user_id'], $_SESSION['logged_in_at'], $_SESSION['expire_at']);
        session_destroy();
    }

    /**
     * Check if a user is logged in via session.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if session contains valid user ID and hasn't expired
        if (!isset($_SESSION['user_id'], $_SESSION['expire_at'])) {
            return false;
        }

        if (time() > $_SESSION['expire_at']) {
            // Session expired
            $this->logoutSession();
            return false;
        }

        // Optionally, verify the user ID exists in the database
        $user = $this->userRepository->find((int)$_SESSION['user_id']);
        return $user !== null;
    }

    /**
     * Check if the current session user has the "admin" scope.
     *
     * @return bool
     * @throws Exception
     */
    public function isAdmin(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if auth_scopes exist in the session
        if (!isset($_SESSION['auth_scopes'])) {
            return false;
        }

        // Retrieve and parse the scopes
        $authScopes = explode(',', $_SESSION['auth_scopes']);

        // Check if "admin" is in the list of scopes
        return in_array('admin', $authScopes, true);
    }

    public function getAuthUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        $id = $_SESSION['user_id'];
        return $this->userRepository->find((int)$id);
    }

    /* --------------------------------------------------------
     * 4) Third-Party (Google / Facebook) Logins
     * -------------------------------------------------------- */

    /**
     * Return the URL to which we redirect the user to authenticate with Google.
     */
    public function getGoogleAuthUrl(): string
    {
        return $this->googleProvider->getAuthorizationUrl();
    }

    /**
     * After Google redirects back with a "code", we exchange it for an AccessToken.
     * Then we fetch the user profile from Google -> use or store in local DB, etc.
     */
    public function handleGoogleCallback(string $authCode): ?array
    {
        try {
            $token = $this->googleProvider->getAccessToken('authorization_code', [
                'code' => $authCode
            ]);
            // Get user info from Google
            $owner = $this->googleProvider->getResourceOwner($token);
            return $owner->toArray();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Similarly for Facebook.
     */
    public function getFacebookAuthUrl(): string
    {
        return $this->facebookProvider->getAuthorizationUrl();
    }

    public function handleFacebookCallback(string $authCode): ?array
    {
        try {
            $token = $this->facebookProvider->getAccessToken('authorization_code', [
                'code' => $authCode
            ]);
            $owner = $this->facebookProvider->getResourceOwner($token);
            return $owner->toArray();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if an email is already registered.
     *
     * @param string $email
     * @return bool
     */
    public function isEmailRegistered(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);
        return $user !== null;
    }
}
