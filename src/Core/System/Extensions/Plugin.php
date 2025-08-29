<?php

namespace App\Core\System\Extensions;

use App\Core\System\Cache;
use Illuminate\Container\Container;
use App\Core\System\Event;
use function App\Core\System\utils\rcopy;
use function App\Core\System\Utils\rrmdir;
use function App\Core\System\Utils\setConfig;
use function App\Core\System\Utils\unsetConfig;

class Plugin extends Extensions
{
    protected static array $categories = [];
    protected static array $loadedPlugins = [];
    protected static bool $loaded = false;
    protected Container $container;

    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /**
     * Register and bootstrap all active plugins.
     */
    public function register(): void
    {
        $plugins = $this->getActivePlugins();

        foreach ($plugins as $pluginName => $pluginInstance) {
            $this->registerPlugin($pluginName, $pluginInstance);
        }
    }

    /**
     * Clear plugin cache for a given site ID.
     *
     * @param int|string $site_id
     */
    public static function clearPluginsCache($site_id): void
    {
        $cacheDriver = Cache::getInstance();
        $cacheKey = "plugins_list_$site_id";
        $cacheDriver->delete('app', $cacheKey);
    }

    public function loadAllActivePlugins($site_id = SITE_ID): void
    {
        $activePlugins = $this->getActivePlugins();
        foreach ($activePlugins as $pluginName => $plugin) {
            if(isset($plugin['instance']) && class_exists($plugin['instance'])) {
                $pluginInstance = new $plugin['instance']();
                $this->registerPlugin($pluginName, $pluginInstance);

                // Clear the cache for this site ID
                static::clearPluginsCache($site_id);
            }
        }
    }

    /**
     * Dynamically load and register a plugin.
     */
    public function loadPlugin(string $pluginName, $site_id = SITE_ID): bool
    {
        $plugins = $this->getAllPlugins();

        if (!isset($plugins[$pluginName])) {
            return false; // Plugin not found
        }

        $pluginInstance = $plugins[$pluginName];

        if (!$pluginInstance->isActive()) {
            return false; // Plugin is not active
        }

        $this->registerPlugin($pluginName, $pluginInstance);

        // Clear the cache for this site ID
        static::clearPluginsCache($site_id);

        return true;
    }

    /**
     * Register a single plugin.
     */
    protected function registerPlugin(string $pluginName, object $pluginInstance): void
    {
        if (isset(self::$loadedPlugins[$pluginName])) {
            return; // Plugin already loaded
        }

        // Call the plugin's register method
        if (method_exists($pluginInstance, 'register')) {
            $pluginInstance->register();
        }

        // Trigger plugin-specific events
        Event::trigger(__CLASS__, 'registerPlugin', $pluginName);

        // Mark plugin as loaded
        self::$loadedPlugins[$pluginName] = $pluginInstance;
    }

    /**
     * Get all active plugins.
     */
    protected function getActivePlugins(): array
    {
        $plugins = $this->getAllPlugins();
        return array_filter($plugins, function($pluginInstance){
         return isset($pluginInstance['status']) && $pluginInstance['status'] === 'active';
        });
    }

    /**
     * Get all plugins with caching and category filtering.
     *
     * @param string|null $category
     * @return array
     */
    protected function getAllPlugins(?string $category = null, int $site_id = SITE_ID): array
    {
        $cacheKey = "plugins_list_$site_id";
        $cache = Cache::getInstance();
        $cache->delete('app', $cacheKey);
        // Check if plugins are cached
        $cachedPlugins = $cache->get('app', $cacheKey);
        if ($cachedPlugins !== null) {
            // If a specific category is requested, filter cached plugins
            if ($category) {
                return array_filter($cachedPlugins, fn($plugin) => $plugin['category'] === $category);
            }
            return $cachedPlugins;
        }

        $pluginDirs = glob(DIR_PLUGINS . '/*', GLOB_ONLYDIR) ?: [];
        $plugins = [];

        foreach ($pluginDirs as $dir) {
            $pluginClassPath = $dir . '/Plugin.php';

            if (file_exists($pluginClassPath)) {
                $pluginNamespace = $this->getPluginNamespace($dir);
                $pluginClass = $pluginNamespace . '\\Plugin';

                if (class_exists($pluginClass)) {
                    $pluginInstance = new $pluginClass($this->container);
                    $pluginName = basename($dir);

                    // Fetch metadata, assign category, and mark broken if necessary
                    $metadata = $pluginInstance->getMetadata() ?? [];
                    $plugins[$pluginName] = [
                        'instance' => $pluginInstance::class,
                        'name' => $metadata['name'] ?? sprintf('[%s]', $pluginName),
                        'slug' => $pluginName,
                        'status' => $metadata['status'] ?? 'broken',
                        'category' => $metadata['category'] ?? 'uncategorized',
                    ];
                }
            }
        }

        // Detect broken plugins and filter by category
        array_walk($plugins, function (&$plugin, $key) use ($category, &$plugins) {
            if (!isset($plugin['name']) || empty($plugin['name'])) {
                $plugin['name'] = sprintf('[%s]', $key);
                $plugin['status'] = 'broken';
            }

            // Filter plugins by category if specified
            if ($category && $plugin['category'] !== $category) {
                unset($plugins[$key]);
            }
        });

        // Cache the plugin list
        if (!$category) {
            $cache->set('app', $cacheKey, $plugins);

            // Cache categories separately for later use
            static::$categories = array_reduce(
                $plugins,
                function ($carry, $plugin) {
                    $carry[$plugin['category']][] = $plugin['slug'];
                    return $carry;
                },
                []
            );

            $cache->set('app', "{$cacheKey}_categories", static::$categories);
        }

        return $plugins;
    }

