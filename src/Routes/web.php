<?php

declare(strict_types=1);

/**
 * Web Routes (UI Pages)
 * Health & Safety Inspections System
 */

// Define view paths
$viewsPath = __DIR__ . '/../../public/views';

// Simple router for web pages
$webRoutes = [
    '/' => 'dashboard/index.php',
    '/login' => 'auth/login.php',
    '/register' => 'auth/register.php',
    '/forgot-password' => 'auth/forgot-password.php',
    '/dashboard' => 'dashboard/index.php',
    '/inspections' => 'inspections/list.php',
    '/inspections/create' => 'inspections/create.php',
    '/inspections/calendar' => 'inspections/calendar.php',
    '/establishments' => 'establishments/list.php',
    '/establishments/create' => 'establishments/create.php',
    '/violations' => 'violations/list.php',
    '/certificates' => 'certificates/list.php',
    '/certificates/verify' => 'certificates/verify.php',
    '/reports' => 'reports/index.php',
];

// Match route
if (isset($webRoutes[$requestUri])) {
    $viewFile = $viewsPath . '/' . $webRoutes[$requestUri];
    
    if (file_exists($viewFile)) {
        // Check authentication for protected routes
        $publicRoutes = ['/login', '/register', '/forgot-password', '/certificates/verify'];
        
        if (!in_array($requestUri, $publicRoutes)) {
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                header('Location: /HEALTHANDSAFETYINSPECTION/public/login');
                exit;
            }
        }
        
        include $viewFile;
    } else {
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>View file not found: ' . htmlspecialchars($webRoutes[$requestUri]) . '</p>';
    }
} else {
    http_response_code(404);
    include $viewsPath . '/errors/404.php';
}
