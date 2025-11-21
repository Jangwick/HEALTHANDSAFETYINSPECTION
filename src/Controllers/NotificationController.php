<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Utils\Response;
use App\Utils\Validator;

class NotificationController
{
    private NotificationService $notificationService;
    private array $user;

    public function __construct(NotificationService $notificationService, array $user)
    {
        $this->notificationService = $notificationService;
        $this->user = $user;
    }

    /**
     * Get user notifications
     * GET /api/notifications
     */
    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = min((int)($_GET['per_page'] ?? 20), 100);
        $isRead = isset($_GET['is_read']) ? filter_var($_GET['is_read'], FILTER_VALIDATE_BOOLEAN) : null;

        $result = $this->notificationService->getUserNotifications(
            $this->user['id'],
            $page,
            $perPage,
            $isRead
        );

        Response::success($result);
    }

    /**
     * Get unread notification count
     * GET /api/notifications/unread-count
     */
    public function unreadCount(): void
    {
        $count = $this->notificationService->getUnreadCount($this->user['id']);

        Response::success(['unread_count' => $count]);
    }

    /**
     * Mark notification as read
     * PATCH /api/notifications/:id/read
     */
    public function markAsRead(int $id): void
    {
        $success = $this->notificationService->markAsRead($id, $this->user['id']);

        if ($success) {
            Response::success(['message' => 'Notification marked as read']);
        } else {
            Response::error('Notification not found or already read', 404);
        }
    }

    /**
     * Mark all notifications as read
     * PATCH /api/notifications/mark-all-read
     */
    public function markAllAsRead(): void
    {
        $count = $this->notificationService->markAllAsRead($this->user['id']);

        Response::success([
            'message' => 'All notifications marked as read',
            'count' => $count
        ]);
    }

    /**
     * Send notification (admin/system use)
     * POST /api/notifications/send
     * Requires: notifications.send permission
     */
    public function send(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = Validator::validate($data, [
            'user_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'string',
            'channels' => 'array'
        ]);

        if (!$validation['valid']) {
            Response::error('Validation failed', 400, $validation['errors']);
            return;
        }

        $result = $this->notificationService->sendNotification(
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info',
            $data['channels'] ?? ['in_app'],
            $data['data'] ?? null
        );

        if ($result['success']) {
            Response::success([
                'message' => 'Notification sent successfully',
                'notification_id' => $result['notification_id']
            ], 201);
        } else {
            Response::error('Failed to send notification: ' . $result['error'], 500);
        }
    }

    /**
     * Send bulk notifications
     * POST /api/notifications/send-bulk
     * Requires: notifications.send permission
     */
    public function sendBulk(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = Validator::validate($data, [
            'user_ids' => 'required|array',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'string',
            'channels' => 'array'
        ]);

        if (!$validation['valid']) {
            Response::error('Validation failed', 400, $validation['errors']);
            return;
        }

        $result = $this->notificationService->sendBulkNotification(
            $data['user_ids'],
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info',
            $data['channels'] ?? ['in_app'],
            $data['data'] ?? null
        );

        Response::success([
            'message' => 'Bulk notifications sent',
            'success_count' => $result['success_count'],
            'failed_count' => count($result['failed_user_ids']),
            'total' => $result['total']
        ], 201);
    }

    /**
     * Get notification preferences
     * GET /api/notifications/preferences
     */
    public function getPreferences(): void
    {
        $preferences = $this->notificationService->getPreferences($this->user['id']);

        Response::success($preferences);
    }

    /**
     * Update notification preferences
     * PUT /api/notifications/preferences
     */
    public function updatePreferences(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $validation = Validator::validate($data, [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'in_app_enabled' => 'boolean'
        ]);

        if (!$validation['valid']) {
            Response::error('Validation failed', 400, $validation['errors']);
            return;
        }

        $success = $this->notificationService->updatePreferences($this->user['id'], $data);

        if ($success) {
            Response::success(['message' => 'Notification preferences updated successfully']);
        } else {
            Response::error('Failed to update notification preferences', 500);
        }
    }
}
