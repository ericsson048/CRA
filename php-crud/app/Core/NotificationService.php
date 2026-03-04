<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\NotificationModel;
use App\Models\UserModel;

final class NotificationService
{
    public function notifyUser(int $userId, string $title, string $message, ?string $actionUrl = null, ?string $emailSubject = null, ?string $emailHtml = null): void
    {
        $pdo = Database::connection();
        $notificationModel = new NotificationModel($pdo);
        $userModel = new UserModel($pdo);

        $notificationModel->create($userId, $title, $message, $actionUrl);
        Audit::log('notification_created', 'notification', null, ['user_id' => $userId, 'title' => $title]);

        $user = $userModel->findById($userId);
        if ($user === null || !(bool)($user['is_active'] ?? false)) {
            return;
        }

        if ($emailSubject !== null && $emailHtml !== null && !empty($user['email'])) {
            $emailService = new EmailService();
            $emailService->send(
                (string)$user['email'],
                (string)$user['nom'],
                $emailSubject,
                $emailHtml,
                $message
            );
        }
    }

    /**
     * @param array<int, int> $userIds
     */
    public function notifyMany(array $userIds, string $title, string $message, ?string $actionUrl = null, ?string $emailSubject = null, ?string $emailHtml = null): void
    {
        $unique = array_values(array_unique(array_filter($userIds, static fn ($id): bool => (int)$id > 0)));
        foreach ($unique as $userId) {
            $this->notifyUser((int)$userId, $title, $message, $actionUrl, $emailSubject, $emailHtml);
        }
    }
}
