<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\InspectionService;
use HealthSafety\Utils\Validator;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class InspectionController
{
    private InspectionService $inspectionService;
    private Validator $validator;
    private RoleMiddleware $roleMiddleware;

    public function __construct(
        InspectionService $inspectionService, 
        Validator $validator,
        RoleMiddleware $roleMiddleware
    ) {
        $this->inspectionService = $inspectionService;
        $this->validator = $validator;
        $this->roleMiddleware = $roleMiddleware;
    }

    /**
     * GET /api/v1/inspections
     * List all inspections with filters
     */
    public function index(): void
    {
        $this->roleMiddleware->requirePermission('inspections.read');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'inspection_type' => $_GET['inspection_type'] ?? null,
            'inspector_id' => isset($_GET['inspector_id']) ? (int)$_GET['inspector_id'] : null,
            'establishment_id' => isset($_GET['establishment_id']) ? (int)$_GET['establishment_id'] : null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $result = $this->inspectionService->getInspections($filters, $page, $perPage);

        Response::success($result);
    }

    /**
     * GET /api/v1/inspections/{id}
     * Get single inspection details
     */
    public function show(int $id): void
    {
        $this->roleMiddleware->requirePermission('inspections.read');

        $inspection = $this->inspectionService->getInspectionById($id);

        if (!$inspection) {
            Response::error('NOT_FOUND', 'Inspection not found', null, 404);
            return;
        }

        // Check ownership if establishment owner
        if ($_SESSION['role'] === 'establishment_owner') {
            $this->roleMiddleware->requireOwnership('inspection', $id);
        }

        Response::success(['inspection' => $inspection]);
    }

    /**
     * POST /api/v1/inspections
     * Create new inspection
     */
    public function create(array $data): void
    {
        $this->roleMiddleware->requirePermission('inspections.create');

        // Validate input
        $rules = [
            'establishment_id' => ['required', 'integer'],
            'inspection_type' => ['required', 'string'],
            'scheduled_date' => ['required', 'date'],
            'inspector_id' => ['integer'],
            'priority' => ['string'],
            'checklist_template_id' => ['integer']
        ];

        $errors = $this->validator->validate($data, $rules);

        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $data['created_by'] = $_SESSION['user_id'];
            $inspection = $this->inspectionService->createInspection($data);

            Response::success([
                'inspection' => $inspection,
                'message' => 'Inspection scheduled successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error('CREATION_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * PUT /api/v1/inspections/{id}
     * Update inspection
     */
    public function update(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('inspections.update');

        try {
            $inspection = $this->inspectionService->updateInspection($id, $data);

            Response::success([
                'inspection' => $inspection,
                'message' => 'Inspection updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error('UPDATE_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/v1/inspections/{id}/start
     * Start inspection
     */
    public function start(int $id): void
    {
        $this->roleMiddleware->requirePermission('inspections.update');

        try {
            $inspection = $this->inspectionService->startInspection($id);

            Response::success([
                'inspection' => $inspection,
                'message' => 'Inspection started successfully'
            ]);
        } catch (\Exception $e) {
            Response::error('START_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/v1/inspections/{id}/complete
     * Complete inspection
     */
    public function complete(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('inspections.update');

        // Validate input
        $rules = [
            'overall_rating' => ['required', 'string'],
            'inspector_notes' => ['string']
        ];

        $errors = $this->validator->validate($data, $rules);

        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $inspection = $this->inspectionService->completeInspection($id, $data);

            Response::success([
                'inspection' => $inspection,
                'message' => 'Inspection completed successfully'
            ]);
        } catch (\Exception $e) {
            Response::error('COMPLETION_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/v1/inspections/{id}/upload-photo
     * Upload inspection photo
     */
    public function uploadPhoto(int $id): void
    {
        $this->roleMiddleware->requirePermission('inspections.update');

        // Handle file upload
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            Response::error('UPLOAD_FAILED', 'No file uploaded or upload error', null, 400);
            return;
        }

        $file = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            Response::error('INVALID_FILE_TYPE', 'Only JPG and PNG images are allowed', null, 400);
            return;
        }

        if ($file['size'] > $maxSize) {
            Response::error('FILE_TOO_LARGE', 'File size must be less than 5MB', null, 400);
            return;
        }

        try {
            // Create uploads directory if not exists
            $uploadDir = __DIR__ . '/../../public/uploads/inspections/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('inspection_' . $id . '_') . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new \Exception('Failed to save file');
            }

            $photoData = [
                'photo_url' => '/uploads/inspections/' . $filename,
                'photo_type' => $_POST['photo_type'] ?? 'general',
                'caption' => $_POST['caption'] ?? null,
                'gps_coordinates' => $_POST['gps_coordinates'] ?? null
            ];

            $photo = $this->inspectionService->uploadPhoto($id, $photoData);

            Response::success([
                'photo' => $photo,
                'message' => 'Photo uploaded successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error('UPLOAD_FAILED', $e->getMessage(), null, 500);
        }
    }

    /**
     * POST /api/v1/inspections/{id}/checklist-response
     * Submit checklist responses
     */
    public function submitChecklistResponse(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('inspections.update');

        // Validate input
        if (!isset($data['responses']) || !is_array($data['responses'])) {
            Response::error('VALIDATION_ERROR', 'Responses array is required', null, 400);
            return;
        }

        try {
            $this->inspectionService->submitChecklistResponse($id, $data['responses']);

            Response::success([
                'message' => 'Checklist responses saved successfully'
            ]);
        } catch (\Exception $e) {
            Response::error('SUBMISSION_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * GET /api/v1/inspections/schedule
     * Get inspection schedule/calendar
     */
    public function schedule(): void
    {
        $this->roleMiddleware->requirePermission('inspections.read');

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $inspectorId = isset($_GET['inspector_id']) ? (int)$_GET['inspector_id'] : null;

        $schedule = $this->inspectionService->getSchedule($startDate, $endDate, $inspectorId);

        Response::success([
            'schedule' => $schedule,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
}
