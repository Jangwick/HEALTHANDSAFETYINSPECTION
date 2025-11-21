<?php

declare(strict_types=1);

namespace HealthSafety\Utils;

/**
 * Response Helper Utility
 * Health & Safety Inspections System
 */
class Response
{
    /**
     * Send JSON success response
     */
    public static function success(mixed $data = null, string $message = null, int $statusCode = HTTP_OK): void
    {
        http_response_code($statusCode);
        
        $response = [
            'success' => true,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        $response['meta'] = [
            'timestamp' => date('c'),
            'version' => APP_VERSION,
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Send JSON error response
     */
    public static function error(
        string $code,
        string $message,
        mixed $details = null,
        int $statusCode = HTTP_BAD_REQUEST
    ): void {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        $response['meta'] = [
            'timestamp' => date('c'),
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Send validation error response
     */
    public static function validationError(array $errors): void
    {
        self::error(
            ERROR_VALIDATION,
            'Validation failed',
            $errors,
            HTTP_UNPROCESSABLE
        );
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized access'): void
    {
        self::error(
            ERROR_UNAUTHORIZED,
            $message,
            null,
            HTTP_UNAUTHORIZED
        );
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Access forbidden'): void
    {
        self::error(
            ERROR_FORBIDDEN,
            $message,
            null,
            HTTP_FORBIDDEN
        );
    }
    
    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error(
            ERROR_NOT_FOUND,
            $message,
            null,
            HTTP_NOT_FOUND
        );
    }
    
    /**
     * Send rate limit exceeded response
     */
    public static function rateLimitExceeded(int $retryAfter = 60): void
    {
        header("Retry-After: $retryAfter");
        
        self::error(
            ERROR_RATE_LIMIT,
            'Too many requests. Please try again later.',
            ['retry_after' => $retryAfter],
            HTTP_TOO_MANY_REQUESTS
        );
    }
    
    /**
     * Send server error response
     */
    public static function serverError(string $message = 'Internal server error', mixed $details = null): void
    {
        // Don't expose internal details in production
        if (!APP_DEBUG) {
            $details = null;
        }
        
        self::error(
            ERROR_SERVER,
            $message,
            $details,
            HTTP_SERVER_ERROR
        );
    }
    
    /**
     * Send paginated response
     */
    public static function paginated(
        array $data,
        int $total,
        int $page,
        int $pageSize,
        array $meta = []
    ): void {
        $totalPages = (int)ceil($total / $pageSize);
        
        self::success([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1,
            ],
        ] + $meta);
    }
    
    /**
     * Send file download response
     */
    public static function download(string $filePath, string $filename = null, string $mimeType = null): void
    {
        if (!file_exists($filePath)) {
            self::notFound('File not found');
        }
        
        $filename = $filename ?? basename($filePath);
        $mimeType = $mimeType ?? mime_content_type($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }
    
    /**
     * Send CSV response
     */
    public static function csv(array $data, string $filename = 'export.csv'): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
