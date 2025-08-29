<?php

namespace App\Core\System;

class ConfigurationsLoader
{
    protected string $configPath;
    protected string $pluginsPath;

    public function __construct(string $basePath = ROOT_DIR)
    {
        $this->configPath = $basePath . '/src/Core/config';
        $this->pluginsPath = $basePath . '/src/Plugins';
    }

    /**
     * Load configuration from the `config` folder and all `plugins` folders.
     *
     * @return array
     */
    public function loadConfiguration(): array
    {
        $configurations = [];

        // Load files from the config directory
        $configFiles = $this->findPhpFiles($this->configPath);

        foreach ($configFiles as $file) {
            $configName = pathinfo($file, PATHINFO_FILENAME);
            $configurations[$configName] = include $file;
        }

        // Load files from the plugins directory
        $pluginConfigFiles = $this->findPhpFilesInSubdirectories($this->pluginsPath);

        foreach ($pluginConfigFiles as $file) {
            $directory = dirname($file);;
            $parentDirectory = dirname($directory);
            $pluginName = basename($parentDirectory); // Folder name as plugin name
            $configName = pathinfo($file, PATHINFO_FILENAME);

            // Store plugin configurations under their plugin name
            if (!isset($configurations['plugins'][$pluginName])) {
                $configurations['plugins'][$pluginName] = [];
            }

            $configurations['plugins'][$pluginName][$configName] = include $file;
        }

        return $configurations;
    }

    /**
     * Find all .php files in the specified directory.
     *
     * @param string $path
     * @return array
     */
    protected function findPhpFiles(string $path): array
    {
        return glob($path . '/*.php') ?: [];
    }

    /**
     * Find all .php files in the specified directory and its subdirectories.
     *
     * @param string $path
     * @return array
     */
    protected function findPhpFilesInSubdirectories(string $path): array
    {
        return glob($path . '/*/config/*.php') ?: [];
    }
}
