<?php

namespace App\Controllers;

use App\Services\IntegrationService;
use App\Utils\Response;
use App\Utils\Validator;

class IntegrationController
{
    private IntegrationService $integrationService;
    private array $user;

    public function __construct(IntegrationService $integrationService, array $user)
    {
        $this->integrationService = $integrationService;
        $this->user = $user;
    }

    /**
     * Generate API key
     * POST /api/integrations/api-keys
     * Requires: integrations.manage permission
     */
    public function generateApiKey(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = Validator::validate($data, [
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'expires_at' => 'date'
        ]);

        if (!$validation['valid']) {
            Response::error('Validation failed', 400, $validation['errors']);
            return;
        }

        $result = $this->integrationService->generateApiKey(
            $this->user['id'],
            $data['name'],
            $data['permissions'] ?? null,
            $data['expires_at'] ?? null
        );

        Response::success($result, 201);
    }

    /**
     * List API keys
     * GET /api/integrations/api-keys
     * Requires: integrations.read permission
     */
    public function listApiKeys(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 20), 100);
        $isActive = isset($_GET['is_active']) ? filter_var($_GET['is_active'], FILTER_VALIDATE_BOOLEAN) : null;

        $result = $this->integrationService->listApiKeys(
            null, // Show all API keys for admins
            $isActive,
            $page,
            $perPage
        );

        Response::success($result);
    }

    /**
     * Revoke API key
     * DELETE /api/integrations/api-keys/:id
     * Requires: integrations.manage permission
     */
    public function revokeApiKey(int $id): void
    {
        $success = $this->integrationService->revokeApiKey($id, $this->user['id']);

        if ($success) {
            Response::success(['message' => 'API key revoked successfully']);
        } else {
            Response::error('API key not found', 404);
        }
    }

    /**
     * Register webhook
     * POST /api/integrations/webhooks
     * Requires: integrations.manage permission
     */
    public function registerWebhook(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = Validator::validate($data, [
            'url' => 'required|string',
            'events' => 'required|array',
            'secret' => 'string',
            'is_active' => 'boolean'
        ]);

        if (!$validation['valid']) {
            Response::error('Validation failed', 400, $validation['errors']);
            return;
        }

        // Validate URL
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            Response::error('Invalid URL format', 400);
            return;
        }

        $webhookId = $this->integrationService->registerWebhook(
            $this->user['id'],
            $data['url'],
            $data['events'],
            $data['secret'] ?? null,
            $data['is_active'] ?? true
        );

        Response::success([
            'message' => 'Webhook registered successfully',
            'webhook_id' => $webhookId
        ], 201);
    }

    /**
     * List webhooks
     * GET /api/integrations/webhooks
     * Requires: integrations.read permission
     */
    public function listWebhooks(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 20), 100);
        $isActive = isset($_GET['is_active']) ? filter_var($_GET['is_active'], FILTER_VALIDATE_BOOLEAN) : null;

        $result = $this->integrationService->listWebhooks(
            null, // Show all webhooks for admins
            $isActive,
            $page,
            $perPage
        );

        Response::success($result);
    }

    /**
     * Delete webhook
     * DELETE /api/integrations/webhooks/:id
     * Requires: integrations.manage permission
     */
    public function deleteWebhook(int $id): void
    {
        $success = $this->integrationService->deleteWebhook($id);

        if ($success) {
            Response::success(['message' => 'Webhook deleted successfully']);
        } else {
            Response::error('Webhook not found', 404);
        }
    }

    /**
     * Get webhook statistics
     * GET /api/integrations/webhooks/:id/stats
     * Requires: integrations.read permission
     */
    public function webhookStats(int $id): void
    {
        $stats = $this->integrationService->getWebhookStats($id);

        Response::success($stats);
    }

    /**
     * Trigger webhook manually (for testing)
     * POST /api/integrations/webhooks/:id/trigger
     * Requires: integrations.manage permission
     */
    public function triggerWebhook(int $id): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = Validator::validate($data, [
            'event' => 'required|string',
            'data' => 'array'
        ]);

        if (!$validation['valid']) {
            Response::error('Validation failed', 400, $validation['errors']);
            return;
        }

        $result = $this->integrationService->triggerWebhook(
            $data['event'],
            $data['data'] ?? []
        );

        Response::success($result);
    }

    /**
     * Get integration logs
     * GET /api/integrations/logs
     * Requires: integrations.read permission
     */
    public function integrationLogs(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 50), 100);
        $action = $_GET['action'] ?? null;

        $result = $this->integrationService->getIntegrationLogs($action, $page, $perPage);

        Response::success($result);
    /**
     * Cross-Cluster Integration: Notify Law Enforcement (LGU 4 Subsystem)
     * POST /api/integrations/notify-police
     */
    public function notifyLawEnforcement(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['violation_id'])) {
            Response::error('violation_id is required', 400);
            return;
        }

        $reason = $data['reason'] ?? 'Critical safety violation detected requiring police enforcement.';
        
        $success = $this->integrationService->notifyLawEnforcement((int)$data['violation_id'], $reason);

        if ($success) {
            Response::success(['message' => 'Police notification dispatched successfully']);
        } else {
            Response::error('Failed to dispatch notification. Violation record not found.', 404);
        }
    }
}
