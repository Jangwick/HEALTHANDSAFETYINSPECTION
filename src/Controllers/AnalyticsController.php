<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\AnalyticsService;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class AnalyticsController
{
    private AnalyticsService $analyticsService;
    private RoleMiddleware $roleMiddleware;

    public function __construct(
        AnalyticsService $analyticsService,
        RoleMiddleware $roleMiddleware
    ) {
        $this->analyticsService = $analyticsService;
        $this->roleMiddleware = $roleMiddleware;
    }

    public function dashboard(): void
    {
        $this->roleMiddleware->requirePermission('analytics.read');

        $metrics = $this->analyticsService->getDashboardMetrics();
        Response::success(['metrics' => $metrics]);
    }

    public function inspectionTrends(): void
    {
        $this->roleMiddleware->requirePermission('analytics.read');

        $months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
        $trends = $this->analyticsService->getInspectionTrends($months);
        
        Response::success(['trends' => $trends]);
    }

    public function violationsByCategory(): void
    {
        $this->roleMiddleware->requirePermission('analytics.read');

        $violations = $this->analyticsService->getViolationsByCategory();
        Response::success(['violations' => $violations]);
    }

    public function complianceReport(): void
    {
        $this->roleMiddleware->requirePermission('reports.generate');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $report = $this->analyticsService->getComplianceReport($startDate, $endDate);
        
        Response::success([
            'report' => $report,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    public function violationTrends(): void
    {
        $this->roleMiddleware->requirePermission('analytics.read');

        $months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
        $trends = $this->analyticsService->getViolationTrends($months);
        
        Response::success(['trends' => $trends]);
    }

    public function complianceAnalytics(): void
    {
        $this->roleMiddleware->requirePermission('analytics.read');

        $statsByType = $this->analyticsService->getComplianceStatsByType();
        $statsByArea = $this->analyticsService->getComplianceStatsByArea();
        $trends = $this->analyticsService->getComplianceTrends();

        Response::success([
            'by_type' => $statsByType,
            'by_area' => $statsByArea,
            'trends' => $trends
        ]);
    }
}
