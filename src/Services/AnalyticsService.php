<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;

class AnalyticsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getDashboardMetrics(): array
    {
        return [
            'inspections' => $this->getInspectionMetrics(),
            'establishments' => $this->getEstablishmentMetrics(),
            'violations' => $this->getViolationMetrics(),
            'certificates' => $this->getCertificateMetrics()
        ];
    }

    private function getInspectionMetrics(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN YEAR(scheduled_date) = YEAR(NOW()) THEN 1 ELSE 0 END) as this_year
            FROM inspections
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getEstablishmentMetrics(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN compliance_status = 'compliant' THEN 1 ELSE 0 END) as compliant,
                SUM(CASE WHEN risk_category = 'high' THEN 1 ELSE 0 END) as high_risk
            FROM establishments
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getViolationMetrics(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
                SUM(CASE WHEN severity = 'major' THEN 1 ELSE 0 END) as major
            FROM violations
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getCertificateMetrics(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid,
                SUM(CASE WHEN MONTH(issue_date) = MONTH(NOW()) AND YEAR(issue_date) = YEAR(NOW()) THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
            FROM certificates
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getInspectionTrends(int $months = 6): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE_FORMAT(scheduled_date, '%Y-%m') as month,
                COUNT(*) as count
            FROM inspections
            WHERE scheduled_date >= DATE_SUB(NOW(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(scheduled_date, '%Y-%m')
            ORDER BY month
        ");

        $stmt->execute(['months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getViolationsByCategory(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                category,
                COUNT(*) as count
            FROM violations
            WHERE YEAR(reported_at) = YEAR(NOW())
            GROUP BY category
            ORDER BY count DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getComplianceReport(string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                e.name,
                e.type,
                e.compliance_status,
                COUNT(DISTINCT i.inspection_id) as total_inspections,
                COUNT(DISTINCT v.violation_id) as total_violations,
                COUNT(DISTINCT c.certificate_id) as total_certificates
            FROM establishments e
            LEFT JOIN inspections i ON e.establishment_id = i.establishment_id 
                AND i.scheduled_date BETWEEN :start_date AND :end_date
            LEFT JOIN violations v ON e.establishment_id = v.establishment_id
                AND v.reported_at BETWEEN :start_date AND :end_date
            LEFT JOIN certificates c ON e.establishment_id = c.establishment_id
                AND c.issue_date BETWEEN :start_date AND :end_date
            GROUP BY e.establishment_id
            ORDER BY e.name
        ");

        $stmt->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
