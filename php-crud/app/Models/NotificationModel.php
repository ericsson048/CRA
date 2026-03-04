<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class NotificationModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, user_id, title, message, action_url, is_read, read_at, created_at
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY is_read ASC, created_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUnreadForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(int $userId, string $title, string $message, ?string $actionUrl = null): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO notifications (user_id, title, message, action_url, is_read, created_at)
            VALUES (:user_id, :title, :message, :action_url, 0, NOW())
        ');
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':message' => $message,
            ':action_url' => $actionUrl,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE id = :id AND user_id = :user_id
        ');
        return $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId,
        ]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE notifications
            SET is_read = 1, read_at = NOW()
            WHERE user_id = :user_id AND is_read = 0
        ');
        return $stmt->execute([':user_id' => $userId]);
    }
}
