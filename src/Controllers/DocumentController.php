<?php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Utils\Response;
use App\Utils\Validator;
use Exception;

class DocumentController
{
    private DocumentService $documentService;
    private array $user;

    public function __construct(DocumentService $documentService, array $user)
    {
        $this->documentService = $documentService;
        $this->user = $user;
    }

    /**
     * List documents
     * GET /api/documents
     */
    public function index(): void
    {
        $filters = [
            'entity_type' => $_GET['entity_type'] ?? null,
            'entity_id' => isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : null,
            'uploaded_by' => isset($_GET['uploaded_by']) ? (int)$_GET['uploaded_by'] : null,
            'search' => $_GET['search'] ?? null
        ];

        // Remove null filters
        $filters = array_filter($filters, fn($value) => $value !== null);

        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 20), 100);

        $result = $this->documentService->listDocuments($filters, $page, $perPage);

        Response::success($result);
    }

    /**
     * Get document details
     * GET /api/documents/:id
     */
    public function show(int $id): void
    {
        $document = $this->documentService->getDocument($id, $this->user['id']);

        if (!$document) {
            Response::error('Document not found', 404);
            return;
        }

        Response::success($document);
    }

    /**
     * Upload document
     * POST /api/documents
     */
    public function upload(): void
    {
        // Check if file was uploaded
        if (!isset($_FILES['file'])) {
            Response::error('No file uploaded', 400);
            return;
        }

        // Get metadata from POST data
        $entityType = $_POST['entity_type'] ?? null;
        $entityId = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : null;
        $title = $_POST['title'] ?? null;
        $description = $_POST['description'] ?? null;
        $tags = isset($_POST['tags']) ? json_decode($_POST['tags'], true) : null;

        // Validate required fields
        if (!$entityType) {
            Response::error('entity_type is required', 400);
            return;
        }

        try {
            $result = $this->documentService->uploadDocument(
                $_FILES['file'],
                $this->user['id'],
                $entityType,
                $entityId,
                $title,
                $description,
                $tags
            );

            Response::success($result, 201);
        } catch (Exception $e) {
            Response::error('Upload failed: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Download document
     * GET /api/documents/:id/download
     */
    public function download(int $id): void
    {
        try {
            $file = $this->documentService->downloadDocument($id, $this->user['id']);

            // Set headers for download
            header('Content-Type: ' . $file['mime_type']);
            header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
            header('Content-Length: ' . $file['size']);
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');

            // Output file
            readfile($file['filepath']);
            exit;
        } catch (Exception $e) {
            Response::error('Download failed: ' . $e->getMessage(), 404);
        }
    }

    /**
     * Update document metadata
     * PUT /api/documents/:id
     */
    public function update(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = Validator::validate($data, [
            'title' => 'string|max:255',
            'description' => 'string',
            'tags' => 'array'
        ]);

        if (!$validation['valid']) {
            Response::error('Validation failed', 400, $validation['errors']);
            return;
        }

        $success = $this->documentService->updateDocument($id, $data);

        if ($success) {
            Response::success(['message' => 'Document updated successfully']);
        } else {
            Response::error('Document not found or no changes made', 404);
        }
    }

    /**
     * Delete document (soft delete)
     * DELETE /api/documents/:id
     */
    public function delete(int $id): void
    {
        $success = $this->documentService->deleteDocument($id, $this->user['id']);

        if ($success) {
            Response::success(['message' => 'Document deleted successfully']);
        } else {
            Response::error('Document not found', 404);
        }
    }

    /**
     * Get document access logs
     * GET /api/documents/:id/access-logs
     */
    public function accessLogs(int $id): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 20), 100);

        $result = $this->documentService->getAccessLogs($id, $page, $perPage);

        Response::success($result);
    }

    /**
     * Get storage statistics
     * GET /api/documents/storage-stats
     */
    public function storageStats(): void
    {
        $stats = $this->documentService->getStorageStats();

        Response::success($stats);
    }
}
