<?php

/**
 * SA Technology
 *
 * Copyright (C) 2022  Shofiul Alam
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

defined('V_VERSION') || define('V_VERSION', '0.0.7');
defined('APP') || define('APP', 'app');

/*
Check .sql files for changes and recompile, use on dev only
*/
defined('SQL_CHECK') || define('SQL_CHECK', true);

/*
 Page cache needs web server support for maximum performance, make sure that apache has .htaccess support and nginx is configured according to included nginx.conf
 */
defined('PAGE_CACHE') || define('PAGE_CACHE', true);

/*
Disable on production to hide error messages, if enabled it will show detailed error messages
Warning: Enabling debug will decrease performance
*/
defined('DEBUG') || define('DEBUG', true);
defined('VTPL_DEBUG') || define('VTPL_DEBUG', false);

/*
If enabled if a plugin generates an error it will be automatically disabled
*/
defined('DISABLE_PLUGIN_ON_ERORR') || define('DISABLE_PLUGIN_ON_ERORR', false);

//no trailing slash for subdir path
//defined('V_SUBDIR_INSTALL') || define('V_SUBDIR_INSTALL', '/vvveb');
defined('V_SUBDIR_INSTALL') || define('V_SUBDIR_INSTALL', false);

//if shared session is enabled then user session (login) will work on all subdomains on multisite installations
defined('V_SHARED_SESSION') || define('V_SHARED_SESSION', false);

defined('LOG_SQL_QUERIES') || define('LOG_SQL_QUERIES', false);

defined('REST') || define('REST', true);
defined('GRAPHQL') || define('GRAPHQL', false);

if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

defined('DIR_CONFIG') || define('DIR_CONFIG', DIR_ROOT . 'config' . DS);
defined('DIR_SYSTEM') || define('DIR_SYSTEM', DIR_ROOT . 'system' . DS);
defined('PAGE_CACHE_DIR') || define('PAGE_CACHE_DIR', 'page-cache' . DS);
defined('DIR_CACHE') || define('DIR_CACHE', DIR_ROOT . join(DS, ['storage', 'cache']) . DS);
defined('DIR_PLUGINS') || define('DIR_PLUGINS', DIR_ROOT . join(DS, ['src', 'Plugins']). DS);
defined('DIR_COMPILED_TEMPLATES') || define('DIR_COMPILED_TEMPLATES', DIR_STORAGE . 'compiled-templates' . DS);
defined('DIR_BACKUP') || define('DIR_BACKUP', DIR_STORAGE . 'backup' . DS);
defined('DIR_THEMES') || define('DIR_THEMES', DIR_ROOT . join(DS, ['themes']) . DS);
defined('DIR_PUBLIC') || define('DIR_PUBLIC', DIR_ROOT . 'public' . DS);
defined('DIR_APP') || define('DIR_APP', DIR_ROOT . APP . DS);
defined('DIR_TEMPLATE') || define('DIR_TEMPLATE', DIR_ROOT . 'templates' . DS);
defined('DIR_MEDIA') || define('DIR_MEDIA', DIR_PUBLIC . 'media' . DS);

if (APP == 'app') {
    defined('DIR_THEME') || define('DIR_THEME', ROOT_DIR . DS . join(DS, ['src', 'themes']) . DS);
} else {
    define('DIR_THEME', DIR_ROOT . 'public' . DS . APP . DS);
}
if (! isset($PUBLIC_PATH)) {
    $PUBLIC_PATH = '/public/';
}

if (! isset($PUBLIC_THEME_PATH)) {
    $PUBLIC_THEME_PATH = '/public/';
}
if (! defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $PUBLIC_PATH);
    define('PUBLIC_THEME_PATH', (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $PUBLIC_THEME_PATH);
}







defined('CDATA_START') || define('CDATA_START', '<![CDATA[');
defined('CDATA_END') || define('CDATA_END', ']]>');

defined('SITE_URL') || define('SITE_URL', $_SERVER['HTTP_HOST'] ?? 'localhost');
defined('SITE_ID') || define('SITE_ID', 1);

defined('DB_ENGINE') || define('DB_ENGINE', '');
