<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use HealthSafety\Utils\Logger;

class ViolationService
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getViolations(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'v.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['severity'])) {
            $where[] = 'v.severity = :severity';
            $params['severity'] = $filters['severity'];
        }

        if (!empty($filters['establishment_id'])) {
            $where[] = 'v.establishment_id = :establishment_id';
            $params['establishment_id'] = $filters['establishment_id'];
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM violations v WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT 
                v.*,
                e.name as establishment_name,
                i.reference_number as inspection_reference,
                vc.description as violation_description
            FROM violations v
            LEFT JOIN establishments e ON v.establishment_id = e.establishment_id
            LEFT JOIN inspections i ON v.inspection_id = i.inspection_id
            LEFT JOIN violation_codes vc ON v.violation_code = vc.code
            WHERE $whereClause
            ORDER BY v.reported_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $violations,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    public function createViolation(array $data): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO violations (
                inspection_id, establishment_id, violation_code,
                category, description, severity,
                corrective_action_required, corrective_action_deadline,
                status, evidence_photos, reported_by, reported_at
            ) VALUES (
                :inspection_id, :establishment_id, :violation_code,
                :category, :description, :severity,
                :corrective_action_required, :corrective_action_deadline,
                'open', :evidence_photos, :reported_by, NOW()
            )
        ");

        $stmt->execute([
            'inspection_id' => $data['inspection_id'],
            'establishment_id' => $data['establishment_id'],
            'violation_code' => $data['violation_code'] ?? null,
            'category' => $data['category'],
            'description' => $data['description'],
            'severity' => $data['severity'],
            'corrective_action_required' => $data['corrective_action_required'] ?? null,
            'corrective_action_deadline' => $data['corrective_action_deadline'] ?? null,
            'evidence_photos' => isset($data['evidence_photos']) ? json_encode($data['evidence_photos']) : null,
            'reported_by' => $data['reported_by']
        ]);

        $violationId = (int)$this->pdo->lastInsertId();
        
        // Enhance violation with citation data (QR Code ready)
        $this->generateCitationForViolation($violationId);

        // Sync establishment compliance status
        $this->syncEstablishmentCompliance($data['establishment_id']);

        $this->logger->info("Violation created with digital citation", ['violation_id' => $violationId]);

        return $this->getViolationById($violationId);
    }

    /**
     * Generate formal digital citation with QR identifier
     */
    private function generateCitationForViolation(int $violationId): void
    {
        // In a real system, this would generate a unique hash for a QR code link 
        // leading to the official LGU violation payment/appeal portal.
        $citationHash = bin2hex(random_bytes(16));
        
        $stmt = $this->pdo->prepare("
            UPDATE violations 
            SET resolution_notes = CONCAT('Citation QR Hash: ', ?)
            WHERE violation_id = ?
        ");
        $stmt->execute([$citationHash, $violationId]);
        
        $this->logger->info("Digital citation generated for violation ID: $violationId", ['hash' => $citationHash]);
    }

    public function getViolationById(int $violationId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*, e.name as establishment_name
            FROM violations v
            LEFT JOIN establishments e ON v.establishment_id = e.establishment_id
            WHERE v.violation_id = :violation_id
        ");

        $stmt->execute(['violation_id' => $violationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function resolveViolation(int $violationId, array $data): array
    {
        $stmt = $this->pdo->prepare("
            UPDATE violations 
            SET status = 'resolved',
                resolution_date = NOW(),
                resolution_notes = :resolution_notes,
                resolved_by = :resolved_by
            WHERE violation_id = :violation_id
        ");

        $stmt->execute([
            'violation_id' => $violationId,
            'resolution_notes' => $data['resolution_notes'] ?? null,
            'resolved_by' => $data['resolved_by']
        ]);

        // Get establishment ID to sync
        $v = $this->getViolationById($violationId);
        if ($v) {
            $this->syncEstablishmentCompliance($v['establishment_id']);
        }

        $this->logger->info("Violation resolved", ['violation_id' => $violationId]);
        return $v;
    }

    /**
     * Helper to sync establishment compliance status
     */
    private function syncEstablishmentCompliance(int $establishmentId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE establishments e
            SET e.compliance_status = CASE 
                WHEN (SELECT COUNT(*) FROM violations WHERE establishment_id = ? AND status = 'open' AND severity = 'critical') > 0 THEN 'non_compliant'
                ELSE 'compliant'
            END,
            e.updated_at = NOW()
            WHERE e.establishment_id = ?
        ");
        $stmt->execute([$establishmentId, $establishmentId]);
    }

    /**
     * Get violation by ID
     */
    public function getViolationById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*, e.name as establishment_name
            FROM violations v
            LEFT JOIN establishments e ON v.establishment_id = e.establishment_id
            WHERE v.violation_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
