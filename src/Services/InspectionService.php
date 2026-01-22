<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use HealthSafety\Utils\Logger;

class InspectionService
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Get all inspections with filters
     */
    public function getInspections(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'i.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['inspection_type'])) {
            $where[] = 'i.inspection_type = :inspection_type';
            $params['inspection_type'] = $filters['inspection_type'];
        }

        if (!empty($filters['inspector_id'])) {
            $where[] = 'i.inspector_id = :inspector_id';
            $params['inspector_id'] = $filters['inspector_id'];
        }

        if (!empty($filters['establishment_id'])) {
            $where[] = 'i.establishment_id = :establishment_id';
            $params['establishment_id'] = $filters['establishment_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'i.scheduled_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'i.scheduled_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM inspections i WHERE $whereClause
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Get inspections
        $stmt = $this->pdo->prepare("
            SELECT 
                i.*,
                e.name as establishment_name,
                e.type as establishment_type,
                e.address_street,
                e.address_barangay,
                CONCAT(u.first_name, ' ', u.last_name) as inspector_name
            FROM inspections i
            LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
            LEFT JOIN inspectors ins ON i.inspector_id = ins.inspector_id
            LEFT JOIN users u ON ins.user_id = u.user_id
            WHERE $whereClause
            ORDER BY i.scheduled_date DESC, i.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $inspections,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Get inspection by ID
     */
    public function getInspectionById(int $inspectionId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                i.*,
                e.name as establishment_name,
                e.type as establishment_type,
                e.address_street,
                e.address_barangay,
                e.address_city,
                e.owner_name,
                e.owner_contact,
                CONCAT(u.first_name, ' ', u.last_name) as inspector_name,
                ins.badge_number,
                u.email as inspector_email,
                u.phone as inspector_phone
            FROM inspections i
            LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
            LEFT JOIN inspectors ins ON i.inspector_id = ins.inspector_id
            LEFT JOIN users u ON ins.user_id = u.user_id
            WHERE i.inspection_id = :inspection_id
        ");

        $stmt->execute(['inspection_id' => $inspectionId]);
        $inspection = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inspection) {
            return null;
        }

        // Get checklist responses
        $inspection['checklist_responses'] = $this->getChecklistResponses($inspectionId);
        
        // Get photos
        $inspection['photos'] = $this->getInspectionPhotos($inspectionId);

        return $inspection;
    }

    /**
     * Create new inspection
     */
    public function createInspection(array $data): array
    {
        // Generate reference number
        $referenceNumber = $this->generateReferenceNumber();

        $stmt = $this->pdo->prepare("
            INSERT INTO inspections (
                reference_number, establishment_id, inspection_type,
                inspector_id, scheduled_date, status, priority,
                checklist_template_id, created_by, created_at
            ) VALUES (
                :reference_number, :establishment_id, :inspection_type,
                :inspector_id, :scheduled_date, 'pending', :priority,
                :checklist_template_id, :created_by, NOW()
            )
        ");

        $stmt->execute([
            'reference_number' => $referenceNumber,
            'establishment_id' => $data['establishment_id'],
            'inspection_type' => $data['inspection_type'],
            'inspector_id' => $data['inspector_id'] ?? null,
            'scheduled_date' => $data['scheduled_date'],
            'priority' => $data['priority'] ?? 'medium',
            'checklist_template_id' => $data['checklist_template_id'] ?? null,
            'created_by' => $data['created_by']
        ]);

        $inspectionId = (int)$this->pdo->lastInsertId();

        $this->logger->info("Inspection created: $referenceNumber", [
            'inspection_id' => $inspectionId,
            'establishment_id' => $data['establishment_id']
        ]);

        return $this->getInspectionById($inspectionId);
    }

    /**
     * Update inspection
     */
    public function updateInspection(int $inspectionId, array $data): array
    {
        $fields = [];
        $params = ['inspection_id' => $inspectionId];

        $allowedFields = [
            'establishment_id', 'inspection_type', 'inspector_id',
            'scheduled_date', 'status', 'priority', 'checklist_template_id',
            'overall_rating', 'inspector_notes', 'weather_conditions'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            throw new \Exception('No valid fields to update');
        }

        $fields[] = 'updated_at = NOW()';
        $fieldsString = implode(', ', $fields);

        $stmt = $this->pdo->prepare("
            UPDATE inspections 
            SET $fieldsString
            WHERE inspection_id = :inspection_id
        ");

        $stmt->execute($params);

        $this->logger->info("Inspection updated", ['inspection_id' => $inspectionId]);

        return $this->getInspectionById($inspectionId);
    }

    /**
     * Start inspection
     */
    public function startInspection(int $inspectionId): array
    {
        $stmt = $this->pdo->prepare("
            UPDATE inspections 
            SET status = 'in_progress',
                actual_start_datetime = NOW(),
                updated_at = NOW()
            WHERE inspection_id = :inspection_id
            AND status = 'pending'
        ");

        $stmt->execute(['inspection_id' => $inspectionId]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception('Inspection cannot be started');
        }

        $this->logger->info("Inspection started", ['inspection_id' => $inspectionId]);

        return $this->getInspectionById($inspectionId);
    }

    /**
     * Prioritize inspections based on AI Risk Scores (LGU Enhancement)
     */
    public function getPrioritizedSchedule(string $date): array
    {
        // Fetch inspections for the date joins with establishment risk data
        $stmt = $this->pdo->prepare("
            SELECT i.*, e.name as establishment_name, e.risk_category, e.compliance_status
            FROM inspections i
            JOIN establishments e ON i.establishment_id = e.establishment_id
            WHERE i.scheduled_date = ? AND i.status = 'pending'
            ORDER BY 
                CASE 
                    WHEN e.risk_category = 'high' THEN 1
                    WHEN i.priority = 'urgent' THEN 2
                    WHEN e.compliance_status = 'non_compliant' THEN 3
                    ELSE 4
                END ASC
        ");
        $stmt->execute([$date]);
        $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->logger->info("AI-Prioritized schedule generated for $date", ['count' => count($inspections)]);
        
        return $inspections;
    }

    /**
     * Complete inspection
     */
    public function completeInspection(int $inspectionId, array $data): array
    {
        $stmt = $this->pdo->prepare("
            UPDATE inspections 
            SET status = 'completed',
                actual_end_datetime = NOW(),
                overall_rating = :overall_rating,
                inspector_notes = :inspector_notes,
                updated_at = NOW()
            WHERE inspection_id = :inspection_id
            AND status = 'in_progress'
        ");

        $stmt->execute([
            'inspection_id' => $inspectionId,
            'overall_rating' => $data['overall_rating'],
            'inspector_notes' => $data['inspector_notes'] ?? null
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception('Inspection cannot be completed');
        }

        $this->logger->info("Inspection completed", [
            'inspection_id' => $inspectionId,
            'rating' => $data['overall_rating']
        ]);

        return $this->getInspectionById($inspectionId);
    }

    /**
     * Upload inspection photo
     */
    public function uploadPhoto(int $inspectionId, array $photoData): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO inspection_photos (
                inspection_id, photo_url, photo_type,
                caption, gps_coordinates, uploaded_at
            ) VALUES (
                :inspection_id, :photo_url, :photo_type,
                :caption, :gps_coordinates, NOW()
            )
        ");

        $stmt->execute([
            'inspection_id' => $inspectionId,
            'photo_url' => $photoData['photo_url'],
            'photo_type' => $photoData['photo_type'] ?? 'general',
            'caption' => $photoData['caption'] ?? null,
            'gps_coordinates' => $photoData['gps_coordinates'] ?? null
        ]);

        $photoId = (int)$this->pdo->lastInsertId();

        return [
            'photo_id' => $photoId,
            'inspection_id' => $inspectionId,
            'photo_url' => $photoData['photo_url'],
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Submit checklist response
     */
    public function submitChecklistResponse(int $inspectionId, array $responses): bool
    {
        foreach ($responses as $response) {
            $stmt = $this->pdo->prepare("
                INSERT INTO inspection_checklist_responses (
                    inspection_id, checklist_item_id, response,
                    notes, evidence_photos, recorded_at
                ) VALUES (
                    :inspection_id, :checklist_item_id, :response,
                    :notes, :evidence_photos, NOW()
                )
                ON DUPLICATE KEY UPDATE
                    response = VALUES(response),
                    notes = VALUES(notes),
                    evidence_photos = VALUES(evidence_photos),
                    recorded_at = NOW()
            ");

            $stmt->execute([
                'inspection_id' => $inspectionId,
                'checklist_item_id' => $response['checklist_item_id'],
                'response' => $response['response'],
                'notes' => $response['notes'] ?? null,
                'evidence_photos' => isset($response['evidence_photos']) 
                    ? json_encode($response['evidence_photos']) 
                    : null
            ]);
        }

        return true;
    }

    /**
     * Generate unique reference number
     */
    private function generateReferenceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Get last number for this month
        $stmt = $this->pdo->prepare("
            SELECT reference_number 
            FROM inspections 
            WHERE reference_number LIKE :pattern
            ORDER BY inspection_id DESC
            LIMIT 1
        ");
        
        $stmt->execute(['pattern' => "HSI-$year-$month%"]);
        $lastRef = $stmt->fetchColumn();

        if ($lastRef) {
            $lastNumber = (int)substr($lastRef, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('HSI-%s-%s-%04d', $year, $month, $newNumber);
    }

    /**
     * Get checklist responses for inspection
     */
    private function getChecklistResponses(int $inspectionId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                icr.*,
                ci.requirement_text,
                ci.category,
                ci.mandatory
            FROM inspection_checklist_responses icr
            JOIN checklist_items ci ON icr.checklist_item_id = ci.item_id
            WHERE icr.inspection_id = :inspection_id
            ORDER BY ci.order_sequence
        ");

        $stmt->execute(['inspection_id' => $inspectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get photos for inspection
     */
    private function getInspectionPhotos(int $inspectionId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM inspection_photos
            WHERE inspection_id = :inspection_id
            ORDER BY uploaded_at DESC
        ");

        $stmt->execute(['inspection_id' => $inspectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get inspection calendar/schedule
     */
    public function getSchedule(string $startDate, string $endDate, ?int $inspectorId = null): array
    {
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $inspectorClause = '';
        if ($inspectorId) {
            $inspectorClause = 'AND i.inspector_id = :inspector_id';
            $params['inspector_id'] = $inspectorId;
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                i.inspection_id,
                i.reference_number,
                i.scheduled_date,
                i.status,
                i.priority,
                e.name as establishment_name,
                CONCAT(u.first_name, ' ', u.last_name) as inspector_name
            FROM inspections i
            LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
            LEFT JOIN inspectors ins ON i.inspector_id = ins.inspector_id
            LEFT JOIN users u ON ins.user_id = u.user_id
            WHERE i.scheduled_date BETWEEN :start_date AND :end_date
            $inspectorClause
            ORDER BY i.scheduled_date, i.priority DESC
        ");

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
