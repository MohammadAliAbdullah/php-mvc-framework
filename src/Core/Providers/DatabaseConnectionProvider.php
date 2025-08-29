<?php

declare(strict_types=1);

namespace App\Core\Providers;

use Illuminate\Container\Container;
use PDO;
use PDOException;

class DatabaseConnectionProvider
{
    /**
     * @var Container
     */
    protected Container $container;

    /**
     * Accept the Container instance in the constructor so we can bind.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register the PDO binding in the container as a singleton.
     */
    public function register(): void
    {
        $this->container->singleton(PDO::class, function () {
            $host     = $_ENV['DB_HOST'] ?? 'mvc.db';
            $database = $_ENV['DB_NAME'] ?? 'mvc';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            $charset  = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

            try {
                return new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => true,
                ]);
            } catch (PDOException $e) {
                throw new PDOException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
            }
        });
    }
}
