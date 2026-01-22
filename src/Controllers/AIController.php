<?php
0
declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\AIService;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class AIController
{
    private AIService $aiService;
    private RoleMiddleware $roleMiddleware;

    public function __construct(AIService $aiService, RoleMiddleware $roleMiddleware)
    {
        $this->aiService = $aiService;
        $this->roleMiddleware = $roleMiddleware;
    }

    /**
     * GET /api/v1/ai/risk-assessment/{establishment_id}
     */
    public function getRiskAssessment(int $establishmentId): void
    {
        $this->roleMiddleware->requirePermission('analytics.read');

        try {
            $assessment = $this->aiService->calculateEstablishmentRisk($establishmentId);
            Response::success($assessment);
        } catch (\Exception $e) {
            Response::error('AI_ERROR', $e->getMessage(), null, 500);
        }
    }

    /**
     * POST /api/v1/ai/analyze-evidence
     */
    public function analyzeEvidence(array $data): void
    {
        $this->roleMiddleware->requirePermission('inspections.update');

        if (!isset($data['photo_id'])) {
            Response::error('VALIDATION_ERROR', 'photo_id is required', null, 400);
            return;
        }

        try {
            $findings = $this->aiService->analyzeEvidence((int)$data['photo_id']);
            Response::success($findings, 'Evidence AI analysis complete');
        } catch (\Exception $e) {
            Response::error('AI_ERROR', $e->getMessage(), null, 500);
        }
    }

    /**
     * GET /api/v1/ai/audit-notes/{inspection_id}
     */
    public function auditNotes(int $inspectionId): void
    {
        $this->roleMiddleware->requirePermission('inspections.read');

        try {
            $audit = $this->aiService->auditInspectionNotes($inspectionId);
            Response::success($audit);
        } catch (\Exception $e) {
            Response::error('AI_ERROR', $e->getMessage(), null, 500);
        }
    }

    /**
     * GET /api/v1/ai/action-details/{inspection_id}
     */
    public function getActionDetails(int $inspectionId): void
    {
        $this->roleMiddleware->requirePermission('inspections.read');

        try {
            $details = $this->aiService->getInspectionActionDetails($inspectionId);
            Response::success($details);
        } catch (\Exception $e) {
            Response::error('AI_ERROR', $e->getMessage(), null, 500);
        }
    }
}
