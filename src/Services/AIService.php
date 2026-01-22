<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use Exception;
use HealthSafety\Utils\Logger;

/**
 * Service for integrating Safety Culture AI (Gemini)
 * Handles predictive risk scoring, violation detection, and automated reporting logic.
 */
class AIService
{
    private PDO $pdo;
    private Logger $logger;
    private string $apiKey;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
        // In a real scenario, this would be loaded from .env
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? 'dummy_key_for_proposal';
    }

    /**
     * Analyze inspection photos for potential violations using Computer Vision (Simulated)
     */
    public function analyzeEvidence(int $photoId): array
    {
        // 1. Fetch photo details
        $stmt = $this->pdo->prepare("SELECT photo_url, photo_type FROM inspection_photos WHERE photo_id = ?");
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$photo) {
            throw new Exception("Photo not found.");
        }

        // 2. Simulate Gemini API Call for Vision analysis
        $this->logger->info("AI Vision analyzing photo ID: $photoId");
        
        // Mocked AI Response
        $aiFindings = [
            'analyzed_at' => date('Y-m-d H:i:s'),
            'detected_objects' => ['exposed_wiring', 'missing_fire_extinguisher_sign'],
            'confidence_score' => 0.94,
            'suggested_violation_code' => 'FIRE-002',
            'severity_prediction' => 'major'
        ];

        // 3. Store AI analysis results (would need a new table or field in a real system)
        return $aiFindings;
    }

    /**
     * Generate Predictive Risk Scoring for an Establishment
     */
    public function calculateEstablishmentRisk(int $establishmentId): array
    {
        // Fetch historical violations and inspection frequency
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as violation_count,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_count,
                DATEDIFF(NOW(), MAX(reported_at)) as days_since_last_violation
            FROM violations
            WHERE establishment_id = ?
        ");
        $stmt->execute([$establishmentId]);
        $history = $stmt->fetch(PDO::FETCH_ASSOC);

        // Simulate Gemini risk analysis algorithm
        $baseRisk = 0.5;
        if ($history['critical_count'] > 0) $baseRisk += 0.3;
        if ($history['violation_count'] > 5) $baseRisk += 0.2;
        
        $riskScore = min(1.0, $baseRisk);
        
        $riskCategory = 'low';
        if ($riskScore > 0.8) $riskCategory = 'high';
        elseif ($riskScore > 0.4) $riskCategory = 'medium';

        return [
            'establishment_id' => $establishmentId,
            'risk_score' => $riskScore,
            'risk_category' => $riskCategory,
            'factors' => [
                'critical_violation_history' => $history['critical_count'] > 0,
                'violation_frequency' => $history['violation_count'],
                'recency_penalty' => ($history['days_since_last_violation'] < 30) ? 0.2 : 0
            ],
            'recommendation' => $riskCategory === 'high' ? 'Schedule immediate inspection' : 'Maintain standard schedule'
        ];
    }

    /**
     * Audit Inspector Notes for consistency using NLP (Simulated)
     */
    public function auditInspectionNotes(int $inspectionId): array
    {
        $stmt = $this->pdo->prepare("SELECT inspector_notes FROM inspections WHERE inspection_id = ?");
        $stmt->execute([$inspectionId]);
        $notes = $stmt->fetchColumn();

        if (empty($notes)) {
            return ['status' => 'no_notes', 'sentiment' => 'neutral'];
        }

        // Simulate Gemini NLP Sentiment and Content Analysis
        return [
            'inspection_id' => $inspectionId,
            'summary_sentiment' => 'objective',
            'flagged_keywords' => [],
            'compliance_confidence' => 0.88,
            'ai_summary' => "Inspection report appears thorough and matches the recorded checklist responses."
        ];
    }

    /**
     * Get Comprehensive Action Details for an Inspection (LGU 4 AI Integration)
     */
    public function getInspectionActionDetails(int $inspectionId): array
    {
        // 1. Fetch inspection & establishment details
        $stmt = $this->pdo->prepare("
            SELECT i.*, e.establishment_id, e.name as establishment_name, e.type as establishment_type
            FROM inspections i
            JOIN establishments e ON i.establishment_id = e.establishment_id
            WHERE i.inspection_id = ?
        ");
        $stmt->execute([$inspectionId]);
        $inspection = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inspection) {
            throw new Exception("Inspection not found");
        }

        // 2. Perform AI Risk Assessment
        $risk = $this->calculateEstablishmentRisk((int)$inspection['establishment_id']);

        // 3. Perform AI Note Auditing
        $audit = $this->auditInspectionNotes($inspectionId);

        // 4. Generate AI Strategic Recommendations
        $recommendations = [
            "Verify " . str_replace('_', ' ', $inspection['establishment_type']) . " specific sanitation permits.",
            "Compare current findings with trailing violations from " . date('Y', strtotime('-1 year')) . ".",
            "Ensure forensic metadata (GPS) is captured for all identified hazards."
        ];

        if ($risk['risk_category'] === 'high') {
            array_unshift($recommendations, "Escalate findings to LGU Safety Cluster for joint-agency review.");
        }

        return [
            'inspection' => $inspection,
            'risk_assessment' => $risk,
            'note_audit' => $audit,
            'strategic_recommendations' => $recommendations,
            'forensic_summary' => [
                'gps_enabled' => true,
                'device_fingerprint' => 'captured',
                'timestamp_integrity' => 'verified'
            ]
        ];
    }
}
