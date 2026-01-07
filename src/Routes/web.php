<?php

declare(strict_types=1);

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
    '/register' => ['type' => 'view', 'file' => 'auth/register.php'],
    '/forgot-password' => ['type' => 'view', 'file' => 'auth/forgot-password.php'],
    '/inspections' => ['type' => 'view', 'file' => 'inspections/list.php'],
    '/inspections/create' => ['type' => 'view', 'file' => 'inspections/create.php'],
    '/inspections/list.php' => ['type' => 'view', 'file' => 'inspections/list.php'],
    '/establishments' => ['type' => 'view', 'file' => 'establishments/list.php'],
    '/establishments/list' => ['type' => 'view', 'file' => 'establishments/list.php'],
    '/establishments/list.php' => ['type' => 'view', 'file' => 'establishments/list.php'],
    '/establishments/create' => ['type' => 'view', 'file' => 'establishments/create.php'],
    '/establishments/view.php' => ['type' => 'view', 'file' => 'establishments/view.php'],
    '/establishments/edit.php' => ['type' => 'view', 'file' => 'establishments/edit.php'],
    '/establishments/statistics.php' => ['type' => 'view', 'file' => 'establishments/statistics.php'],
    '/certificates' => ['type' => 'view', 'file' => 'certificates/list.php'],
    '/certificates/list.php' => ['type' => 'view', 'file' => 'certificates/list.php'],
    '/certificates/verify' => ['type' => 'view', 'file' => 'certificates/verify.php'],
    '/certificates/verify.php' => ['type' => 'view', 'file' => 'certificates/verify.php'],
    '/certificates/issue.php' => ['type' => 'view', 'file' => 'certificates/issue.php'],
    '/certificates/view.php' => ['type' => 'view', 'file' => 'certificates/view.php'],
];

// Match route - strip query parameters for route matching
$baseUri = strtok($requestUri, '?');

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
        $publicRoutes = ['/login', '/register', '/forgot-password', '/certificates/verify', '/certificates/verify.php'];
        
        if (!in_array($baseUri, $publicRoutes)) {
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                header('Location: /login');
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
