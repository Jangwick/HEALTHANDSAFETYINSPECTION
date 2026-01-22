<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\ViolationService;
use HealthSafety\Utils\Validator;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class ViolationController
{
    private ViolationService $violationService;
    private Validator $validator;
    private RoleMiddleware $roleMiddleware;

    public function __construct(
        ViolationService $violationService, 
        Validator $validator,
        RoleMiddleware $roleMiddleware
    ) {
        $this->violationService = $violationService;
        $this->validator = $validator;
        $this->roleMiddleware = $roleMiddleware;
    }

    public function index(): void
    {
        $this->roleMiddleware->requirePermission('violations.read');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'severity' => $_GET['severity'] ?? null,
            'establishment_id' => isset($_GET['establishment_id']) ? (int)$_GET['establishment_id'] : null
        ];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $result = $this->violationService->getViolations($filters, $page, $perPage);
        Response::success($result);
    }

    public function create(array $data): void
    {
        $this->roleMiddleware->requirePermission('violations.create');

        $rules = [
            'inspection_id' => ['required', 'integer'],
            'establishment_id' => ['required', 'integer'],
            'category' => ['required', 'string'],
            'description' => ['required', 'string'],
            'severity' => ['required', 'string']
        ];

        $errors = $this->validator->validate($data, $rules);

        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $data['reported_by'] = $_SESSION['user_id'];
            $violation = $this->violationService->createViolation($data);

            Response::success([
                'violation' => $violation,
                'message' => 'Violation reported successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error('CREATION_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function show(int $id): void
    {
        $this->roleMiddleware->requirePermission('violations.read');

        try {
            $violation = $this->violationService->getViolationById($id);
            if (!$violation) {
                Response::error('NOT_FOUND', 'Violation not found', null, 404);
                return;
            }
            Response::success(['violation' => $violation]);
        } catch (\Exception $e) {
            Response::error('SERVER_ERROR', $e->getMessage(), null, 500);
        }
    }

    public function resolve(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('violations.update');

        try {
            $data['resolved_by'] = $_SESSION['user_id'];
            $violation = $this->violationService->resolveViolation($id, $data);

            Response::success([
                'violation' => $violation,
                'message' => 'Violation marked as resolved'
            ]);
        } catch (\Exception $e) {
            Response::error('RESOLVE_FAILED', $e->getMessage(), null, 400);
        }
    }
}
