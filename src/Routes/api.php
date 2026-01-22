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
    'GET /v1/violations/{id}' => ['ViolationController', 'show'],
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
    'GET /v1/analytics/compliance-analytics' => ['AnalyticsController', 'complianceAnalytics'],
    'GET /v1/analytics/violation-trends' => ['AnalyticsController', 'violationTrends'],
    
    // AI Integration Routes (LGU Enhancement)
    'GET /v1/ai/risk-assessment/{id}' => ['AIController', 'getRiskAssessment'],
    'POST /v1/ai/analyze-evidence' => ['AIController', 'analyzeEvidence'],
    'GET /v1/ai/audit-notes/{id}' => ['AIController', 'auditNotes'],
    'GET /v1/ai/action-details/{id}' => ['AIController', 'getActionDetails'],

    // Cross-Cluster Integration
    'POST /v1/integrations/notify-police' => ['IntegrationController', 'notifyLawEnforcement'],
];

// Match route
$routeKey = $requestMethod . ' ' . $route;

// Debug logging
error_log("API Request: $routeKey");
error_log("Available routes: " . implode(', ', array_keys($routes)));

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
        
        // Try HealthSafety namespace first, then App namespace
        $controllerClass = "HealthSafety\\Controllers\\$controllerName";
        if (!class_exists($controllerClass)) {
            $controllerClass = "App\\Controllers\\$controllerName";
        }
        
        if (class_exists($controllerClass)) {
            // Simple Dependency Injection for Controllers (LGU Enhancement)
            $db = \Database::getConnection();
            $logger = new \HealthSafety\Utils\Logger();
            $validator = new \HealthSafety\Utils\Validator();
            $roleMiddleware = new \HealthSafety\Middleware\RoleMiddleware($db);

            $controller = null;
            
            // Map Controllers to their Dependencies
            switch ($controllerName) {
                case 'AuthController':
                    $authService = new \HealthSafety\Services\AuthService($db, $logger);
                    $jwtHandler = new \HealthSafety\Utils\JWTHandler();
                    $controller = new $controllerClass($authService, $jwtHandler, $validator);
                    break;
                    
                case 'InspectionController':
                    $inspectionService = new \HealthSafety\Services\InspectionService($db, $logger);
                    $controller = new $controllerClass($inspectionService, $validator, $roleMiddleware);
                    break;
                    
                case 'AIController':
                    $aiService = new \HealthSafety\Services\AIService($db, $logger);
                    $controller = new $controllerClass($aiService, $roleMiddleware);
                    break;
                
                case 'ViolationController':
                    $violationService = new \HealthSafety\Services\ViolationService($db, $logger);
                    $controller = new $controllerClass($violationService, $validator, $roleMiddleware);
                    break;
                
                case 'EstablishmentController':
                    $establishmentService = new \HealthSafety\Services\EstablishmentService($db, $logger);
                    $controller = new $controllerClass($establishmentService, $validator, $roleMiddleware);
                    break;

                case 'CertificateController':
                    $certificateService = new \HealthSafety\Services\CertificateService($db, $logger);
                    $controller = new $controllerClass($certificateService, $validator, $roleMiddleware);
                    break;
                
                case 'IntegrationController':
                    $integrationService = new \HealthSafety\Services\IntegrationService($db, $logger);
                    $controller = new $controllerClass($integrationService, $validator, $roleMiddleware);
                    break;

                case 'AnalyticsController':
                    $analyticsService = new \HealthSafety\Services\AnalyticsService($db, $logger);
                    $controller = new $controllerClass($analyticsService, $roleMiddleware);
                    break;
                    
                default:
                    try {
                        $controller = new $controllerClass();
                    } catch (\ArgumentCountError $e) {
                        Response::serverError("DI Failure: Controller $controllerName requires dependencies not mapped in api.php");
                        return;
                    }
                    break;
            }
            
            if ($controller && method_exists($controller, $methodName)) {
                // Merge params with request body
                $data = array_merge($params, $requestBody ?? [], $_GET);
                
                call_user_func([$controller, $methodName], $data);
            } else {
                Response::notFound("Method $methodName not found in $controllerName");
            }
        } else {
            Response::notFound("Controller $controllerClass not found");
        }
    }
} else {
    Response::notFound("Endpoint not found: $routeKey");
}
