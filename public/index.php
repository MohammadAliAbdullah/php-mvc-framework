<?php

declare(strict_types=1);

use App\Core\App\Kernel;
use App\Core\Constants\Constants;
use App\Core\Http\Request;

// Start output buffering to prevent early header sending
ob_start();

require_once __DIR__ . '/../autoload.php';

// Set up error handling BEFORE any output to prevent automatic header sending
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Set up exception handler to prevent automatic output
set_exception_handler(function($exception) {
    // Clear any output buffer
    ob_clean();
    
    // Log the exception for debugging
    error_log("Uncaught exception: " . $exception->getMessage());
    error_log("File: " . $exception->getFile() . " Line: " . $exception->getLine());
    error_log("Stack trace: " . $exception->getTraceAsString());
    
    // Create a basic error response without CORS headers
    // Let the middleware handle CORS
    header('Content-Type: application/json');
    http_response_code(500);
    
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
    exit;
});

// Path to the plugins folder
$pluginsPath = __DIR__ . '/../plugins';

//// Find all env.php files in the plugins directory and subdirectories
//$envFiles = glob($pluginsPath . '/*/env.php');
//
//foreach ($envFiles as $envFile) {
//    if (file_exists($envFile)) {
//        include_once $envFile;
//    }
//}

require_once __DIR__ . '/../src/Core/System/utils/functions.php';

// 1. Create or retrieve the Kernel (which sets up the container, registers providers, etc.).
$kernel = new Kernel();

// 2. Handle the Request through the Kernel (routing, middleware, etc.) to get a Response.
$response = $kernel->handle();

// 3. Send the Response (sets status code, headers, and echoes content).
$response->send();

// End output buffering and flush
ob_end_flush();



