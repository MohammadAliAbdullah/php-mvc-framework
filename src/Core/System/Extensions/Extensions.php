<?php

namespace App\Core\System\Extensions;

use App\Core\System\Event;
use App\Core\System\Import\Rss;
use App\Core\System\Utils\Str;
use function App\Core\System\utils\__;
use function App\Core\System\Utils\adminPath;
use function App\Core\System\utils\download;
use function App\Core\System\utils\getUrl;
use function App\Core\System\utils\unzip;

/**
 * Base class for managing extensions such as plugins or themes.
 */
abstract class Extensions
{
    protected static array $extensions = [];
    protected static array $categories = [];
    protected static string $extension = 'extension';
    protected static string $baseDir = 'extensions';
    protected static ?string $url = null;
    protected static ?string $feedUrl = null;

    private const KEY_VALUE_REGEX = '/^([\w ]+):\s+(.+)$/m';

    /**
     * Parse key-value parameters from comments.
     *
     * @param string $comments
     * @return array
     */
    protected static function getParams(string $comments): array
    {
        $results = [];

        if (preg_match_all(self::KEY_VALUE_REGEX, $comments, $matches)) {
            $keys = array_map(fn($key) => str_replace(' ', '-', strtolower($key)), $matches[1]);
            $results = array_combine($keys, $matches[2]);
        }

        return $results;
    }

    /**
     * Extract all comments from a file's content.
     *
     * @param string $content
     * @return string
     */
    protected static function getComments(string $content): string
    {
        if (function_exists('token_get_all')) {
            $comments = array_filter(
                token_get_all($content),
                fn($entry) => is_array($entry) && ($entry[0] === T_DOC_COMMENT || $entry[0] === T_COMMENT)
            );
            return implode("\n", array_column($comments, 1));
        }

        if (preg_match_all('@(?s)/\*.*?\*/@', $content, $matches, PREG_PATTERN_ORDER)) {
            return implode("\n", $matches[0] ?? []);
        }

        return '';
    }

    /**
     * Retrieve metadata from file content.
     *
     * @param string $content
     * @param string|null $name
     * @return array
     */
    public static function getInfo(string $content, ?string $name = null): array
    {
        $comments = self::getComments($content);
        $params = self::getParams($comments);

        unset($params['status']);

        if (!empty($params['category']) && $name) {
            static::$categories[$params['category']][] = $name;
        }

        return $params;
    }

    /**
     * Get a list of extension metadata from a given path.
     *
     * @param string $path
     * @return array
     */
    public static function getListInfo(string $path): array
    {
        if (isset(static::$extensions[static::$extension])) {
            return static::$extensions[static::$extension];
        }

        static::$extensions[static::$extension] = [];
        $adminPath = adminPath();
        $files = glob($path) ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $dir = Str::match('@(.+)/[a-z]+\.\w+$@', $file);
            $folder = Str::match('@/([^/]+)/[a-z]+\.\w+$@', $file);
            $info = self::getInfo($content, $folder);

            $info['file'] = $file;
            $info['folder'] = $folder;
            $info['import'] = file_exists($dir . DIRECTORY_SEPARATOR . 'import');

            if (isset($info['settings'])) {
                $info['settings'] = str_replace('/admin/', $adminPath, $info['settings']);
            }

            if (!isset($info['slug']) || $info['slug'] !== $info['folder']) {
                $info['status'] = 'slug_folder_mismatch';
                $info['slug'] = $info['folder'];
            }

            static::$extensions[static::$extension][$folder] = $info;
        }

        return static::$extensions[static::$extension];
    }

    /**
     * Get all extension categories.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return static::$categories;
    }

    /**
     * Download a file from a URL.
     *
     * @param string $url
     * @return string|false
     */
    public static function download(string $url): string|false
    {
        $temp = tempnam(sys_get_temp_dir(), 'app_extension');

        if ($content = download($url)) {
            file_put_contents($temp, $content, LOCK_EX);
            return $temp;
        }

        return false;
    }

    /**
     * Install an extension from a ZIP file.
     *
     * @param string $extensionZipFile
     * @param string|null $slug
     * @param bool $validate
     * @return string|bool
     * @throws \Exception
     */
    public static function install(string $extensionZipFile, ?string $slug = null, bool $validate = true): string|bool
    {
        $extension = static::$extension;
        $extractTo = static::$baseDir;
        $fileCheck = "$extension.php";

        $zip = new \ZipArchive();
        if ($zip->open($extensionZipFile) !== true) {
            throw new \Exception(__('Invalid ZIP archive!'));
        }

        $folderName = $zip->getNameIndex(0);
        $slug = $slug ?? rtrim($folderName, '/');

        if (!self::validateFolder($folderName, $validate)) {
            return false;
        }

        $info = self::extractExtension($zip, $folderName, $slug, $fileCheck, $validate);
        $zip->close();

        if (!$info) {
            throw new \Exception(sprintf(__('No `%s.php` info found in ZIP!'), $extension));
        }

        Event::trigger(__CLASS__, __FUNCTION__, $extensionZipFile, $info['slug']);
        return $info['slug'];
    }

    /**
     * Validate the folder structure in the ZIP.
     *
     * @param string $folderName
     * @param bool $validate
     * @return bool
     * @throws \Exception
     */
    private static function validateFolder(string $folderName, bool $validate): bool
    {
        if (!str_ends_with($folderName, '/')) {
            if ($validate) {
                throw new \Exception(__('ZIP must contain a top-level folder!'));
            }
            return false;
        }

        return true;
    }

    /**
     * Extract and validate extension files from the ZIP.
     *
     * @param \ZipArchive $zip
     * @param string $folderName
     * @param string $slug
     * @param string $fileCheck
     * @param bool $validate
     * @return array|null
     */
    private static function extractExtension(
        \ZipArchive $zip,
        string $folderName,
        string $slug,
        string $fileCheck,
        bool $validate
    ): ?array {
        $info = null;
        $extractTo = static::$baseDir . DIRECTORY_SEPARATOR . $slug;

        for ($i = $zip->numFiles - 1; $i >= 0; $i--) {
            $file = $zip->getNameIndex($i);

            if (str_contains($file, $fileCheck)) {
                $content = $zip->getFromName($file);
                $info = self::getInfo($content);

                if ($info['slug'] !== $slug) {
                    throw new \Exception(__('Slug does not match folder name!'));
                }

                if (!$zip->extractTo($extractTo)) {
                    return null;
                }
            }
        }

        return $info;
    }

    /**
     * Get the marketplace URL.
     *
     * @return string|null
     */
    public static function marketUrl(): ?string
    {
        return static::$url;
    }

    /**
     * Fetch a list of extensions from the marketplace.
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public static function getMarketList(array $params = []): array
    {
        if (!static::$feedUrl) {
            throw new \Exception(__('Marketplace feed URL is not set!'));
        }

        $query = http_build_query($params);
        $content = getUrl(static::$feedUrl . '?' . $query);

        if ($content) {
            $rss = new Rss($content);
            return [
                static::$extension . 's' => $rss->get($params['start'] ?? 1, $params['limit'] ?? 10),
                'count' => $rss->value('count'),
            ];
        }

        return [];
    }
}
