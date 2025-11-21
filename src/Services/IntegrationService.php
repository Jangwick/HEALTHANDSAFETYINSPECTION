<?php

namespace App\Services;

use PDO;
use Exception;

class IntegrationService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Generate API key for integration
     * 
     * @param int $createdBy User ID
     * @param string $name API key name/description
     * @param array|null $permissions Array of allowed endpoints/permissions
     * @param string|null $expiresAt Expiration date (null = no expiration)
     * @return array API key details with plaintext key (only shown once)
     */
    public function generateApiKey(
        int $createdBy,
        string $name,
        ?array $permissions = null,
        ?string $expiresAt = null
    ): array {
        // Generate random API key
        $apiKey = 'hsi_' . bin2hex(random_bytes(32));
        $hashedKey = hash('sha256', $apiKey);

        $stmt = $this->db->prepare("
            INSERT INTO integration_api_keys (name, key_hash, permissions, created_by, expires_at, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $name,
            $hashedKey,
            $permissions ? json_encode($permissions) : null,
            $createdBy,
            $expiresAt
        ]);

        $keyId = (int)$this->db->lastInsertId();

        // Log API key creation
        $this->logIntegrationActivity('api_key_created', [
            'key_id' => $keyId,
            'name' => $name,
            'created_by' => $createdBy
        ]);

        return [
            'id' => $keyId,
            'name' => $name,
            'api_key' => $apiKey, // Only returned once!
            'permissions' => $permissions,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Validate API key
     * 
     * @param string $apiKey Plaintext API key
     * @return array|null API key details if valid, null otherwise
     */
    public function validateApiKey(string $apiKey): ?array
    {
        $hashedKey = hash('sha256', $apiKey);

        $stmt = $this->db->prepare("
            SELECT id, name, permissions, created_by, expires_at, last_used_at
            FROM integration_api_keys
            WHERE key_hash = ? AND is_active = 1 AND revoked_at IS NULL
            AND (expires_at IS NULL OR expires_at > NOW())
        ");

        $stmt->execute([$hashedKey]);
        $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($keyData) {
            // Update last used timestamp
            $stmt = $this->db->prepare("
                UPDATE integration_api_keys
                SET last_used_at = NOW(), usage_count = usage_count + 1
                WHERE id = ?
            ");
            $stmt->execute([$keyData['id']]);

            // Parse permissions
            $keyData['permissions'] = $keyData['permissions'] ? json_decode($keyData['permissions'], true) : null;
        }

        return $keyData ?: null;
    }

    /**
     * Revoke API key
     * 
     * @param int $keyId API key ID
     * @param int $revokedBy User ID
     * @return bool Success status
     */
    public function revokeApiKey(int $keyId, int $revokedBy): bool
    {
        $stmt = $this->db->prepare("
            UPDATE integration_api_keys
            SET is_active = 0, revoked_at = NOW(), revoked_by = ?
            WHERE id = ?
        ");

        $stmt->execute([$revokedBy, $keyId]);

        if ($stmt->rowCount() > 0) {
            $this->logIntegrationActivity('api_key_revoked', [
                'key_id' => $keyId,
                'revoked_by' => $revokedBy
            ]);
            return true;
        }

        return false;
    }

    /**
     * List API keys
     * 
     * @param int|null $createdBy Filter by creator
     * @param bool|null $isActive Filter by active status
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array API keys and pagination info
     */
    public function listApiKeys(
        ?int $createdBy = null,
        ?bool $isActive = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $offset = ($page - 1) * $perPage;

        $whereClause = "WHERE 1=1";
        $params = [];

        if ($createdBy !== null) {
            $whereClause .= " AND created_by = ?";
            $params[] = $createdBy;
        }

        if ($isActive !== null) {
            $whereClause .= " AND is_active = ?";
            $params[] = $isActive ? 1 : 0;
        }

        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM integration_api_keys {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Get API keys (without sensitive key_hash)
        $stmt = $this->db->prepare("
            SELECT id, name, permissions, created_by, is_active, usage_count,
                   last_used_at, expires_at, revoked_at, revoked_by, created_at
            FROM integration_api_keys
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse permissions
        foreach ($keys as &$key) {
            $key['permissions'] = $key['permissions'] ? json_decode($key['permissions'], true) : null;
        }

        return [
            'api_keys' => $keys,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Register webhook endpoint
     * 
     * @param int $createdBy User ID
     * @param string $url Webhook URL
     * @param array $events Events to trigger webhook
     * @param string|null $secret Webhook secret for signature verification
     * @param bool $isActive Active status
     * @return int Webhook ID
     */
    public function registerWebhook(
        int $createdBy,
        string $url,
        array $events,
        ?string $secret = null,
        bool $isActive = true
    ): int {
        // Generate secret if not provided
        if (!$secret) {
            $secret = bin2hex(random_bytes(32));
        }

        $stmt = $this->db->prepare("
            INSERT INTO integration_webhooks (url, events, secret, is_active, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $url,
            json_encode($events),
            hash('sha256', $secret),
            $isActive ? 1 : 0,
            $createdBy
        ]);

        $webhookId = (int)$this->db->lastInsertId();

        $this->logIntegrationActivity('webhook_registered', [
            'webhook_id' => $webhookId,
            'url' => $url,
            'events' => $events
        ]);

        return $webhookId;
    }

    /**
     * Trigger webhook
     * 
     * @param string $event Event name
     * @param array $data Event data
     * @return array Results with success/failure counts
     */
    public function triggerWebhook(string $event, array $data): array
    {
        // Get all active webhooks listening for this event
        $stmt = $this->db->prepare("
            SELECT id, url, secret
            FROM integration_webhooks
            WHERE is_active = 1 AND JSON_CONTAINS(events, ?)
        ");

        $stmt->execute([json_encode($event)]);
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [
            'success_count' => 0,
            'failure_count' => 0,
            'webhooks_triggered' => count($webhooks)
        ];

        foreach ($webhooks as $webhook) {
            $success = $this->deliverWebhook($webhook['id'], $webhook['url'], $event, $data, $webhook['secret']);
            
            if ($success) {
                $results['success_count']++;
            } else {
                $results['failure_count']++;
            }
        }

        return $results;
    }

    /**
     * Deliver webhook payload
     * 
     * @param int $webhookId Webhook ID
     * @param string $url Webhook URL
     * @param string $event Event name
     * @param array $data Event data
     * @param string $secret Webhook secret
     * @return bool Success status
     */
    private function deliverWebhook(int $webhookId, string $url, string $event, array $data, string $secret): bool
    {
        $payload = json_encode([
            'event' => $event,
            'timestamp' => time(),
            'data' => $data
        ]);

        // Generate signature
        $signature = hash_hmac('sha256', $payload, $secret);

        // Prepare cURL request
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature,
                'X-Webhook-Event: ' . $event,
                'User-Agent: HealthInspection-Webhook/1.0'
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);

        // Log webhook delivery
        $this->logWebhookDelivery($webhookId, $event, $success, $httpCode, $error);

        return $success;
    }

    /**
     * Log webhook delivery
     * 
     * @param int $webhookId Webhook ID
     * @param string $event Event name
     * @param bool $success Success status
     * @param int|null $httpCode HTTP response code
     * @param string|null $error Error message
     */
    private function logWebhookDelivery(
        int $webhookId,
        string $event,
        bool $success,
        ?int $httpCode = null,
        ?string $error = null
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO integration_webhook_logs 
                (webhook_id, event, status, http_code, error_message, delivered_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $webhookId,
                $event,
                $success ? 'success' : 'failed',
                $httpCode,
                $error
            ]);

            // Update webhook last triggered timestamp
            $stmt = $this->db->prepare("
                UPDATE integration_webhooks
                SET last_triggered_at = NOW(), delivery_count = delivery_count + 1
                WHERE id = ?
            ");
            $stmt->execute([$webhookId]);
        } catch (Exception $e) {
            error_log("Failed to log webhook delivery: " . $e->getMessage());
        }
    }

    /**
     * List webhooks
     * 
     * @param int|null $createdBy Filter by creator
     * @param bool|null $isActive Filter by active status
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Webhooks and pagination info
     */
    public function listWebhooks(
        ?int $createdBy = null,
        ?bool $isActive = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $offset = ($page - 1) * $perPage;

        $whereClause = "WHERE 1=1";
        $params = [];

        if ($createdBy !== null) {
            $whereClause .= " AND created_by = ?";
            $params[] = $createdBy;
        }

        if ($isActive !== null) {
            $whereClause .= " AND is_active = ?";
            $params[] = $isActive ? 1 : 0;
        }

        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM integration_webhooks {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Get webhooks (without secret)
        $stmt = $this->db->prepare("
            SELECT id, url, events, is_active, delivery_count, last_triggered_at, created_by, created_at
            FROM integration_webhooks
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse events
        foreach ($webhooks as &$webhook) {
            $webhook['events'] = json_decode($webhook['events'], true);
        }

        return [
            'webhooks' => $webhooks,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Delete webhook
     * 
     * @param int $webhookId Webhook ID
     * @return bool Success status
     */
    public function deleteWebhook(int $webhookId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM integration_webhooks WHERE id = ?");
        $stmt->execute([$webhookId]);

        if ($stmt->rowCount() > 0) {
            $this->logIntegrationActivity('webhook_deleted', ['webhook_id' => $webhookId]);
            return true;
        }

        return false;
    }

    /**
     * Log integration activity
     * 
     * @param string $action Action performed
     * @param array $data Action data
     */
    private function logIntegrationActivity(string $action, array $data): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO integration_logs (action, data, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $action,
                json_encode($data),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log integration activity: " . $e->getMessage());
        }
    }

    /**
     * Get integration logs
     * 
     * @param string|null $action Filter by action
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Logs and pagination info
     */
    public function getIntegrationLogs(
        ?string $action = null,
        int $page = 1,
        int $perPage = 50
    ): array {
        $offset = ($page - 1) * $perPage;

        $whereClause = "WHERE 1=1";
        $params = [];

        if ($action) {
            $whereClause .= " AND action = ?";
            $params[] = $action;
        }

        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM integration_logs {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Get logs
        $stmt = $this->db->prepare("
            SELECT id, action, data, ip_address, user_agent, created_at
            FROM integration_logs
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse data
        foreach ($logs as &$log) {
            $log['data'] = json_decode($log['data'], true);
        }

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
     * Get webhook delivery statistics
     * 
     * @param int $webhookId Webhook ID
     * @return array Statistics
     */
    public function getWebhookStats(int $webhookId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_deliveries,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_deliveries,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_deliveries,
                MAX(delivered_at) as last_delivery_at
            FROM integration_webhook_logs
            WHERE webhook_id = ?
        ");

        $stmt->execute([$webhookId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total_deliveries' => (int)$stats['total_deliveries'],
            'successful_deliveries' => (int)$stats['successful_deliveries'],
            'failed_deliveries' => (int)$stats['failed_deliveries'],
            'success_rate' => $stats['total_deliveries'] > 0 
                ? round(($stats['successful_deliveries'] / $stats['total_deliveries']) * 100, 2)
                : 0,
            'last_delivery_at' => $stats['last_delivery_at']
        ];
    }
}
