<?php

declare(strict_types=1);

namespace HealthSafety\Controllers;

use HealthSafety\Services\CertificateService;
use HealthSafety\Utils\Validator;
use HealthSafety\Utils\Response;
use HealthSafety\Middleware\RoleMiddleware;

class CertificateController
{
    private CertificateService $certificateService;
    private Validator $validator;
    private RoleMiddleware $roleMiddleware;

    public function __construct(
        CertificateService $certificateService, 
        Validator $validator,
        RoleMiddleware $roleMiddleware
    ) {
        $this->certificateService = $certificateService;
        $this->validator = $validator;
        $this->roleMiddleware = $roleMiddleware;
    }

    public function index(): void
    {
        $this->roleMiddleware->requirePermission('certificates.read');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'establishment_id' => isset($_GET['establishment_id']) ? (int)$_GET['establishment_id'] : null
        ];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $result = $this->certificateService->getCertificates($filters, $page, $perPage);
        Response::success($result);
    }

    public function issue(array $data): void
    {
        $this->roleMiddleware->requirePermission('certificates.issue');

        $rules = [
            'establishment_id' => ['required', 'integer'],
            'inspection_id' => ['required', 'integer'],
            'certificate_type' => ['required', 'string'],
            'expiry_date' => ['required', 'date']
        ];

        $errors = $this->validator->validate($data, $rules);

        if (!empty($errors)) {
            Response::error('VALIDATION_ERROR', 'Invalid input data', $errors, 400);
            return;
        }

        try {
            $data['issued_by'] = $_SESSION['user_id'];
            $certificate = $this->certificateService->issueCertificate($data);

            Response::success([
                'certificate' => $certificate,
                'message' => 'Certificate issued successfully'
            ], 201);
        } catch (\Exception $e) {
            Response::error('ISSUE_FAILED', $e->getMessage(), null, 400);
        }
    }

    public function verify(string $certificateNumber): void
    {
        // Public endpoint - no permission required
        $certificate = $this->certificateService->verifyCertificate($certificateNumber);

        if (!$certificate) {
            Response::error('NOT_FOUND', 'Certificate not found', null, 404);
            return;
        }

        Response::success([
            'certificate' => $certificate,
            'is_valid' => $certificate['status'] === 'valid' && strtotime($certificate['expiry_date']) > time()
        ]);
    }

    public function show(int $id): void
    {
        $this->roleMiddleware->requirePermission('certificates.read');

        $certificate = $this->certificateService->getCertificateById($id);

        if (!$certificate) {
            Response::error('NOT_FOUND', 'Certificate not found', null, 404);
            return;
        }

        Response::success(['certificate' => $certificate]);
    }
}
