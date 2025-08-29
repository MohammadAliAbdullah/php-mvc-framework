<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace App\Core\System\Component;

use App\Core\Http\Response;
use App\Core\System\Cache;
use Illuminate\Contracts\Container\BindingResolutionException;
use function App\Core\System\utils\app;
use function App\Core\System\utils\session as sess;
use App\Core\Http\Request;
use App\Core\System\User\User;
use App\Core\View\View;
use App\Core\System\Session;

#[\AllowDynamicProperties]
class OldComponentBase {
    public string $cacheKey;
    public int $cacheExpire = 3600; //seconds
    public static array $global = [];
    protected array $options = [];
    protected ?string $_hash = null;
    public static array $defaultOptions = [];

    public function __construct(array $options = []) {
        $this->initializeGlobalDefaults();
        $this->setOptions($options);
        $this->resolveRequestParameters();
    }

    /**
     * Initializes global defaults shared across all components.
     */
    private function initializeGlobalDefaults(): void {
        if (!self::$global) {
            $user = User::current();
            self::$global = [
                'start' => 0,
                'site_id' => sess('site_id') ?? SITE_ID ?? 0,
                'user_id' => $user['user_id'] ?? null,
                'user_group_id' => $user['user_group_id'] ?? 1,
                'language_id' => sess('language_id') ?? 1,
                'language' => sess('language') ?? 'en_US',
                'default_language' => sess('default_language') ?? 'en_US',
                'default_language_id' => sess('default_language_id') ?? 1,
                'currency_id' => sess('currency_id') ?? 1,
            ];
        }
        static::$defaultOptions = array_merge(self::$global, static::$defaultOptions);
    }

    /**
     * Merges provided options with default options.
     */
    public function setOptions(array $options): void {
        if (isset($options['_hash'])) {
            $this->_hash = $options['_hash'];
            unset($options['_hash']);
        }
        $this->options = array_merge(static::$defaultOptions, $options);
    }

    /**
     * Resolves request parameters that might be used in options.
     */
    private function resolveRequestParameters(): void {
        $request = Request::getInstance();

        foreach ($this->options as $key => &$value) {
            if (is_string($value) && str_starts_with($value, 'url')) {
                $paramKey = str_contains($value, '.') ? substr($value, strrpos($value, '.') + 1) : $key;
                $value = $request->request[$paramKey] ?? $request->get[$paramKey] ?? null;
            }
        }
    }

    /**
     * Generates a unique cache key for this component.
     */
    public function cacheKey(): string {
        if (!isset($this->cacheKey)) {
            $className = strtolower(str_replace(['Vvveb\Plugins\\', 'Vvveb\Component\\', '\Component\\'], '', get_class($this)));
            $this->cacheKey = $className . '.' . md5(serialize($this->options));
        }
        return $this->cacheKey;
    }

    /**
     * Retrieves results from cache.
     */
    public function results(): mixed {
        return Cache::getInstance()->get($this->cacheKey);
    }

    /**
     * Generates and stores cache for component results.
     */
    public function generateCache(mixed $results): bool {
        $cache = Cache::getInstance();
        $expire = $_SERVER['REQUEST_TIME'] + $this->cacheExpire;

        $results = $results ?: 0;
        $cache->set($this->cacheKey, $results, $expire + COMPONENT_CACHE_EXPIRE);

        return $cache->set('expire_' . $this->cacheKey, $expire, $expire + COMPONENT_CACHE_EXPIRE);
    }
}
