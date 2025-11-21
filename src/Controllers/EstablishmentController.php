<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\EstablishmentService;
use HealthSafety\Utils\Validator;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class EstablishmentController
{
    private EstablishmentService $establishmentService;
    private Validator $validator;
    private RoleMiddleware $roleMiddleware;

    public function __construct(
        EstablishmentService $establishmentService, 
        Validator $validator,
        RoleMiddleware $roleMiddleware
    ) {
        $this->establishmentService = $establishmentService;
        $this->validator = $validator;
        $this->roleMiddleware = $roleMiddleware;
    }

    /**
     * GET /api/v1/establishments
     * List all establishments
     */
    public function index(): void
    {
        $this->roleMiddleware->requirePermission('establishments.read');

        $filters = [
            'type' => $_GET['type'] ?? null,
            'compliance_status' => $_GET['compliance_status'] ?? null,
            'risk_category' => $_GET['risk_category'] ?? null,
            'barangay' => $_GET['barangay'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $result = $this->establishmentService->getEstablishments($filters, $page, $perPage);

        Response::success($result);
    }

    /**
     * GET /api/v1/establishments/{id}
     * Get single establishment
     */
    public function show(int $id): void
    {
        $this->roleMiddleware->requirePermission('establishments.read');

        $establishment = $this->establishmentService->getEstablishmentById($id);

        if (!$establishment) {
            Response::error('NOT_FOUND', 'Establishment not found', null, 404);
            return;
        }

        // Check ownership if establishment owner
        if ($_SESSION['role'] === 'establishment_owner') {
            $this->roleMiddleware->requireOwnership('establishment', $id);
        }

        Response::success(['establishment' => $establishment]);
    }

    /**
     * POST /api/v1/establishments
     * Create new establishment
     */
    public function create(array $data): void
    {
        $this->roleMiddleware->requirePermission('establishments.create');

        // Validate input
        $rules = [
            'name' => ['required', 'string'],
            'type' => ['required', 'string'],
            'owner_name' => ['required', 'string'],
            'owner_contact' => ['required', 'string'],
            'address_street' => ['required', 'string'],
            'address_barangay' => ['required', 'string'],
            'address_city' => ['required', 'string']
        ];

        $errors = $this->validator->validate($data, $rules);

        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $establishment = $this->establishmentService->createEstablishment($data);

            Response::success([
                'establishment' => $establishment,
                'message' => 'Establishment registered successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error('CREATION_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * PUT /api/v1/establishments/{id}
     * Update establishment
     */
    public function update(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('establishments.update');

        // Check ownership if establishment owner
        if ($_SESSION['role'] === 'establishment_owner') {
            $this->roleMiddleware->requireOwnership('establishment', $id);
        }

        try {
            $establishment = $this->establishmentService->updateEstablishment($id, $data);

            Response::success([
                'establishment' => $establishment,
                'message' => 'Establishment updated successfully'
            ]);
        } catch (\Exception $e) {
            Response::error('UPDATE_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/v1/establishments/{id}/contacts
     * Add contact person
     */
    public function addContact(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('establishments.update');

        // Validate input
        $rules = [
            'contact_type' => ['required', 'string'],
            'name' => ['required', 'string'],
            'phone' => ['required', 'string']
        ];

        $errors = $this->validator->validate($data, $rules);

        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $contact = $this->establishmentService->addContact($id, $data);

            Response::success([
                'contact' => $contact,
                'message' => 'Contact added successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error('ADD_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * POST /api/v1/establishments/{id}/permits
     * Add permit
     */
    public function addPermit(int $id, array $data): void
    {
        $this->roleMiddleware->requirePermission('establishments.update');

        // Validate input
        $rules = [
            'permit_type' => ['required', 'string'],
            'permit_number' => ['required', 'string'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date']
        ];

        $errors = $this->validator->validate($data, $rules);

        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $permit = $this->establishmentService->addPermit($id, $data);

            Response::success([
                'permit' => $permit,
                'message' => 'Permit added successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error('ADD_FAILED', $e->getMessage(), null, 400);
        }
    }

    /**
     * GET /api/v1/establishments/map
     * Get establishments for map view
     */
    public function map(): void
    {
        $this->roleMiddleware->requirePermission('establishments.read');

        $filters = [
            'type' => $_GET['type'] ?? null,
            'compliance_status' => $_GET['compliance_status'] ?? null
        ];

        $establishments = $this->establishmentService->getEstablishmentsForMap($filters);

        Response::success(['establishments' => $establishments]);
    }
}
