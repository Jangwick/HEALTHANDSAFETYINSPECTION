<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\ChecklistService;
use HealthSafety\Utils\Validator;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class ChecklistController
{
    private ChecklistService $checklistService;
    private Validator $validator;
    private RoleMiddleware $roleMiddleware;

    public function __construct(ChecklistService $checklistService, Validator $validator, RoleMiddleware $roleMiddleware)
    {
        $this->checklistService = $checklistService;
        $this->validator = $validator;
        $this->roleMiddleware = $roleMiddleware;
    }

    public function index(): void
    {
        $this->roleMiddleware->requirePermission('checklists.read');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $result = $this->checklistService->listTemplates($page, $perPage);
        Response::success($result);
    }

    public function show(int $id): void
    {
        $this->roleMiddleware->requirePermission('checklists.read');

        $template = $this->checklistService->getTemplate($id);
        if (!$template) {
            Response::error('NOT_FOUND', 'Checklist template not found', null, 404);
            return;
        }

        Response::success(['template' => $template]);
    }

    public function create(array $data): void
    {
        $this->roleMiddleware->requirePermission('checklists.create');

        $rules = [
            'name' => ['required', 'string'],
            'inspection_type' => ['string'],
            'establishment_type' => ['string']
        ];

        $errors = $this->validator->validate($data, $rules);
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input', $errors, 400);
            return;
        }

        $data['created_by'] = $_SESSION['user_id'] ?? null;

        try {
            $template = $this->checklistService->createTemplate($data);
            Response::success(['template' => $template, 'message' => 'Template created'], 201);
        } catch (\Exception $e) {
            Response::error('CREATION_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function update(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('checklists.update');

        try {
            $template = $this->checklistService->updateTemplate($id, $data);
            Response::success(['template' => $template, 'message' => 'Template updated']);
        } catch (\Exception $e) {
            Response::error('UPDATE_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function delete(int $id): void
    {
        $this->roleMiddleware->requirePermission('checklists.delete');

        try {
            $ok = $this->checklistService->deleteTemplate($id);
            if ($ok) {
                Response::success(['message' => 'Template archived']);
            } else {
                Response::error('DELETE_FAILED', 'Failed to archive template', null, 400);
            }
        } catch (\Exception $e) {
            Response::error('DELETE_FAILED', $e->getMessage(), null, 400);
        }
    }
}
