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
use App\Core\Models\User;
use App\Core\System\Cache;
use Illuminate\Contracts\Container\BindingResolutionException;
use function App\Core\System\utils\app;
use function App\Core\System\utils\session as sess;
use App\Core\Http\Request;
use App\Core\View\View;
use App\Core\System\Session;

#[\AllowDynamicProperties]
class ComponentBase {
    public $cacheKey;

    public $cacheExpire = 3600; //seconds

    public static $global;

    protected $options;

    protected $_hash;

    public static $defaultOptions = [];

    function __construct($options = []) {
        $request = app()->make(Request::class);

        if (! self::$global) {
            $user                                  = User::current();
            self::$global['start']               = 0;
            self::$global['site_id']             = $options['site_id']??sess('site_id') ?? (defined('SITE_ID') ? SITE_ID : 0);
            self::$global['user_id']             = $user['user_id'] ?? null;
            self::$global['user_group_id']       = $user['user_group_id'] ?? 1;
            self::$global['language_id']         = $options['language_id']??$request->request['language_id'] ?? sess('language_id') ?? 1;
            self::$global['language']            = $request->request['language'] ?? sess('language') ?? 'en_US';
            self::$global['default_language']    = sess('default_language') ?? 'en_US';
            self::$global['default_language_id'] = sess('default_language_id') ?? 1;
            self::$global['currency_id']         = sess('currency_id') ?? 1;
        }

        static::$defaultOptions = array_merge(self::$global, static::$defaultOptions);

        foreach (['site_id', 'language_id', 'currency_id', 'user_id', 'user_group_id'] as $key) {
            if (! isset(static::$defaultOptions[$key]) || empty(static::$defaultOptions[$key])) {
                static::$defaultOptions[$key] = self::$global[$key];
            }
        }

        if (isset(static::$defaultOptions)) {
            $this->options = $this->filter($options);
        }

        foreach ($this->options as $key => &$value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    if (strpos($val, 'url') === 0) {
                        //check if url parameter is specified ex: url.slug
                        if ($dot = strrpos($val,'.')) {
                            $key = substr($val, $dot + 1);
                        }

                        if (isset($request->request[$key])) {
                            $value = $request->request[$key];

                            break;
                        } else {
                            if (isset($value[1])) {
                                $value = $value[1];
                            } else {
                                $value = null;
                            }
                        }
                    }
                }
            }

            if ($value && is_string($value) && strpos($value, 'url') === 0) {
                //check if url parameter is specified ex: url.slug
                if ($dot = strrpos($value,'.')) {
                    $key = substr($value, $dot + 1);
                }

                $value = (isset($request->request[$key]) ? $request->request[$key] : (isset($request->get[$key]) ? $request->get[$key] : null));
            }
        }
    }



    function filter(&$options) {
        //remove fields not declared in the class
        if (is_array($options)) {
            if (isset($options['_hash'])) {
                $this->_hash = $options['_hash'];
                unset($options['_hash']);
            }
            return array_merge(static::$defaultOptions, $options);
        } else {
            return static::$defaultOptions;
        }
    }

    function results() {
        //check cache
        $cache = Cache::getInstance();

        return $cache->get($this->cacheKey);
    }

    function generateCache($results) {
        $cache  = Cache::getInstance();
        $expire = $_SERVER['REQUEST_TIME'] + $this->cacheExpire;

        if (! $results) {
            $results = 0;
        }
        $cache->set($this->cacheKey, $results, $expire + COMPONENT_CACHE_EXPIRE);

        return $cache->set('expire_' . $this->cacheKey, $expire, $expire + COMPONENT_CACHE_EXPIRE);
    }
}
