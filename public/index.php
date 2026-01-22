<?php

declare(strict_types=1);

/**
 * Application Entry Point
 * Health & Safety Inspections System
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
        }
    }
}

// Load configuration
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/bootstrap.php';

// Import classes
use HealthSafety\Utils\Response;
use HealthSafety\Utils\Logger;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get request information
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Clean up the URI - remove common path prefixes
$requestUri = str_replace('/HEALTHANDSAFETYINSPECTION/public', '', $requestUri);
$requestUri = str_replace('/public', '', $requestUri);

// Ensure URI starts with /
if ($requestUri === '' || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

error_log("CLEANED_URI: " . $requestUri);

// Log API request
$startTime = microtime(true);

// Route the request
try {
    // API Routes
    if (strpos($requestUri, '/api/') === 0) {
        // Remove /api prefix but keep the leading slash for the route
        $route = substr($requestUri, 4);
        
        // Get request body for POST/PUT/PATCH
        $requestBody = null;
        if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $requestBody = json_decode(file_get_contents('php://input'), true);
            } else {
                $requestBody = $_POST;
            }
        }
        
        // Route to appropriate controller
        require_once __DIR__ . '/../src/Routes/api.php';
        
    } 
    // Web Routes (UI pages)
    else {
        // Route to appropriate view
        require_once __DIR__ . '/../src/Routes/web.php';
    }
    
} catch (\Throwable $e) {
    Logger::error('Uncaught exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    if (strpos($requestUri, '/api/') === 0) {
        Response::serverError(
            APP_DEBUG ? $e->getMessage() : 'An unexpected error occurred',
            APP_DEBUG ? ['trace' => $e->getTraceAsString()] : null
        );
    } else {
        http_response_code(500);
        $error = $e->getMessage();
        include __DIR__ . '/views/errors/500.php';
    }
}

// Log API request completion
$duration = microtime(true) - $startTime;
if (strpos($requestUri, '/api/') === 0) {
    Logger::api(
        $requestMethod,
        $requestUri,
        http_response_code(),
        ['duration' => round($duration * 1000, 2) . 'ms']
    );
}
