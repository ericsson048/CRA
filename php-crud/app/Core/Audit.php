<?php
declare(strict_types=1);

namespace App\Core;

final class Audit
{
    public static function log(string $action, string $entityType, ?int $entityId = null, array $details = []): void
    {
        try {
            $pdo = Database::connection();
            $sessionUser = Auth::user();
            $stmt = $pdo->prepare('
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, details_json, created_at)
                VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :details_json, NOW())
            ');
            $stmt->execute([
                ':user_id' => isset($sessionUser['id']) ? (int)$sessionUser['id'] : null,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':ip_address' => self::ipAddress(),
                ':details_json' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable $exception) {
            Logger::error('Echec audit log', [
                'action' => $action,
                'entity_type' => $entityType,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private static function ipAddress(): string
    {
        $candidates = [
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['HTTP_CLIENT_IP'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }
            $value = trim(explode(',', $candidate)[0]);
            if ($value !== '') {
                return $value;
            }
        }

        return 'unknown';
    }
}
