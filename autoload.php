<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

const ROOT_DIR = __DIR__;
const DS = DIRECTORY_SEPARATOR;
defined('DIR_ROOT') || define('DIR_ROOT', __DIR__ . DS);
defined('DIR_CORE') || define('DIR_CORE', ROOT_DIR . DS . join(DS, ['src', 'Core']) . DS);
defined('DIR_PLUGINS') || define('DIR_PLUGINS', ROOT_DIR . DS . join(DS, ['src', 'Plugins']) . DS);

$storage_dir = DIR_ROOT . 'storage' . DS;

if (! is_dir($storage_dir)) {
    @mkdir($storage_dir);
    @mkdir($storage_dir . 'compiled-templates' . DS);
    @mkdir($storage_dir . 'cache');
    @mkdir($storage_dir . 'model');
    @mkdir($storage_dir . join(DS, ['model', 'admin']) . DS);
    @mkdir($storage_dir . join(DS, ['model/app']) . DS);
    @mkdir($storage_dir . join(DS, ['model/install']) . DS);
}

if (is_writable($storage_dir)) {
    define('DIR_STORAGE', $storage_dir);
}
