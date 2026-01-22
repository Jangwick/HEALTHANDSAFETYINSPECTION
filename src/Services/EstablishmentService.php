<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use HealthSafety\Utils\Logger;

class EstablishmentService
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * Get all establishments with filters
     */
    public function getEstablishments(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = 'e.type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['compliance_status'])) {
            $where[] = 'e.compliance_status = :compliance_status';
            $params['compliance_status'] = $filters['compliance_status'];
        }

        if (!empty($filters['risk_category'])) {
            $where[] = 'e.risk_category = :risk_category';
            $params['risk_category'] = $filters['risk_category'];
        }

        if (!empty($filters['barangay'])) {
            $where[] = 'e.address_barangay = :barangay';
            $params['barangay'] = $filters['barangay'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(e.name LIKE :search OR e.owner_name LIKE :search OR e.business_permit_number LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM establishments e WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Get establishments
        $stmt = $this->pdo->prepare("
            SELECT 
                e.*,
                (SELECT COUNT(*) FROM inspections WHERE establishment_id = e.establishment_id) as total_inspections,
                (SELECT COUNT(*) FROM violations v JOIN inspections i ON v.inspection_id = i.inspection_id 
                 WHERE i.establishment_id = e.establishment_id AND v.status = 'open') as open_violations
            FROM establishments e
            WHERE $whereClause
            ORDER BY e.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $establishments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $establishments,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Synchronize Compliance Status based on latest inspection and violations
     */
    public function syncComplianceStatus(int $establishmentId): void
    {
        // Check for open critical violations
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM violations 
            WHERE establishment_id = ? AND status = 'open' AND severity = 'critical'
        ");
        $stmt->execute([$establishmentId]);
        $criticalCount = (int)$stmt->fetchColumn();

        $newStatus = ($criticalCount > 0) ? 'non_compliant' : 'compliant';

        $stmt = $this->pdo->prepare("
            UPDATE establishments 
            SET compliance_status = ?, updated_at = NOW() 
            WHERE establishment_id = ?
        ");
        $stmt->execute([$newStatus, $establishmentId]);

        $this->logger->info("Establishment compliance synced", [
            'establishment_id' => $establishmentId,
            'status' => $newStatus
        ]);
    }

    /**
     * Get establishment by ID
     */
    public function getEstablishmentById(int $establishmentId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT e.*
            FROM establishments e
            WHERE e.establishment_id = :establishment_id
        ");

        $stmt->execute(['establishment_id' => $establishmentId]);
        $establishment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$establishment) {
            return null;
        }

        // Get contacts
        $establishment['contacts'] = $this->getEstablishmentContacts($establishmentId);
        
        // Get permits
        $establishment['permits'] = $this->getEstablishmentPermits($establishmentId);
        
        // Get inspection history
        $establishment['recent_inspections'] = $this->getRecentInspections($establishmentId);

        return $establishment;
    }

    /**
     * Create new establishment
     */
    public function createEstablishment(array $data): array
    {
        // Generate reference number
        $referenceNumber = $this->generateReferenceNumber();

        $stmt = $this->pdo->prepare("
            INSERT INTO establishments (
                reference_number, name, type, subtype,
                owner_name, owner_contact, owner_email, owner_user_id,
                manager_name, manager_contact,
                business_permit_number, permit_issue_date, permit_expiry_date,
                address_street, address_barangay, address_city, address_postal_code,
                gps_latitude, gps_longitude,
                employee_count, floor_area_sqm, operating_hours,
                risk_category, compliance_status, status, created_at
            ) VALUES (
                :reference_number, :name, :type, :subtype,
                :owner_name, :owner_contact, :owner_email, :owner_user_id,
                :manager_name, :manager_contact,
                :business_permit_number, :permit_issue_date, :permit_expiry_date,
                :address_street, :address_barangay, :address_city, :address_postal_code,
                :gps_latitude, :gps_longitude,
                :employee_count, :floor_area_sqm, :operating_hours,
                :risk_category, 'non_compliant', 'active', NOW()
            )
        ");

        $stmt->execute([
            'reference_number' => $referenceNumber,
            'name' => $data['name'],
            'type' => $data['type'],
            'subtype' => $data['subtype'] ?? null,
            'owner_name' => $data['owner_name'],
            'owner_contact' => $data['owner_contact'],
            'owner_email' => $data['owner_email'] ?? null,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'manager_name' => $data['manager_name'] ?? null,
            'manager_contact' => $data['manager_contact'] ?? null,
            'business_permit_number' => $data['business_permit_number'] ?? null,
            'permit_issue_date' => $data['permit_issue_date'] ?? null,
            'permit_expiry_date' => $data['permit_expiry_date'] ?? null,
            'address_street' => $data['address_street'],
            'address_barangay' => $data['address_barangay'],
            'address_city' => $data['address_city'],
            'address_postal_code' => $data['address_postal_code'] ?? null,
            'gps_latitude' => $data['gps_latitude'] ?? null,
            'gps_longitude' => $data['gps_longitude'] ?? null,
            'employee_count' => $data['employee_count'] ?? null,
            'floor_area_sqm' => $data['floor_area_sqm'] ?? null,
            'operating_hours' => isset($data['operating_hours']) ? json_encode($data['operating_hours']) : null,
            'risk_category' => $data['risk_category'] ?? 'medium'
        ]);

        $establishmentId = (int)$this->pdo->lastInsertId();

        $this->logger->info("Establishment created: $referenceNumber", [
            'establishment_id' => $establishmentId,
            'name' => $data['name']
        ]);

        return $this->getEstablishmentById($establishmentId);
    }

    /**
     * Update establishment
     */
    public function updateEstablishment(int $establishmentId, array $data): array
    {
        $fields = [];
        $params = ['establishment_id' => $establishmentId];

        $allowedFields = [
            'name', 'type', 'subtype', 'owner_name', 'owner_contact', 'owner_email',
            'manager_name', 'manager_contact', 'business_permit_number',
            'permit_issue_date', 'permit_expiry_date', 'address_street',
            'address_barangay', 'address_city', 'address_postal_code',
            'gps_latitude', 'gps_longitude', 'employee_count', 'floor_area_sqm',
            'risk_category', 'compliance_status', 'status'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['operating_hours'])) {
            $fields[] = 'operating_hours = :operating_hours';
            $params['operating_hours'] = json_encode($data['operating_hours']);
        }

        if (empty($fields)) {
            throw new \Exception('No valid fields to update');
        }

        $fields[] = 'updated_at = NOW()';
        $fieldsString = implode(', ', $fields);

        $stmt = $this->pdo->prepare("
            UPDATE establishments 
            SET $fieldsString
            WHERE establishment_id = :establishment_id
        ");

        $stmt->execute($params);

        $this->logger->info("Establishment updated", ['establishment_id' => $establishmentId]);

        return $this->getEstablishmentById($establishmentId);
    }

    /**
     * Add establishment contact
     */
    public function addContact(int $establishmentId, array $contactData): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO establishment_contacts (
                establishment_id, contact_type, name, position,
                phone, email, is_primary
            ) VALUES (
                :establishment_id, :contact_type, :name, :position,
                :phone, :email, :is_primary
            )
        ");

        $stmt->execute([
            'establishment_id' => $establishmentId,
            'contact_type' => $contactData['contact_type'],
            'name' => $contactData['name'],
            'position' => $contactData['position'] ?? null,
            'phone' => $contactData['phone'],
            'email' => $contactData['email'] ?? null,
            'is_primary' => $contactData['is_primary'] ?? 0
        ]);

        $contactId = (int)$this->pdo->lastInsertId();

        return [
            'contact_id' => $contactId,
            'establishment_id' => $establishmentId,
            'contact_type' => $contactData['contact_type'],
            'name' => $contactData['name']
        ];
    }

    /**
     * Add establishment permit
     */
    public function addPermit(int $establishmentId, array $permitData): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO establishment_permits (
                establishment_id, permit_type, permit_number,
                issuing_authority, issue_date, expiry_date,
                status, document_url
            ) VALUES (
                :establishment_id, :permit_type, :permit_number,
                :issuing_authority, :issue_date, :expiry_date,
                :status, :document_url
            )
        ");

        $stmt->execute([
            'establishment_id' => $establishmentId,
            'permit_type' => $permitData['permit_type'],
            'permit_number' => $permitData['permit_number'],
            'issuing_authority' => $permitData['issuing_authority'] ?? null,
            'issue_date' => $permitData['issue_date'],
            'expiry_date' => $permitData['expiry_date'],
            'status' => $permitData['status'] ?? 'valid',
            'document_url' => $permitData['document_url'] ?? null
        ]);

        $permitId = (int)$this->pdo->lastInsertId();

        return [
            'permit_id' => $permitId,
            'establishment_id' => $establishmentId,
            'permit_type' => $permitData['permit_type']
        ];
    }

    /**
     * Generate unique reference number
     */
    private function generateReferenceNumber(): string
    {
        $year = date('Y');
        
        $stmt = $this->pdo->prepare("
            SELECT reference_number 
            FROM establishments 
            WHERE reference_number LIKE :pattern
            ORDER BY establishment_id DESC
            LIMIT 1
        ");
        
        $stmt->execute(['pattern' => "EST-$year%"]);
        $lastRef = $stmt->fetchColumn();

        if ($lastRef) {
            $lastNumber = (int)substr($lastRef, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('EST-%s-%05d', $year, $newNumber);
    }

    /**
     * Get establishment contacts
     */
    private function getEstablishmentContacts(int $establishmentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM establishment_contacts
            WHERE establishment_id = :establishment_id
            ORDER BY is_primary DESC, contact_type
        ");

        $stmt->execute(['establishment_id' => $establishmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get establishment permits
     */
    private function getEstablishmentPermits(int $establishmentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM establishment_permits
            WHERE establishment_id = :establishment_id
            ORDER BY expiry_date DESC
        ");

        $stmt->execute(['establishment_id' => $establishmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent inspections
     */
    private function getRecentInspections(int $establishmentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                i.*,
                CONCAT(u.first_name, ' ', u.last_name) as inspector_name
            FROM inspections i
            LEFT JOIN inspectors ins ON i.inspector_id = ins.inspector_id
            LEFT JOIN users u ON ins.user_id = u.user_id
            WHERE i.establishment_id = :establishment_id
            ORDER BY i.scheduled_date DESC
            LIMIT 10
        ");

        $stmt->execute(['establishment_id' => $establishmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get establishments for map view
     */
    public function getEstablishmentsForMap(array $filters = []): array
    {
        $where = ['e.gps_latitude IS NOT NULL', 'e.gps_longitude IS NOT NULL'];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = 'e.type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['compliance_status'])) {
            $where[] = 'e.compliance_status = :compliance_status';
            $params['compliance_status'] = $filters['compliance_status'];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $this->pdo->prepare("
            SELECT 
                e.establishment_id,
                e.reference_number,
                e.name,
                e.type,
                e.compliance_status,
                e.risk_category,
                e.gps_latitude,
                e.gps_longitude,
                e.address_street,
                e.address_barangay
            FROM establishments e
            WHERE $whereClause
        ");

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
