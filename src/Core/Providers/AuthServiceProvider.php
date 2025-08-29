<?php

declare(strict_types=1);

namespace App\Core\Providers;

use App\Core\Repositories\Auth\AccessTokenRepository;
use App\Core\Repositories\Auth\AccessTokenRepositoryInterface;
use App\Core\Repositories\Auth\ClientRepository;
use App\Core\Repositories\Auth\ClientRepositoryInterface;
use App\Core\Repositories\Auth\RefreshTokenRepository;
use App\Core\Repositories\Auth\RefreshTokenRepositoryInterface;
use App\Core\Repositories\Auth\ScopeRepository;
use App\Core\Repositories\Auth\ScopeRepositoryInterface;
use App\Core\Repositories\UserRepositoryInterface;
use App\Core\Services\AuthService;
use DateInterval;
use Defuse\Crypto\Key;
use Illuminate\Container\Container;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use PDO;

/**
 * AuthServiceProvider sets up:
 * 1. The OAuth2 AuthorizationServer for local token issuance (username/password, client creds).
 * 2. Google and Facebook providers (league/oauth2-client) for third-party logins.
 * 3. Binds a unified AuthService that uses these providers/repositories.
 */
class AuthServiceProvider
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }


    /**
     * Register all Auth-related dependencies in the container.
     */
    public function register(): void
    {
        //Register Auth Repositories to Container
        $this->registerAuthRepositories();

        // 1) Bind the AuthorizationServer
        $this->registerAuthorizationServer();

        // 2) Bind Google and Facebook providers
        $this->registerThirdPartyProviders();

        // 3) Bind our AuthService
        $this->registerAuthService();
    }

    protected function registerAuthRepositories(): void
    {
        $this->container->singleton(ClientRepositoryInterface::class, ClientRepository::class);
        $this->container->singleton(ScopeRepositoryInterface::class, ScopeRepository::class);
        $this->container->bind(AccessTokenRepositoryInterface::class, function ($c) {
            return new AccessTokenRepository($c->make(PDO::class));
        });
        $this->container->bind(RefreshTokenRepositoryInterface::class, function ($c) {
            return new RefreshTokenRepository($c->make(PDO::class));
        });

    }

    /**
     * Example: Set up the League OAuth2 AuthorizationServer with password & client credentials grants.
     */
    protected function registerAuthorizationServer(): void
    {
        $this->container->singleton(AuthorizationServer::class, function ($container) {
            // Retrieve your required repositories from the container or config
            $clientRepository        = $container->make(ClientRepositoryInterface::class);
            $accessTokenRepository   = $container->make(AccessTokenRepositoryInterface::class);
            $scopeRepository         = $container->make(ScopeRepositoryInterface::class);
            $refreshTokenRepository  = $container->make(RefreshTokenRepositoryInterface::class);
            $userRepository          = $container->make(UserRepositoryInterface::class);

            // Configure the private key
            $privateKeyPath = ROOT_DIR.DIRECTORY_SEPARATOR.'private.key';
            $encryptionKeyPath = ROOT_DIR.DIRECTORY_SEPARATOR.'encryption.key';
            $privateKey = new CryptKey($privateKeyPath, null, false);

            // Configure the encryption key
            $encryptionKey = Key::loadFromAsciiSafeString(
                file_get_contents($encryptionKeyPath)
            );

            // Create the server
            $server = new AuthorizationServer(
                $clientRepository,
                $accessTokenRepository,
                $scopeRepository,
                $privateKey,
                $encryptionKey
            );

            // 1) Enable Password grant (username/password)
            $passwordGrant = new PasswordGrant($userRepository, $refreshTokenRepository);
            // Example: tokens last 1 hour
            $passwordGrant->setRefreshTokenTTL(new DateInterval('P1M')); // e.g. refresh tokens valid for 1 month
            $server->enableGrantType($passwordGrant, new DateInterval('PT1H'));

            // 2) Enable Client Credentials grant
            $clientGrant = new ClientCredentialsGrant();
            $server->enableGrantType($clientGrant, new DateInterval('PT1H'));

            return $server;
        });
    }

    /**
     * Example: Create Google & Facebook providers (league/oauth2-client).
     */
    protected function registerThirdPartyProviders(): void
    {
        // Google
        $this->container->singleton(Google::class, function () {
            return new Google([
                'clientId'     => $_ENV['GOOGLE_CLIENT_ID']     ?? '',
                'clientSecret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
                'redirectUri'  => $_ENV['GOOGLE_REDIRECT_URI']  ?? '',
                // Additional config as needed
            ]);
        });

        // Facebook
        $this->container->singleton(Facebook::class, function () {
            return new Facebook([
                'clientId'          => $_ENV['FB_CLIENT_ID']      ?? '',
                'clientSecret'      => $_ENV['FB_CLIENT_SECRET']  ?? '',
                'redirectUri'       => $_ENV['FB_REDIRECT_URI']   ?? '',
                'graphApiVersion'   => 'v12.0',
                // Additional config as needed
            ]);
        });
    }

    /**
     * Finally, bind our unified AuthService which uses the AuthorizationServer
     * and the 3rd-party providers. Also references the local UserRepository.
     */
    protected function registerAuthService(): void
    {
        $this->container->singleton(AuthService::class, function ($container) {
            /** @var AuthorizationServer $authServer */
            $authServer = $container->make(AuthorizationServer::class);

            /** @var UserRepositoryInterface $userRepo */
            $userRepo = $container->make(UserRepositoryInterface::class);

            /** @var ClientRepositoryInterface $clientRepository */
            $clientRepository = $container->make(ClientRepositoryInterface::class);

            /** @var RefreshTokenRepositoryInterface $refreshTokenRepository */
            $refreshTokenRepository = $container->make(RefreshTokenRepositoryInterface::class);

            /** @var ScopeRepositoryInterface $scopeRepository */
            $scopeRepository = $container->make(ScopeRepositoryInterface::class);

            /** @var AccessTokenRepositoryInterface $accessTokenRepository */
            $accessTokenRepository = $container->make(AccessTokenRepositoryInterface::class);

            /** @var Google $google */
            $google = $container->make(Google::class);

            /** @var Facebook $facebook */
            $facebook = $container->make(Facebook::class);

            return new AuthService(
                $authServer,
                $userRepo,
                $clientRepository,
                $refreshTokenRepository,
                $scopeRepository,
                $accessTokenRepository,
                $google,
                $facebook);
        });
    }
}
