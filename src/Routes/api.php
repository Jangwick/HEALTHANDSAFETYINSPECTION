<?php

declare(strict_types=1);

/**
 * API Routes
 * Health & Safety Inspections System
 */

use HealthSafety\Utils\Response;

// Simple router
$routes = [
    // Health check
    'GET /v1/health' => function() {
        Response::success([
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => APP_VERSION,
        ]);
    },
    
    // Authentication routes (will be implemented)
    'POST /v1/auth/register' => ['AuthController', 'register'],
    'POST /v1/auth/login' => ['AuthController', 'login'],
    'POST /v1/auth/logout' => ['AuthController', 'logout'],
    'POST /v1/auth/forgot-password' => ['AuthController', 'forgotPassword'],
    'POST /v1/auth/reset-password' => ['AuthController', 'resetPassword'],
    'GET /v1/auth/verify-email/{token}' => ['AuthController', 'verifyEmail'],
    'GET /v1/auth/me' => ['AuthController', 'me'],
    'PUT /v1/auth/profile' => ['AuthController', 'updateProfile'],
    'PUT /v1/auth/change-password' => ['AuthController', 'changePassword'],
    'POST /v1/auth/refresh-token' => ['AuthController', 'refreshToken'],
    
    // Inspections
    'GET /v1/inspections' => ['InspectionController', 'index'],
    'POST /v1/inspections' => ['InspectionController', 'create'],
    'GET /v1/inspections/{id}' => ['InspectionController', 'show'],
    'PUT /v1/inspections/{id}' => ['InspectionController', 'update'],
    'DELETE /v1/inspections/{id}' => ['InspectionController', 'delete'],
    'POST /v1/inspections/{id}/start' => ['InspectionController', 'start'],
    'POST /v1/inspections/{id}/complete' => ['InspectionController', 'complete'],
    'POST /v1/inspections/{id}/upload-photo' => ['InspectionController', 'uploadPhoto'],
    
    // Establishments
    'GET /v1/establishments' => ['EstablishmentController', 'index'],
    'POST /v1/establishments' => ['EstablishmentController', 'create'],
    'GET /v1/establishments/{id}' => ['EstablishmentController', 'show'],
    'PUT /v1/establishments/{id}' => ['EstablishmentController', 'update'],
    'GET /v1/establishments/{id}/inspection-history' => ['EstablishmentController', 'inspectionHistory'],
    'POST /v1/establishments/{id}/suspend' => ['EstablishmentController', 'suspend'],
    
    // Violations
    'GET /v1/violations' => ['ViolationController', 'index'],
    'POST /v1/inspections/{id}/violations' => ['ViolationController', 'create'],
    'PUT /v1/violations/{id}' => ['ViolationController', 'update'],
    'POST /v1/violations/{id}/resolve' => ['ViolationController', 'resolve'],
    'GET /v1/violations/{id}/follow-ups' => ['ViolationController', 'followUps'],
    
    // Certificates
    'GET /v1/certificates' => ['CertificateController', 'index'],
    'POST /v1/inspections/{id}/issue-certificate' => ['CertificateController', 'issue'],
    'GET /v1/certificates/{id}' => ['CertificateController', 'show'],
    'GET /v1/certificates/{id}/download' => ['CertificateController', 'download'],
    'POST /v1/certificates/{id}/revoke' => ['CertificateController', 'revoke'],
    'GET /v1/certificates/verify/{number}' => ['CertificateController', 'verify'],
    
    // Analytics
    'GET /v1/analytics/dashboard' => ['AnalyticsController', 'dashboard'],
    'GET /v1/analytics/compliance-report' => ['AnalyticsController', 'complianceReport'],
    'GET /v1/analytics/violation-trends' => ['AnalyticsController', 'violationTrends'],
];

// Match route
$routeKey = $requestMethod . ' ' . $route;

// Handle route parameters (e.g., {id})
$matchedRoute = null;
$params = [];

foreach ($routes as $pattern => $handler) {
    $patternRegex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
    $patternRegex = '#^' . $patternRegex . '$#';
    
    if (preg_match($patternRegex, $routeKey, $matches)) {
        $matchedRoute = $handler;
        
        // Extract parameter names
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        
        // Map parameter values
        for ($i = 0; $i < count($paramNames[1]); $i++) {
            $params[$paramNames[1][$i]] = $matches[$i + 1];
        }
        
        break;
    }
}

// Execute route
if ($matchedRoute !== null) {
    if (is_callable($matchedRoute)) {
        // Direct callable
        call_user_func($matchedRoute);
    } elseif (is_array($matchedRoute)) {
        // Controller method
        [$controllerName, $methodName] = $matchedRoute;
        $controllerClass = "HealthSafety\\Controllers\\$controllerName";
        
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            
            if (method_exists($controller, $methodName)) {
                // Merge params with request body
                $data = array_merge($params, $requestBody ?? [], $_GET);
                
                call_user_func([$controller, $methodName], $data);
            } else {
                Response::notFound("Method not found");
            }
        } else {
            Response::notFound("Controller not found");
        }
    }
} else {
    Response::notFound("Endpoint not found: $routeKey");
}
