<?php

declare(strict_types=1);

namespace App\Core\App;

use App\Core\Providers\AppServiceProvider;
use App\Core\Providers\AuthServiceProvider;
use App\Core\Providers\DatabaseConnectionProvider;
use App\Core\Providers\ExceptionServiceProvider;
use App\Core\System\Config;
use App\Core\System\ConfigurationsLoader;
use App\Core\System\Event;
use App\Core\System\Extensions\Plugin;
use Illuminate\Container\Container;
use PDO;

/**
 * KernelCli is a specialized version of the Kernel for CLI and testing environments.
 * It provides the same dependency injection container but without HTTP-specific features.
 */
class KernelCli
{
    /**
     * The global IoC Container instance.
     */
    protected Container $container;

    /**
     * An array of providers to register.
     *
     * @var string[]
     */
    protected array $providers = [
        DatabaseConnectionProvider::class,
        AppServiceProvider::class,
        ExceptionServiceProvider::class,
        AuthServiceProvider::class
    ];

    /**
     * Create a new CLI Kernel instance.
     */
    public function __construct()
    {
        // Get the global instance of the container (Singleton)
        $this->container = Container::getInstance();

        // Set the instance to this container
        Container::setInstance($this->container);

        // Perform the bootstrapping tasks
        $this->bootstrap();
    }

    /**
     * Get the container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Bootstrap the application for CLI/testing.
     */
    protected function bootstrap(): void
    {
        // Load configuration
        $this->loadConfiguration();

        // Register core services
        $this->registerCoreServices();

        // Load plugins
        $this->loadPlugins();

        // Load providers list
        $this->loadProvidersList();

        // Register all providers
        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass($this->container);
            $provider->register();
        }
    }

    /**
     * Load configuration files.
     */
    protected function loadConfiguration(): void
    {
        $loader = new ConfigurationsLoader();
        $configurations = $loader->loadConfiguration();

        // Bind the configuration array
        $this->container->instance('config', $configurations);

        // Inject the Config instance
        $this->container->singleton(Config::class, function () {
            return Config::getInstance();
        });
    }

    /**
     * Register core services needed for testing.
     */
    protected function registerCoreServices(): void
    {
        $this->container->singleton(Plugin::class, function () {
            return new Plugin();
        });

        // Register PDO with test database configuration
        $this->container->singleton(PDO::class, function () {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $database = $_ENV['DB_NAME'] ?? 'test_db';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false // Don't use persistent connections for tests
            ]);
        });
    }

    /**
     * Load plugins for testing environment.
     */
    protected function loadPlugins(): void
    {
        try {
            $pluginSystem = $this->container->make(Plugin::class);
            $pluginSystem->loadAllActivePlugins();
        } catch (\Exception $e) {
            // Log error but continue - plugins aren't critical for tests
            error_log("Error loading plugins in test environment: " . $e->getMessage());
        }
    }

    /**
     * Load service providers from plugins.
     */
    protected function loadProvidersList(): void
    {
        $providers = Event::trigger(KernelCli::class, 'add-providers', $this->providers);
        if (count($providers)) {
            $this->providers = $providers[0];
        }
    }

    /**
     * Add a service provider to the list.
     */
    public function addProvider(string $providerClass): void
    {
        if (!in_array($providerClass, $this->providers)) {
            $this->providers[] = $providerClass;
            $provider = new $providerClass($this->container);
            $provider->register();
        }
    }

    /**
     * Reset the container instance.
     * Useful for cleaning up between tests.
     */
    public function reset(): void
    {
        Container::setInstance(null);
        $this->container = Container::getInstance();
        $this->bootstrap();
    }
} 