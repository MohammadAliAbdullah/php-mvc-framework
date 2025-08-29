<?php

declare(strict_types=1);

namespace App\Core\System;

use function App\Core\System\Utils\app;

class Config
{
    private static ?Config $instance = null;

    private function __construct() {}

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get a configuration value by its path.
     */
    public function get(string|null $path, mixed $default = []): mixed
    {
        $value = app()->make('config');
        if($path){
            $segments = explode('.', $path);

            foreach ($segments as $segment) {
                if (is_array($value) && array_key_exists($segment, $value)) {
                    $value = $value[$segment];
                } else {
                    return $default; // Return default if path not found
                }
            }
        }

        return $value??[];
    }

    /**
     * Set a configuration value by its path.
     */
    public function set(string $path, mixed $value): bool
    {
        $config = app()->make('config');

        $segments = explode('.', $path);
        $namespace = $segments[0];
        $configRef = &$config;

        foreach ($segments as $segment) {
            if (!isset($configRef[$segment]) || !is_array($configRef[$segment])) {
                $configRef[$segment] = [];
            }
            $configRef = &$configRef[$segment];
        }

        $configRef = $value;

        // Update the container with the modified config
        app()->instance('config', $config);

        // Save the namespace configuration to the corresponding .php file
        return $this->saveConfig($namespace);
    }

    /**
     * Unset a configuration value by its path.
     */
    public function unset(string $path): bool
    {
        $config = app()->make('config');

        $segments = explode('.', $path);
        $namespace = $segments[0];
        $lastKey = array_pop($segments);
        $configRef = &$config;

        foreach ($segments as $segment) {
            if (!isset($configRef[$segment]) || !is_array($configRef[$segment])) {
                return false; // Path not found
            }
            $configRef = &$configRef[$segment];
        }

        if (isset($configRef[$lastKey])) {
            unset($configRef[$lastKey]);
        }

        // Update the container with the modified config
        app()->instance('config', $config);

        // Save the namespace configuration to the corresponding .php file
        return $this->saveConfig($namespace);
    }

    /**
     * Save a configuration namespace to a file.
     */
    private function saveConfig(string $namespace): bool
    {
        $config = app()->make('config');

        if (!isset($config[$namespace])) {
            return false; // Nothing to save
        }

        $filePath = DIR_ROOT . "/config/$namespace.php";
        $data = "<?php\n\nreturn " . var_export($config[$namespace], true) . ";\n";

        try {
            file_put_contents($filePath, $data, LOCK_EX);
            clearstatcache(true, $filePath);

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($filePath);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving configuration: " . $e->getMessage());
            return false;
        }
    }
}