    /**
     * Get the namespace of the plugin based on its folder structure.
     */
    protected function getPluginNamespace(string $pluginDir): string
    {
        $baseNamespace = 'App\\Plugins';
        $pluginName = basename($pluginDir);

        return $baseNamespace . '\\' . $pluginName;
    }

    /**
     * Activate a plugin.
     *
     * @param string $pluginName
     * @param int|string $site_id
     * @return bool
     */
    public static function activate(string $pluginName, $site_id = SITE_ID): bool
    {
        if (!$pluginName) {
            return false;
        }

        // Get plugin file path
        $pluginDir = DIR_PLUGINS . "/$pluginName";
        $pluginFilePath = $pluginDir . '/Plugin.php';

        // Check if the plugin file exists
        if (!file_exists($pluginFilePath)) {
            return false;
        }

        // Update the status in the plugin file
        $fileContents = file_get_contents($pluginFilePath);

        // Replace 'status' => 'inactive' with 'status' => 'active'
        $updatedContents = preg_replace(
            "/('status'\s*=>\s*)'inactive'/",
            "$1'active'",
            $fileContents
        );

        // Save the updated contents back to the file
        if ($updatedContents !== $fileContents) {
            file_put_contents($pluginFilePath, $updatedContents);
        }

        // Update configuration to mark plugin as active
        $key = "plugins.$site_id.$pluginName.status";
        $result = setConfig($key, 'active');

        // Trigger activation event
        Event::trigger(__CLASS__, 'activate', $pluginName, $site_id);

        // Clear cache
        static::clearPluginsCache($site_id);

        return $result;
    }

    /**
     * Deactivate a plugin.
     *
     * @param string $pluginName
     * @param int|string $site_id
     * @return bool
     */
    public static function deactivate(string $pluginName, $site_id = SITE_ID): bool
    {
        if (!$pluginName) {
            return false;
        }

        // Get plugin file path
        $pluginDir = DIR_PLUGINS . "/$pluginName";
        $pluginFilePath = $pluginDir . '/Plugin.php';

        // Check if the plugin file exists
        if (!file_exists($pluginFilePath)) {
            return false;
        }

        // Update the status in the plugin file
        $fileContents = file_get_contents($pluginFilePath);

        // Replace 'status' => 'active' with 'status' => 'inactive'
        $updatedContents = preg_replace(
            "/('status'\s*=>\s*)'active'/",
            "$1'inactive'",
            $fileContents
        );

        // Save the updated contents back to the file
        if ($updatedContents !== $fileContents) {
            file_put_contents($pluginFilePath, $updatedContents);
        }

        // Update configuration to mark plugin as inactive
        $key = "plugins.$site_id.$pluginName.status";
        $result = setConfig($key, 'inactive');

        // Trigger deactivation event
        Event::trigger(__CLASS__, 'deactivate', $pluginName, $site_id);

        // Clear cache
        static::clearPluginsCache($site_id);

        return $result;
    }

    //copy plugin public folder to public/plugins folder
    static function copyPublicDir($pluginName) {
        if ($pluginName) {
            $publicSrc  = DIR_PLUGINS . "$pluginName/public";
            $publicDest = DIR_PUBLIC . "plugins/$pluginName";

            // Copy to public/plugins/plugin_name
            if (!file_exists($publicDest)) {
                rcopy($publicSrc, $publicDest);
            }

            // Copy to themes/landing/plugin_name
            $themeDest = DIR_THEMES . "landing/$pluginName";
            if (!file_exists($themeDest)) {
                rcopy($publicSrc, $themeDest);
            }
        }
    }

    static function install(string $extensionZipFile, ?string $slug = null, bool $validate = true): string|bool {
        $pluginName = parent::install($extensionZipFile, $slug, $validate);

        if (is_string($pluginName)) {
            self::copyPublicDir($pluginName);
            self::clearPluginsCache(SITE_ID);
        }

        return $pluginName;
    }

    /**
     * Uninstall a plugin.
     *
     * @param string $pluginName
     * @param int|string $site_id
     * @return bool
     */
    public static function uninstall(string $pluginName, $site_id = SITE_ID): bool
    {
        if (!$pluginName) {
            return false;
        }

        $pluginDir = DIR_PLUGINS . "/$pluginName";
        $publicDir = DIR_PUBLIC . "/plugins/$pluginName";

        // Remove directories
        rrmdir($publicDir);
        $success = rrmdir($pluginDir);

        // Update configuration to remove plugin entry
        unsetConfig("plugins.$site_id.$pluginName");

        // Trigger uninstall event
        Event::trigger(__CLASS__, 'uninstall', $pluginName, $success);

        // Clear cache
        static::clearPluginsCache($site_id);

        return $success;
    }

    /**
     * Get all categories with their plugins.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return static::$categories;
    }

    /**
     * Add a plugin to a category.
     *
     * @param string $category
     * @param string $pluginSlug
     * @return void
     */
    public static function addToCategory(string $category, string $pluginSlug): void
    {
        if (!isset(static::$categories[$category])) {
            static::$categories[$category] = [];
        }

        static::$categories[$category][] = $pluginSlug;
    }

    public static function getPluginPath(string $name): string
    {
        return DIR_PLUGINS . '/' . ucfirst($name);
    }
}
