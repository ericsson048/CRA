<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class TeamModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('
            SELECT
                t.id,
                t.nom,
                t.description,
                t.tl_user_id,
                t.tla_user_id,
                tl.nom AS tl_name,
                tla.nom AS tla_name,
                t.created_at,
                (SELECT COUNT(*) FROM users u WHERE u.team_id = t.id) AS members_count
            FROM teams t
            LEFT JOIN users tl ON tl.id = t.tl_user_id
            LEFT JOIN users tla ON tla.id = t.tla_user_id
            ORDER BY t.id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array{id:int, nom:string}>
     */
    public function selectList(): array
    {
        $stmt = $this->pdo->query('SELECT id, nom FROM teams ORDER BY nom ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, nom, description, tl_user_id, tla_user_id, created_at
            FROM teams
            WHERE id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array{nom:string, description:?string, tl_user_id:?int, tla_user_id:?int} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO teams (nom, description, tl_user_id, tla_user_id)
            VALUES (:nom, :description, :tl_user_id, :tla_user_id)
        ');
        $stmt->execute([
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':tl_user_id' => $data['tl_user_id'],
            ':tla_user_id' => $data['tla_user_id'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array{nom:string, description:?string, tl_user_id:?int, tla_user_id:?int} $data
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE teams
            SET nom = :nom, description = :description, tl_user_id = :tl_user_id, tla_user_id = :tla_user_id
            WHERE id = :id
        ');
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':tl_user_id' => $data['tl_user_id'],
            ':tla_user_id' => $data['tla_user_id'],
            ':id' => $id,
        ]);
    }

    public function existsName(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM teams WHERE nom = :nom';
        $params = [':nom' => $name];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }
}
