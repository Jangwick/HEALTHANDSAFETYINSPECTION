<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use HealthSafety\Utils\Logger;

class CertificateService
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getCertificates(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'c.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['establishment_id'])) {
            $where[] = 'c.establishment_id = :establishment_id';
            $params['establishment_id'] = $filters['establishment_id'];
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM certificates c WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT 
                c.*,
                e.name as establishment_name,
                i.reference_number as inspection_reference
            FROM certificates c
            LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
            LEFT JOIN inspections i ON c.inspection_id = i.inspection_id
            WHERE $whereClause
            ORDER BY c.issue_date DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $certificates,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    public function issueCertificate(array $data): array
    {
        $certificateNumber = $this->generateCertificateNumber();
        $qrData = $this->generateQRData($certificateNumber);

        $stmt = $this->pdo->prepare("
            INSERT INTO certificates (
                certificate_number, establishment_id, inspection_id,
                certificate_type, issue_date, expiry_date, status,
                conditions, issued_by, approved_by, qr_code_data, created_at
            ) VALUES (
                :certificate_number, :establishment_id, :inspection_id,
                :certificate_type, NOW(), :expiry_date, 'valid',
                :conditions, :issued_by, :approved_by, :qr_code_data, NOW()
            )
        ");

        $stmt->execute([
            'certificate_number' => $certificateNumber,
            'establishment_id' => $data['establishment_id'],
            'inspection_id' => $data['inspection_id'],
            'certificate_type' => $data['certificate_type'],
            'expiry_date' => $data['expiry_date'],
            'conditions' => isset($data['conditions']) ? json_encode($data['conditions']) : null,
            'issued_by' => $data['issued_by'],
            'approved_by' => $data['approved_by'] ?? null,
            'qr_code_data' => $qr_data
        ]);

        $certificateId = (int)$this->pdo->lastInsertId();
        $this->logger->info("Certificate issued", ['certificate_id' => $certificateId]);

        return $this->getCertificateById($certificateId);
    }

    public function getCertificateById(int $certificateId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, e.name as establishment_name
            FROM certificates c
            LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
            WHERE c.certificate_id = :certificate_id
        ");

        $stmt->execute(['certificate_id' => $certificateId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function verifyCertificate(string $certificateNumber): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, e.name as establishment_name, e.address_street, e.address_barangay
            FROM certificates c
            LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
            WHERE c.certificate_number = :certificate_number
        ");

        $stmt->execute(['certificate_number' => $certificateNumber]);
        $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($certificate) {
            // Log verification
            $this->logVerification($certificate['certificate_id'], 'web_portal');
        }

        return $certificate ?: null;
    }

    private function generateCertificateNumber(): string
    {
        $year = date('Y');
        $stmt = $this->pdo->prepare("
            SELECT certificate_number 
            FROM certificates 
            WHERE certificate_number LIKE :pattern
            ORDER BY certificate_id DESC
            LIMIT 1
        ");
        
        $stmt->execute(['pattern' => "CERT-$year%"]);
        $lastCert = $stmt->fetchColumn();

        if ($lastCert) {
            $lastNumber = (int)substr($lastCert, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('CERT-%s-%06d', $year, $newNumber);
    }

    private function generateQRData(string $certificateNumber): string
    {
        return base64_encode(json_encode([
            'certificate_number' => $certificateNumber,
            'verification_url' => 'https://lgu.gov.ph/verify/' . $certificateNumber,
            'issued_at' => time()
        ]));
    }

    private function logVerification(int $certificateId, string $method): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO certificate_verifications (
                certificate_id, verified_by, verification_method,
                verified_at, ip_address
            ) VALUES (
                :certificate_id, 'public', :verification_method,
                NOW(), :ip_address
            )
        ");

        $stmt->execute([
            'certificate_id' => $certificateId,
            'verification_method' => $method,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    }
}
