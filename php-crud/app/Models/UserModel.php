<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class UserModel
{
    /** @var array<int, string> */
    public const ROLES = ['admin', 'gestionnaire', 'team_leader', 'team_leader_adjoint', 'developpeur'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.nom, u.email, u.password_hash, u.role, u.team_id, t.nom AS team_name, u.created_at
            FROM users u
            LEFT JOIN teams t ON t.id = u.team_id
            WHERE u.email = :email
            LIMIT 1
        ');
        $stmt->execute([':email' => $email]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.nom, u.email, u.role, u.team_id, t.nom AS team_name, u.created_at
            FROM users u
            LEFT JOIN teams t ON t.id = u.team_id
            WHERE u.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('
            SELECT u.id, u.nom, u.email, u.role, u.team_id, t.nom AS team_name, u.created_at
            FROM users u
            LEFT JOIN teams t ON t.id = u.team_id
            ORDER BY u.id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function developers(?int $teamId = null): array
    {
        $sql = "SELECT id, nom, team_id FROM users WHERE role = 'developpeur'";
        $params = [];
        if ($teamId !== null) {
            $sql .= ' AND team_id = :team_id';
            $params[':team_id'] = $teamId;
        }
        $sql .= ' ORDER BY nom ASC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function leaders(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, nom, role, team_id
            FROM users
            WHERE role IN ('team_leader', 'team_leader_adjoint')
            ORDER BY nom ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function managers(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, nom, role
            FROM users
            WHERE role IN ('admin', 'gestionnaire')
            ORDER BY nom ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array{nom:string, email:string, password_hash:string, role:string, team_id:?int} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (nom, email, password_hash, role, team_id)
            VALUES (:nom, :email, :password_hash, :role, :team_id)
        ');
        $stmt->execute([
            ':nom' => $data['nom'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role' => $data['role'],
            ':team_id' => $data['team_id'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function setTeam(int $userId, ?int $teamId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET team_id = :team_id WHERE id = :id');
        return $stmt->execute([
            ':team_id' => $teamId,
            ':id' => $userId,
        ]);
    }

    public function canCreateRole(string $creatorRole, string $targetRole): bool
    {
        if (!in_array($targetRole, self::ROLES, true)) {
            return false;
        }

        if ($creatorRole === 'admin') {
            return in_array($targetRole, ['gestionnaire', 'team_leader', 'team_leader_adjoint', 'developpeur'], true);
        }
        if ($creatorRole === 'gestionnaire') {
            return in_array($targetRole, ['team_leader', 'team_leader_adjoint', 'developpeur'], true);
        }
        return false;
    }
}
