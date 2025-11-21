<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\InspectorService;
use HealthSafety\Utils\Validator;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class InspectorController
{
    private InspectorService $inspectorService;
    private Validator $validator;
    private RoleMiddleware $roleMiddleware;

    public function __construct(InspectorService $inspectorService, Validator $validator, RoleMiddleware $roleMiddleware)
    {
        $this->inspectorService = $inspectorService;
        $this->validator = $validator;
        $this->roleMiddleware = $roleMiddleware;
    }

    public function index(): void
    {
        $this->roleMiddleware->requirePermission('inspectors.read');

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $result = $this->inspectorService->listInspectors($page, $perPage);
        Response::success($result);
    }

    public function show(int $id): void
    {
        $this->roleMiddleware->requirePermission('inspectors.read');

        $inspector = $this->inspectorService->getInspector($id);
        if (!$inspector) {
            Response::error('NOT_FOUND', 'Inspector not found', null, 404);
            return;
        }

        Response::success(['inspector' => $inspector]);
    }

    public function create(array $data): void
    {
        $this->roleMiddleware->requirePermission('inspectors.create');

        $rules = [
            'user_id' => ['required', 'integer'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['required', 'email']
        ];

        $errors = $this->validator->validate($data, $rules);
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input', $errors, 400);
            return;
        }

        try {
            $inspector = $this->inspectorService->createInspector($data);
            Response::success(['inspector' => $inspector, 'message' => 'Inspector created'], 201);
        } catch (\Exception $e) {
            Response::error('CREATION_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function update(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('inspectors.update');

        try {
            $inspector = $this->inspectorService->updateInspector($id, $data);
            Response::success(['inspector' => $inspector, 'message' => 'Inspector updated']);
        } catch (\Exception $e) {
            Response::error('UPDATE_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function addCertification(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('inspectors.update');

        $rules = [
            'certification_type' => ['required', 'string'],
            'certification_number' => ['required', 'string'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date']
        ];

        $errors = $this->validator->validate($data, $rules);
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input', $errors, 400);
            return;
        }

        try {
            $cert = $this->inspectorService->addCertification($id, $data);
            Response::success(['certification' => $cert, 'message' => 'Certification added'], 201);
        } catch (\Exception $e) {
            Response::error('ADD_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function getSchedule(int $id): void
    {
        $this->roleMiddleware->requirePermission('inspectors.read');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        $schedule = $this->inspectorService->getSchedule($id, $startDate, $endDate);
        Response::success(['schedule' => $schedule]);
    }

    public function setSchedule(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('inspectors.update');

        $rules = [
            'date' => ['required', 'date'],
            'start_time' => ['string'],
            'end_time' => ['string'],
            'status' => ['string']
        ];

        $errors = $this->validator->validate($data, $rules);
        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input', $errors, 400);
            return;
        }

        try {
            $schedule = $this->inspectorService->setSchedule($id, $data);
            Response::success(['schedule' => $schedule, 'message' => 'Schedule set']);
        } catch (\Exception $e) {
            Response::error('SCHEDULE_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function available(): void
    {
        $this->roleMiddleware->requirePermission('inspectors.read');

        $date = $_GET['date'] ?? date('Y-m-d');
        $specialization = $_GET['specialization'] ?? null;

        $inspectors = $this->inspectorService->getAvailableInspectors($date, $specialization);
        Response::success(['inspectors' => $inspectors, 'date' => $date]);
    }
}
