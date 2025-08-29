<?php
// Load environment variables
require_once __DIR__ . '/../autoload.php';
use Defuse\Crypto\Key;

// Generate a new encryption key
$key = Key::createNewRandomKey();

// Save the key to a secure location
file_put_contents(ROOT_DIR.DIRECTORY_SEPARATOR.'encryption.key', $key->saveToAsciiSafeString());
