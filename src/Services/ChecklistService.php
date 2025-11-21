<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;
use HealthSafety\Utils\Logger;

class ChecklistService
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function listTemplates(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->query('SELECT COUNT(*) FROM checklist_templates');
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT * FROM checklist_templates ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $templates,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int)ceil($total / $perPage)
            ]
        ];
    }

    public function getTemplate(int $templateId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM checklist_templates WHERE template_id = :template_id');
        $stmt->execute(['template_id' => $templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM checklist_items WHERE template_id = :template_id ORDER BY order_sequence');
        $stmt->execute(['template_id' => $templateId]);
        $template['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $template;
    }

    public function createTemplate(array $data): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO checklist_templates (name, description, establishment_type, inspection_type, version, status, created_by, created_at) VALUES (:name, :description, :establishment_type, :inspection_type, :version, :status, :created_by, NOW())');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'establishment_type' => $data['establishment_type'] ?? null,
            'inspection_type' => $data['inspection_type'] ?? null,
            'version' => $data['version'] ?? 1,
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'] ?? null
        ]);

        $templateId = (int)$this->pdo->lastInsertId();

        // Insert items if provided
        if (!empty($data['items']) && is_array($data['items'])) {
            $this->saveItems($templateId, $data['items']);
        }

        $this->logger->info('Checklist template created', ['template_id' => $templateId]);

        return $this->getTemplate($templateId);
    }

    public function updateTemplate(int $templateId, array $data): ?array
    {
        $fields = [];
        $params = ['template_id' => $templateId];

        $allowed = ['name','description','establishment_type','inspection_type','version','status'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (!empty($fields)) {
            $sql = 'UPDATE checklist_templates SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE template_id = :template_id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            // Replace items: simple approach - delete existing and re-insert
            $del = $this->pdo->prepare('DELETE FROM checklist_items WHERE template_id = :template_id');
            $del->execute(['template_id' => $templateId]);
            $this->saveItems($templateId, $data['items']);
        }

        $this->logger->info('Checklist template updated', ['template_id' => $templateId]);

        return $this->getTemplate($templateId);
    }

    public function deleteTemplate(int $templateId): bool
    {
        // Soft-delete: mark status as archived
        $stmt = $this->pdo->prepare('UPDATE checklist_templates SET status = "archived", updated_at = NOW() WHERE template_id = :template_id');
        $stmt->execute(['template_id' => $templateId]);

        $this->logger->info('Checklist template archived', ['template_id' => $templateId]);

        return $stmt->rowCount() > 0;
    }

    private function saveItems(int $templateId, array $items): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO checklist_items (template_id, category, subcategory, item_number, requirement_text, mandatory, scoring_type, points_possible, legal_reference, guidance_notes, order_sequence) VALUES (:template_id, :category, :subcategory, :item_number, :requirement_text, :mandatory, :scoring_type, :points_possible, :legal_reference, :guidance_notes, :order_sequence)');

        $seq = 1;
        foreach ($items as $item) {
            $stmt->execute([
                'template_id' => $templateId,
                'category' => $item['category'] ?? null,
                'subcategory' => $item['subcategory'] ?? null,
                'item_number' => $item['item_number'] ?? null,
                'requirement_text' => $item['requirement_text'] ?? null,
                'mandatory' => isset($item['mandatory']) ? (int)$item['mandatory'] : 0,
                'scoring_type' => $item['scoring_type'] ?? 'pass_fail',
                'points_possible' => $item['points_possible'] ?? null,
                'legal_reference' => $item['legal_reference'] ?? null,
                'guidance_notes' => $item['guidance_notes'] ?? null,
                'order_sequence' => $item['order_sequence'] ?? $seq
            ]);
            $seq++;
        }
    }
}
