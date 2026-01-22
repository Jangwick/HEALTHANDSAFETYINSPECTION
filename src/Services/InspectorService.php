<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use HealthSafety\Utils\Logger;

class InspectorService
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function listInspectors(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->query('SELECT COUNT(*) FROM inspectors WHERE employment_status = "active"');
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare('
            SELECT 
                i.*,
                u.username, u.email, u.phone as contact_phone,
                CONCAT(u.first_name, " ", u.last_name) as full_name
            FROM inspectors i
            JOIN users u ON i.user_id = u.user_id
            WHERE i.employment_status = "active"
            ORDER BY i.hired_date DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage)
            ]
        ];
    }

    public function getInspector(int $inspectorId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                i.*,
                u.username, u.email,
                CONCAT(u.first_name, " ", u.last_name) as full_name
            FROM inspectors i
            JOIN users u ON i.user_id = u.user_id
            WHERE i.inspector_id = :inspector_id
        ');
        $stmt->execute(['inspector_id' => $inspectorId]);
        $inspector = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inspector) {
            return null;
        }

        // Get certifications
        $stmt = $this->pdo->prepare('SELECT * FROM inspector_certifications WHERE inspector_id = :inspector_id ORDER BY expiry_date DESC');
        $stmt->execute(['inspector_id' => $inspectorId]);
        $inspector['certifications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get performance metrics
        $inspector['performance'] = $this->getPerformanceMetrics($inspectorId);

        return $inspector;
    }

    public function createInspector(array $data): array
    {
        // Generate badge number
        $badgeNumber = $this->generateBadgeNumber();

        $stmt = $this->pdo->prepare('
            INSERT INTO inspectors (
                user_id, badge_number, first_name, last_name, email, phone,
                specializations, certification_number, certification_expiry,
                employment_status, years_of_experience, photo_url, hired_date, created_at
            ) VALUES (
                :user_id, :badge_number, :first_name, :last_name, :email, :phone,
                :specializations, :certification_number, :certification_expiry,
                :employment_status, :years_of_experience, :photo_url, :hired_date, NOW()
            )
        ');

        $stmt->execute([
            'user_id' => $data['user_id'],
            'badge_number' => $badgeNumber,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'specializations' => isset($data['specializations']) ? json_encode($data['specializations']) : null,
            'certification_number' => $data['certification_number'] ?? null,
            'certification_expiry' => $data['certification_expiry'] ?? null,
            'employment_status' => $data['employment_status'] ?? 'active',
            'years_of_experience' => $data['years_of_experience'] ?? 0,
            'photo_url' => $data['photo_url'] ?? null,
            'hired_date' => $data['hired_date'] ?? date('Y-m-d')
        ]);

        $inspectorId = (int)$this->pdo->lastInsertId();
        $this->logger->info('Inspector created', ['inspector_id' => $inspectorId, 'badge_number' => $badgeNumber]);

        return $this->getInspector($inspectorId);
    }

    public function updateInspector(int $inspectorId, array $data): ?array
    {
        $fields = [];
        $params = ['inspector_id' => $inspectorId];

        $allowed = ['first_name','last_name','email','phone','certification_number','certification_expiry','employment_status','years_of_experience','photo_url'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['specializations'])) {
            $fields[] = 'specializations = :specializations';
            $params['specializations'] = json_encode($data['specializations']);
        }

        if (!empty($fields)) {
            $sql = 'UPDATE inspectors SET ' . implode(', ', $fields) . ' WHERE inspector_id = :inspector_id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        $this->logger->info('Inspector updated', ['inspector_id' => $inspectorId]);

        return $this->getInspector($inspectorId);
    }

    public function addCertification(int $inspectorId, array $certData): array
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO inspector_certifications (
                inspector_id, certification_type, issuing_body, certification_number,
                issue_date, expiry_date, status
            ) VALUES (
                :inspector_id, :certification_type, :issuing_body, :certification_number,
                :issue_date, :expiry_date, :status
            )
        ');

        $stmt->execute([
            'inspector_id' => $inspectorId,
            'certification_type' => $certData['certification_type'],
            'issuing_body' => $certData['issuing_body'] ?? null,
            'certification_number' => $certData['certification_number'],
            'issue_date' => $certData['issue_date'],
            'expiry_date' => $certData['expiry_date'],
            'status' => $certData['status'] ?? 'valid'
        ]);

        return [
            'cert_id' => (int)$this->pdo->lastInsertId(),
            'inspector_id' => $inspectorId
        ];
    }

    public function getSchedule(int $inspectorId, string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM inspector_schedule
            WHERE inspector_id = :inspector_id
            AND date BETWEEN :start_date AND :end_date
            ORDER BY date, start_time
        ');

        $stmt->execute([
            'inspector_id' => $inspectorId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setSchedule(int $inspectorId, array $scheduleData): array
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO inspector_schedule (
                inspector_id, date, start_time, end_time, status, notes
            ) VALUES (
                :inspector_id, :date, :start_time, :end_time, :status, :notes
            )
            ON DUPLICATE KEY UPDATE
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                status = VALUES(status),
                notes = VALUES(notes)
        ');

        $stmt->execute([
            'inspector_id' => $inspectorId,
            'date' => $scheduleData['date'],
            'start_time' => $scheduleData['start_time'] ?? '08:00:00',
            'end_time' => $scheduleData['end_time'] ?? '17:00:00',
            'status' => $scheduleData['status'] ?? 'available',
            'notes' => $scheduleData['notes'] ?? null
        ]);

        return [
            'schedule_id' => (int)$this->pdo->lastInsertId(),
            'inspector_id' => $inspectorId,
            'date' => $scheduleData['date']
        ];
    }

    public function getAvailableInspectors(string $date, ?string $specialization = null): array
    {
        $sql = '
            SELECT 
                i.inspector_id,
                i.badge_number,
                CONCAT(i.first_name, " ", i.last_name) as full_name,
                i.specializations,
                s.status as schedule_status
            FROM inspectors i
            LEFT JOIN inspector_schedule s ON i.inspector_id = s.inspector_id AND s.date = :date
            WHERE i.employment_status = "active"
            AND (s.status IS NULL OR s.status = "available")
        ';

        $params = ['date' => $date];

        if ($specialization) {
            $sql .= ' AND JSON_CONTAINS(i.specializations, :specialization, "$")';
            $params['specialization'] = json_encode($specialization);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPerformanceMetrics(int $inspectorId): array
    {
        $currentMonth = date('Y-m');

        $stmt = $this->pdo->prepare('
            SELECT * FROM inspector_performance
            WHERE inspector_id = :inspector_id
            AND period = :period
        ');

        $stmt->execute([
            'inspector_id' => $inspectorId,
            'period' => $currentMonth
        ]);

        $perf = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$perf) {
            // Calculate on-the-fly if no record
            return $this->calculatePerformance($inspectorId, $currentMonth);
        }

        return $perf;
    }

    private function calculatePerformance(int $inspectorId, string $period): array
    {
        list($year, $month) = explode('-', $period);

        $stmt = $this->pdo->prepare('
            SELECT 
                COUNT(*) as inspections_completed,
                AVG(TIMESTAMPDIFF(MINUTE, actual_start_datetime, actual_end_datetime)) as avg_duration
            FROM inspections
            WHERE inspector_id = :inspector_id
            AND status = "completed"
            AND YEAR(actual_end_datetime) = :year
            AND MONTH(actual_end_datetime) = :month
        ');

        $stmt->execute([
            'inspector_id' => $inspectorId,
            'year' => $year,
            'month' => $month
        ]);

        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'inspector_id' => $inspectorId,
            'period' => $period,
            'inspections_completed' => (int)($metrics['inspections_completed'] ?? 0),
            'avg_inspection_duration_mins' => (int)($metrics['avg_duration'] ?? 0)
        ];
    }

    /**
     * Get expiring certifications for proactive tracking (LGU Enhancement)
     */
    public function getExpiringCertifications(int $daysAhead = 30): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ic.*,
                CONCAT(u.first_name, ' ', u.last_name) as inspector_name,
                i.badge_number
            FROM inspector_certifications ic
            JOIN inspectors i ON ic.inspector_id = i.inspector_id
            JOIN users u ON i.user_id = u.user_id
            WHERE ic.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
            AND ic.status = 'valid'
            ORDER BY ic.expiry_date ASC
        ");
        $stmt->execute(['days' => $daysAhead]);
        $expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->logger->info("Expiring certifications check performed", [
            'days_ahead' => $daysAhead,
            'count' => count($expiring)
        ]);

        return $expiring;
    }

    private function generateBadgeNumber(): string
    {
        $stmt = $this->pdo->query('SELECT badge_number FROM inspectors ORDER BY inspector_id DESC LIMIT 1');
        $lastBadge = $stmt->fetchColumn();

        if ($lastBadge && preg_match('/INS-(\d+)/', $lastBadge, $matches)) {
            $num = (int)$matches[1] + 1;
        } else {
            $num = 1;
        }

        return sprintf('INS-%04d', $num);
    }
}
