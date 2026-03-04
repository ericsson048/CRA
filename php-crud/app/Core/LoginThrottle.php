<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class LoginThrottle
{
    public static function isBlocked(PDO $pdo, string $identifier, string $ipAddress): bool
    {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM login_attempts
            WHERE identifier = :identifier
              AND ip_address = :ip_address
              AND success = 0
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL :window_minutes MINUTE)
        ');
        $stmt->bindValue(':identifier', mb_strtolower(trim($identifier)), PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->bindValue(':window_minutes', AppConfig::loginWindowMinutes(), PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn() >= AppConfig::loginMaxAttempts();
    }

    public static function record(PDO $pdo, string $identifier, string $ipAddress, bool $success): void
    {
        $stmt = $pdo->prepare('
            INSERT INTO login_attempts (identifier, ip_address, success, attempted_at)
            VALUES (:identifier, :ip_address, :success, NOW())
        ');
        $stmt->execute([
            ':identifier' => mb_strtolower(trim($identifier)),
            ':ip_address' => $ipAddress,
            ':success' => $success ? 1 : 0,
        ]);
    }

    public static function clearFailures(PDO $pdo, string $identifier, string $ipAddress): void
    {
        $stmt = $pdo->prepare('
            DELETE FROM login_attempts
            WHERE identifier = :identifier
              AND ip_address = :ip_address
              AND success = 0
        ');
        $stmt->execute([
            ':identifier' => mb_strtolower(trim($identifier)),
            ':ip_address' => $ipAddress,
        ]);
    }
}
