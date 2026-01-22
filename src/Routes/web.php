<?php

declare(strict_types=1);

use HealthSafety\Utils\Logger;

/**
 * Web Routes (UI Pages)
 * Health & Safety Inspections System
 */

// Define paths
$viewsPath = __DIR__ . '/../../public/views';
$publicPath = __DIR__ . '/../../public';

// Simple router for web pages
$webRoutes = [
    '/' => ['type' => 'public', 'file' => 'dashboard.php'],
    '/dashboard' => ['type' => 'public', 'file' => 'dashboard.php'],
    '/login' => ['type' => 'view', 'file' => 'auth/login.php'],
    '/views/auth/login.php' => ['type' => 'view', 'file' => 'auth/login.php'],
    '/register' => ['type' => 'view', 'file' => 'auth/register.php'],
    '/forgot-password' => ['type' => 'view', 'file' => 'auth/forgot-password.php'],
    '/inspections' => ['type' => 'view', 'file' => 'inspections/list.php'],
    '/scheduling' => ['type' => 'view', 'file' => 'inspections/scheduling.php'],
    '/inspections/scheduling' => ['type' => 'view', 'file' => 'inspections/scheduling.php'],
    '/inspections/create' => ['type' => 'view', 'file' => 'inspections/create.php'],
    '/inspections/view' => ['type' => 'view', 'file' => 'inspections/view.php'],
    '/inspections/view.php' => ['type' => 'view', 'file' => 'inspections/view.php'],
    '/inspections/conduct' => ['type' => 'view', 'file' => 'inspections/conduct.php'],
    '/inspections/conduct.php' => ['type' => 'view', 'file' => 'inspections/conduct.php'],
    '/inspections/report' => ['type' => 'view', 'file' => 'inspections/report.php'],
    '/inspections/list.php' => ['type' => 'view', 'file' => 'inspections/list.php'],
    '/establishments' => ['type' => 'view', 'file' => 'establishments/list.php'],
    '/establishments/list' => ['type' => 'view', 'file' => 'establishments/list.php'],
    '/establishments/list.php' => ['type' => 'view', 'file' => 'establishments/list.php'],
    '/establishments/create' => ['type' => 'view', 'file' => 'establishments/create.php'],
    '/establishments/view' => ['type' => 'view', 'file' => 'establishments/view.php'],
    '/establishments/view.php' => ['type' => 'view', 'file' => 'establishments/view.php'],
    '/establishments/view.php' => ['type' => 'view', 'file' => 'establishments/view.php'],
    '/establishments/edit.php' => ['type' => 'view', 'file' => 'establishments/edit.php'],
    '/analytics' => ['type' => 'view', 'file' => 'establishments/statistics.php'],
    '/establishments/statistics' => ['type' => 'view', 'file' => 'establishments/statistics.php'],
    '/establishments/statistics.php' => ['type' => 'view', 'file' => 'establishments/statistics.php'],
    '/certificates/list' => ['type' => 'view', 'file' => 'certificates/list.php'],
    '/certificates/list.php' => ['type' => 'view', 'file' => 'certificates/list.php'],
    '/certificates/issue' => ['type' => 'view', 'file' => 'certificates/issue.php'],
    '/certificates/issue.php' => ['type' => 'view', 'file' => 'certificates/issue.php'],
    '/certificates/view' => ['type' => 'view', 'file' => 'certificates/view.php'],
    '/certificates/view.php' => ['type' => 'view', 'file' => 'certificates/view.php'],
    '/violations/view' => ['type' => 'view', 'file' => 'violations/view.php'],
    '/violations/view.php' => ['type' => 'view', 'file' => 'violations/view.php'],
    '/violations' => ['type' => 'view', 'file' => 'violations/list.php'],
    '/violations/list' => ['type' => 'view', 'file' => 'violations/list.php'],
    '/certificates' => ['type' => 'view', 'file' => 'certificates/list.php'],
    '/inspectors' => ['type' => 'view', 'file' => 'inspectors/list.php'],
    '/inspectors/list.php' => ['type' => 'view', 'file' => 'inspectors/list.php'],
    '/integrations' => ['type' => 'view', 'file' => 'integrations/hub.php'],
    '/integration/hub' => ['type' => 'view', 'file' => 'integrations/hub.php'],
    '/integration/hub.php' => ['type' => 'view', 'file' => 'integrations/hub.php'],
    '/registration' => ['type' => 'view', 'file' => 'auth/register.php'],
    '/index.php' => ['type' => 'public', 'file' => 'dashboard.php'],
    '/index' => ['type' => 'public', 'file' => 'dashboard.php'],
];

// Clean up trailing slashes
$requestUri = rtrim($requestUri, '/');
if ($requestUri === '') {
    $requestUri = '/';
}

// Match route - strip query parameters for route matching
$baseUri = strtok($requestUri, '?');

if (defined('APP_DEBUG') && APP_DEBUG) {
    error_log("BASE_URI: " . $baseUri);
}

if (isset($webRoutes[$baseUri])) {
    $route = $webRoutes[$baseUri];
    
    // Determine file path based on route type
    if ($route['type'] === 'public') {
        $viewFile = $publicPath . '/' . $route['file'];
    } else {
        $viewFile = $viewsPath . '/' . $route['file'];
    }
    
    if (file_exists($viewFile)) {
        // Check authentication for protected routes
        $publicRoutes = ['/login', '/views/auth/login.php', '/register', '/forgot-password', '/certificates/verify', '/certificates/verify.php'];
        
        if (!in_array($baseUri, $publicRoutes)) {
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                header('Location: login');
                exit;
            }
        }
        
        include $viewFile;
    } else {
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>View file not found: ' . htmlspecialchars($route['file']) . '</p>';
        echo '<p>Looking for: ' . htmlspecialchars($viewFile) . '</p>';
    }
} else {
    http_response_code(404);
    if (file_exists($viewsPath . '/errors/404.php')) {
        include $viewsPath . '/errors/404.php';
    } else {
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>Requested URI: ' . htmlspecialchars($requestUri) . '</p>';
    }
}
