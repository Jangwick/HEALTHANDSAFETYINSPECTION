<?php

declare(strict_types=1);

/**
 * Application Bootstrap
 * Health & Safety Inspections System
 */

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/error.log');

// Timezone
date_default_timezone_set(APP_TIMEZONE);

// Session Configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
ini_set('session.name', SESSION_NAME);

// Create required directories
$directories = [
    LOG_PATH,
    UPLOAD_PATH,
    CACHE_PATH,
    UPLOAD_PATH . '/inspections',
    UPLOAD_PATH . '/violations',
    UPLOAD_PATH . '/certificates',
    UPLOAD_PATH . '/establishments',
    UPLOAD_PATH . '/documents',
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Composer Autoloader (if using Composer)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Custom Autoloader for src/ classes
spl_autoload_register(function ($class) {
    // Support both HealthSafety and App namespaces
    $prefixes = ['HealthSafety\\', 'App\\'];
    $base_dir = ROOT_PATH . '/src/';
    
    foreach ($prefixes as $prefix) {
        // Check if class uses the namespace prefix
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            // Get relative class name
            $relative_class = substr($class, $len);
            
            // Replace namespace separators with directory separators
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            
            // If file exists, require it
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

// CORS Headers (for API)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
    }
    exit(0);
}

// Set JSON response headers for API routes
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    header('Content-Type: application/json; charset=utf-8');
}

// Global Exception Handler
set_exception_handler(function (Throwable $e) {
    error_log("Uncaught Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        http_response_code(HTTP_SERVER_ERROR);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => ERROR_SERVER,
                'message' => APP_DEBUG ? $e->getMessage() : 'An internal error occurred',
                'trace' => APP_DEBUG ? $e->getTraceAsString() : null
            ],
            'meta' => [
                'timestamp' => date('c')
            ]
        ]);
    } else {
        if (APP_DEBUG) {
            echo '<h1>Error</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            echo '<h1>500 - Internal Server Error</h1>';
        }
    }
    exit(1);
});

// Global Error Handler
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    
    throw new ErrorException($message, 0, $severity, $file, $line);
});
