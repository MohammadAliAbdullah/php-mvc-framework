<?php

namespace App\Core\System;

use ReflectionClass;

class ClassReflector {

    /**
     * Create a ReflectionClass from a PHP file path
     *
     * @param string $filePath The path to the PHP file
     * @return ReflectionClass|null Returns ReflectionClass if successful, otherwise null
     */
    public static function createFromFile(string $filePath): ?ReflectionClass
    {
        if (!file_exists($filePath)) {
            return null; // File does not exist
        }

        // Extract the full class name (namespace + class name)
        $className = self::getClassFromFile($filePath);

        if ($className && class_exists($className)) {
            return new \ReflectionClass($className);
        }

        return null;
    }

    /**
     * Extract the fully qualified class name (namespace + class) from a PHP file
     *
     * @param string $filePath Path to the PHP file
     * @return string|null Fully qualified class name or null if not found
     */
    private static function getClassFromFile(string $filePath): ?string
    {
        $namespace = '';
        $className = '';

        $handle = fopen($filePath, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                // Find the namespace
                if (str_starts_with($line, 'namespace ')) {
                    $namespace = trim(str_replace(['namespace', ';'], '', $line));
                }

                // Find the class name
                if (preg_match('/\bclass\s+(\w+)/', $line, $matches)) {
                    $className = $matches[1];
                    break; // Stop after finding class name
                }
            }
            fclose($handle);
        }

        return $className ? trim($namespace) . '\\' . trim($className) : null;
    }
}
