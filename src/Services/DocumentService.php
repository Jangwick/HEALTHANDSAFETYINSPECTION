<?php

namespace App\Services;

use PDO;
use Exception;

class DocumentService
{
    private PDO $db;
    private string $uploadPath;
    private array $allowedTypes;
    private int $maxFileSize;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->uploadPath = __DIR__ . '/../../public/uploads/documents/';
        $this->allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
            'application/zip'
        ];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload a document
     * 
     * @param array $file File from $_FILES
     * @param int $uploadedBy User ID
     * @param string $entityType Entity type (inspection, establishment, violation, certificate)
     * @param int|null $entityId Entity ID
     * @param string|null $title Document title
     * @param string|null $description Document description
     * @param array|null $tags Tags array
     * @return array Result with document ID and file info
     */
    public function uploadDocument(
        array $file,
        int $uploadedBy,
        string $entityType,
        ?int $entityId = null,
        ?string $title = null,
        ?string $description = null,
        ?array $tags = null
    ): array {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $this->generateFilename($extension);
            $filepath = $this->uploadPath . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception("Failed to move uploaded file");
            }

            // Calculate file hash for duplicate detection
            $fileHash = hash_file('sha256', $filepath);

            // Check for duplicates
            $duplicate = $this->checkDuplicate($fileHash);
            if ($duplicate) {
                // Reference existing file instead of uploading duplicate
                unlink($filepath);
                $filename = $duplicate['filename'];
            }

            // Insert document record
            $stmt = $this->db->prepare("
                INSERT INTO documents (
                    entity_type, entity_id, title, description, filename, 
                    original_filename, mime_type, file_size, file_hash,
                    uploaded_by, version, tags, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
            ");

            $stmt->execute([
                $entityType,
                $entityId,
                $title ?? pathinfo($file['name'], PATHINFO_FILENAME),
                $description,
                $filename,
                $file['name'],
                $file['type'],
                $file['size'],
                $fileHash,
                $uploadedBy,
                $tags ? json_encode($tags) : null
            ]);

            $documentId = (int)$this->db->lastInsertId();

            // Log document access
            $this->logAccess($documentId, $uploadedBy, 'upload');

            return [
                'success' => true,
                'document_id' => $documentId,
                'filename' => $filename,
                'original_filename' => $file['name'],
                'size' => $file['size'],
                'mime_type' => $file['type']
            ];
        } catch (Exception $e) {
            // Clean up file if database insert failed
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }

            throw $e;
        }
    }

    /**
     * Validate uploaded file
     * 
     * @param array $file File from $_FILES
     * @throws Exception if validation fails
     */
    private function validateFile(array $file): void
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $file['error']);
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception("File size exceeds maximum allowed size of " . ($this->maxFileSize / 1024 / 1024) . "MB");
        }

        // Check MIME type
        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception("File type not allowed: " . $file['type']);
        }

        // Additional security: check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip'];
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception("File extension not allowed: " . $extension);
        }
    }

    /**
     * Generate unique filename
     * 
     * @param string $extension File extension
     * @return string Unique filename
     */
    private function generateFilename(string $extension): string
    {
        return uniqid('doc_', true) . '_' . time() . '.' . $extension;
    }

    /**
     * Check for duplicate file
     * 
     * @param string $fileHash File hash
     * @return array|null Existing document if found
     */
    private function checkDuplicate(string $fileHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, filename
            FROM documents
            WHERE file_hash = ? AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([$fileHash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get document details
     * 
     * @param int $documentId Document ID
     * @param int $userId User ID (for access logging)
     * @return array|null Document details
     */
    public function getDocument(int $documentId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name as uploaded_by_name
            FROM documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.id = ? AND d.deleted_at IS NULL
        ");

        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($document) {
            // Parse tags
            $document['tags'] = $document['tags'] ? json_decode($document['tags'], true) : [];
            
            // Log access
            $this->logAccess($documentId, $userId, 'view');
        }

        return $document ?: null;
    }

    /**
     * Download document file
     * 
     * @param int $documentId Document ID
     * @param int $userId User ID (for access logging)
     * @return array File path and metadata
     * @throws Exception if document not found
     */
    public function downloadDocument(int $documentId, int $userId): array
    {
        $document = $this->getDocument($documentId, $userId);

        if (!$document) {
            throw new Exception("Document not found");
        }

        $filepath = $this->uploadPath . $document['filename'];

        if (!file_exists($filepath)) {
            throw new Exception("Document file not found on disk");
        }

        // Log download
        $this->logAccess($documentId, $userId, 'download');

        return [
            'filepath' => $filepath,
            'filename' => $document['original_filename'],
            'mime_type' => $document['mime_type'],
            'size' => $document['file_size']
        ];
    }

    /**
     * List documents (paginated)
     * 
     * @param array $filters Filters (entity_type, entity_id, uploaded_by, tags)
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Documents and pagination info
     */
    public function listDocuments(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        // Build query
        $whereClause = "WHERE d.deleted_at IS NULL";
        $params = [];

        if (isset($filters['entity_type'])) {
            $whereClause .= " AND d.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (isset($filters['entity_id'])) {
            $whereClause .= " AND d.entity_id = ?";
            $params[] = $filters['entity_id'];
        }

        if (isset($filters['uploaded_by'])) {
            $whereClause .= " AND d.uploaded_by = ?";
            $params[] = $filters['uploaded_by'];
        }

        if (isset($filters['search'])) {
            $whereClause .= " AND (d.title LIKE ? OR d.description LIKE ? OR d.original_filename LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM documents d {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Get documents
        $stmt = $this->db->prepare("
            SELECT d.*, u.full_name as uploaded_by_name
            FROM documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            {$whereClause}
            ORDER BY d.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse tags
        foreach ($documents as &$document) {
            $document['tags'] = $document['tags'] ? json_decode($document['tags'], true) : [];
        }

        return [
            'documents' => $documents,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Update document metadata
     * 
     * @param int $documentId Document ID
     * @param array $data Updated data (title, description, tags)
     * @return bool Success status
     */
    public function updateDocument(int $documentId, array $data): bool
    {
        $updates = [];
        $params = [];

        if (isset($data['title'])) {
            $updates[] = "title = ?";
            $params[] = $data['title'];
        }

        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }

        if (isset($data['tags'])) {
            $updates[] = "tags = ?";
            $params[] = json_encode($data['tags']);
        }

        if (empty($updates)) {
            return false;
        }

        $updates[] = "updated_at = NOW()";
        $params[] = $documentId;

        $stmt = $this->db->prepare("
            UPDATE documents
            SET " . implode(', ', $updates) . "
            WHERE id = ? AND deleted_at IS NULL
        ");

        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete document (soft delete)
     * 
     * @param int $documentId Document ID
     * @param int $deletedBy User ID
     * @return bool Success status
     */
    public function deleteDocument(int $documentId, int $deletedBy): bool
    {
        $stmt = $this->db->prepare("
            UPDATE documents
            SET deleted_at = NOW(), deleted_by = ?
            WHERE id = ? AND deleted_at IS NULL
        ");

        $stmt->execute([$deletedBy, $documentId]);
        
        // Log deletion
        if ($stmt->rowCount() > 0) {
            $this->logAccess($documentId, $deletedBy, 'delete');
            return true;
        }

        return false;
    }

    /**
     * Permanently delete document and file
     * 
     * @param int $documentId Document ID
     * @return bool Success status
     */
    public function permanentlyDeleteDocument(int $documentId): bool
    {
        // Get document info
        $stmt = $this->db->prepare("SELECT filename FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            return false;
        }

        // Check if other documents reference this file
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM documents 
            WHERE filename = ? AND id != ? AND deleted_at IS NULL
        ");
        $stmt->execute([$document['filename'], $documentId]);
        $references = (int)$stmt->fetchColumn();

        // Delete file only if no other documents reference it
        if ($references === 0) {
            $filepath = $this->uploadPath . $document['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Delete database record
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Log document access
     * 
     * @param int $documentId Document ID
     * @param int $userId User ID
     * @param string $action Action (view, download, upload, delete)
     */
    private function logAccess(int $documentId, int $userId, string $action): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO document_access_logs (document_id, user_id, action, ip_address, user_agent, accessed_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $documentId,
                $userId,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log document access: " . $e->getMessage());
        }
    }

    /**
     * Get document access logs
     * 
     * @param int $documentId Document ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Access logs and pagination info
     */
    public function getAccessLogs(int $documentId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM document_access_logs WHERE document_id = ?");
        $stmt->execute([$documentId]);
        $total = (int)$stmt->fetchColumn();

        // Get logs
        $stmt = $this->db->prepare("
            SELECT dal.*, u.full_name as user_name
            FROM document_access_logs dal
            LEFT JOIN users u ON dal.user_id = u.id
            WHERE dal.document_id = ?
            ORDER BY dal.accessed_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->execute([$documentId, $perPage, $offset]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Get storage statistics
     * 
     * @return array Storage stats
     */
    public function getStorageStats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_documents,
                SUM(file_size) as total_size,
                AVG(file_size) as average_size,
                COUNT(DISTINCT entity_type) as entity_types,
                COUNT(DISTINCT uploaded_by) as unique_uploaders
            FROM documents
            WHERE deleted_at IS NULL
        ");

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get breakdown by entity type
        $stmt = $this->db->query("
            SELECT entity_type, COUNT(*) as count, SUM(file_size) as size
            FROM documents
            WHERE deleted_at IS NULL
            GROUP BY entity_type
        ");

        $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_documents' => (int)$stats['total_documents'],
            'total_size_bytes' => (int)$stats['total_size'],
            'total_size_mb' => round($stats['total_size'] / 1024 / 1024, 2),
            'average_size_bytes' => (int)$stats['average_size'],
            'entity_types' => (int)$stats['entity_types'],
            'unique_uploaders' => (int)$stats['unique_uploaders'],
            'breakdown_by_type' => $breakdown
        ];
    }
}
