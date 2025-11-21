<?php

namespace App\Services;

use PDO;
use Exception;

class NotificationService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Send a notification to a user
     * 
     * @param int $userId User ID to send notification to
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type (info, success, warning, error)
     * @param array $channels Channels to send through (email, sms, in_app)
     * @param array|null $data Additional data (inspection_id, establishment_id, etc)
     * @return array Result with success status and notification ID
     */
    public function sendNotification(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        array $channels = ['in_app'],
        ?array $data = null
    ): array {
        try {
            // Get user details and preferences
            $stmt = $this->db->prepare("
                SELECT u.*, np.email_enabled, np.sms_enabled, np.in_app_enabled
                FROM users u
                LEFT JOIN notification_preferences np ON u.id = np.user_id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("User not found");
            }

            // Create in-app notification if enabled
            $notificationId = null;
            if (in_array('in_app', $channels) && ($user['in_app_enabled'] ?? true)) {
                $notificationId = $this->createInAppNotification(
                    $userId,
                    $title,
                    $message,
                    $type,
                    $data
                );
            }

            // Send email if enabled
            if (in_array('email', $channels) && ($user['email_enabled'] ?? true)) {
                $this->sendEmailNotification(
                    $user['email'],
                    $user['full_name'],
                    $title,
                    $message,
                    $type,
                    $data
                );
            }

            // Send SMS if enabled
            if (in_array('sms', $channels) && ($user['sms_enabled'] ?? false)) {
                $this->sendSMSNotification(
                    $user['contact_number'] ?? null,
                    $title,
                    $message,
                    $data
                );
            }

            return [
                'success' => true,
                'notification_id' => $notificationId
            ];
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create an in-app notification
     * 
     * @param int $userId User ID
     * @param string $title Title
     * @param string $message Message
     * @param string $type Type (info, success, warning, error)
     * @param array|null $data Additional data
     * @return int Notification ID
     */
    private function createInAppNotification(
        int $userId,
        string $title,
        string $message,
        string $type,
        ?array $data
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type, data, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");

        $stmt->execute([
            $userId,
            $title,
            $message,
            $type,
            $data ? json_encode($data) : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Send email notification
     * 
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $title Title
     * @param string $message Message
     * @param string $type Type (info, success, warning, error)
     * @param array|null $data Additional data
     * @return bool Success status
     */
    private function sendEmailNotification(
        string $email,
        string $name,
        string $title,
        string $message,
        string $type,
        ?array $data
    ): bool {
        try {
            // Get email template
            $template = $this->getEmailTemplate($type);
            
            // Replace placeholders
            $htmlContent = str_replace(
                ['{name}', '{title}', '{message}', '{year}'],
                [$name, $title, $message, date('Y')],
                $template
            );

            // Email headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . ($_ENV['MAIL_FROM'] ?? 'noreply@healthinspection.local'),
                'Reply-To: ' . ($_ENV['MAIL_REPLY_TO'] ?? 'support@healthinspection.local'),
                'X-Mailer: PHP/' . phpversion()
            ];

            // Send email
            $result = mail(
                $email,
                $title,
                $htmlContent,
                implode("\r\n", $headers)
            );

            // Log email sent
            $this->logNotificationDelivery('email', $email, $result);

            return $result;
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS notification
     * 
     * @param string|null $phone Phone number
     * @param string $title Title
     * @param string $message Message
     * @param array|null $data Additional data
     * @return bool Success status
     */
    private function sendSMSNotification(
        ?string $phone,
        string $title,
        string $message,
        ?array $data
    ): bool {
        try {
            if (empty($phone)) {
                return false;
            }

            // Format message (SMS has character limit)
            $smsMessage = substr($title . ': ' . $message, 0, 160);

            // TODO: Integrate with SMS provider (e.g., Semaphore, Twilio)
            // For now, log the SMS attempt
            error_log("SMS would be sent to {$phone}: {$smsMessage}");
            
            // Log SMS delivery attempt
            $this->logNotificationDelivery('sms', $phone, true);

            return true;
        } catch (Exception $e) {
            error_log("SMS send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email template
     * 
     * @param string $type Template type
     * @return string HTML template
     */
    private function getEmailTemplate(string $type): string
    {
        $color = match($type) {
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'error' => '#ef4444',
            default => '#3b82f6'
        };

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    <tr>
                        <td style="background-color: {$color}; padding: 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Health & Safety Inspection System</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px;">
                            <p style="color: #374151; font-size: 16px; margin: 0 0 10px 0;">Hello {name},</p>
                            <h2 style="color: #111827; margin: 0 0 20px 0;">{title}</h2>
                            <div style="color: #4b5563; font-size: 14px; line-height: 1.6;">
                                {message}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                &copy; {year} Health & Safety Inspection System. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Log notification delivery
     * 
     * @param string $channel Channel used (email, sms)
     * @param string $recipient Recipient address/phone
     * @param bool $success Success status
     */
    private function logNotificationDelivery(string $channel, string $recipient, bool $success): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs (channel, recipient, status, sent_at)
                VALUES (?, ?, ?, NOW())
            ");

            $stmt->execute([
                $channel,
                $recipient,
                $success ? 'sent' : 'failed'
            ]);
        } catch (Exception $e) {
            error_log("Failed to log notification delivery: " . $e->getMessage());
        }
    }

    /**
     * Get user notifications (paginated)
     * 
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param bool|null $isRead Filter by read status (null = all)
     * @return array Notifications and pagination info
     */
    public function getUserNotifications(
        int $userId,
        int $page = 1,
        int $perPage = 20,
        ?bool $isRead = null
    ): array {
        $offset = ($page - 1) * $perPage;

        // Build query
        $whereClause = "WHERE user_id = ?";
        $params = [$userId];

        if ($isRead !== null) {
            $whereClause .= " AND is_read = ?";
            $params[] = $isRead ? 1 : 0;
        }

        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Get notifications
        $stmt = $this->db->prepare("
            SELECT id, title, message, type, data, is_read, created_at
            FROM notifications
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON data
        foreach ($notifications as &$notification) {
            $notification['data'] = $notification['data'] ? json_decode($notification['data'], true) : null;
        }

        return [
            'notifications' => $notifications,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Mark notification as read
     * 
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for ownership verification)
     * @return bool Success status
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([$notificationId, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Mark all notifications as read for a user
     * 
     * @param int $userId User ID
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(int $userId): int
    {
        $stmt = $this->db->prepare("
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ");

        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    /**
     * Get unread notification count
     * 
     * @param int $userId User ID
     * @return int Unread count
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = ? AND is_read = 0
        ");

        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Update user notification preferences
     * 
     * @param int $userId User ID
     * @param array $preferences Preferences (email_enabled, sms_enabled, in_app_enabled)
     * @return bool Success status
     */
    public function updatePreferences(int $userId, array $preferences): bool
    {
        try {
            // Check if preferences exist
            $stmt = $this->db->prepare("SELECT id FROM notification_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Update existing preferences
                $stmt = $this->db->prepare("
                    UPDATE notification_preferences
                    SET email_enabled = ?, sms_enabled = ?, in_app_enabled = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");

                $stmt->execute([
                    $preferences['email_enabled'] ?? true,
                    $preferences['sms_enabled'] ?? false,
                    $preferences['in_app_enabled'] ?? true,
                    $userId
                ]);
            } else {
                // Create new preferences
                $stmt = $this->db->prepare("
                    INSERT INTO notification_preferences 
                    (user_id, email_enabled, sms_enabled, in_app_enabled, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    $userId,
                    $preferences['email_enabled'] ?? true,
                    $preferences['sms_enabled'] ?? false,
                    $preferences['in_app_enabled'] ?? true
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log("Failed to update notification preferences: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user notification preferences
     * 
     * @param int $userId User ID
     * @return array Preferences
     */
    public function getPreferences(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT email_enabled, sms_enabled, in_app_enabled
            FROM notification_preferences
            WHERE user_id = ?
        ");

        $stmt->execute([$userId]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return defaults if no preferences set
        return $preferences ?: [
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true
        ];
    }

    /**
     * Send bulk notifications to multiple users
     * 
     * @param array $userIds Array of user IDs
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param array $channels Channels to send through
     * @param array|null $data Additional data
     * @return array Results with success count and failed user IDs
     */
    public function sendBulkNotification(
        array $userIds,
        string $title,
        string $message,
        string $type = 'info',
        array $channels = ['in_app'],
        ?array $data = null
    ): array {
        $successCount = 0;
        $failed = [];

        foreach ($userIds as $userId) {
            $result = $this->sendNotification($userId, $title, $message, $type, $channels, $data);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failed[] = $userId;
            }
        }

        return [
            'success_count' => $successCount,
            'failed_user_ids' => $failed,
            'total' => count($userIds)
        ];
    }

    /**
     * Delete old notifications (cleanup)
     * 
     * @param int $daysOld Delete notifications older than this many days
     * @return int Number of notifications deleted
     */
    public function deleteOldNotifications(int $daysOld = 90): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM notifications
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");

        $stmt->execute([$daysOld]);
        return $stmt->rowCount();
    }
}
