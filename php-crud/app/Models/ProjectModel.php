<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class ProjectModel
{
    /** @var array<int, string> */
    public const STATUS = ['Planifie', 'En cours', 'En pause', 'Termine'];

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
                p.id,
                p.nom,
                p.description,
                p.statut,
                p.start_date,
                p.end_date,
                p.team_id,
                t.nom AS team_name,
                p.tl_user_id,
                tl.nom AS tl_name,
                p.tla_user_id,
                tla.nom AS tla_name,
                p.assigned_by,
                m.nom AS assigned_by_name,
                p.created_at
            FROM projects p
            INNER JOIN teams t ON t.id = p.team_id
            INNER JOIN users tl ON tl.id = p.tl_user_id
            LEFT JOIN users tla ON tla.id = p.tla_user_id
            INNER JOIN users m ON m.id = p.assigned_by
            ORDER BY p.id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                p.id,
                p.nom,
                p.description,
                p.statut,
                p.start_date,
                p.end_date,
                p.team_id,
                t.nom AS team_name,
                p.tl_user_id,
                tl.nom AS tl_name,
                p.tla_user_id,
                tla.nom AS tla_name,
                p.assigned_by,
                m.nom AS assigned_by_name,
                p.created_at
            FROM projects p
            INNER JOIN teams t ON t.id = p.team_id
            INNER JOIN users tl ON tl.id = p.tl_user_id
            LEFT JOIN users tla ON tla.id = p.tla_user_id
            INNER JOIN users m ON m.id = p.assigned_by
            WHERE p.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return array<int, array{id:int, nom:string, team_id:int}>
     */
    public function selectForLead(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, nom, team_id
            FROM projects
            WHERE tl_user_id = :uid OR tla_user_id = :uid
            ORDER BY nom ASC
        ');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array{id:int, nom:string, team_id:int}>
     */
    public function selectForManager(): array
    {
        $stmt = $this->pdo->query('SELECT id, nom, team_id FROM projects ORDER BY nom ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array{nom:string, description:?string, statut:string, start_date:?string, end_date:?string, team_id:int, tl_user_id:int, tla_user_id:?int, assigned_by:int} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO projects (nom, description, statut, start_date, end_date, team_id, tl_user_id, tla_user_id, assigned_by)
            VALUES (:nom, :description, :statut, :start_date, :end_date, :team_id, :tl_user_id, :tla_user_id, :assigned_by)
        ');
        $stmt->execute([
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':statut' => $data['statut'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':team_id' => $data['team_id'],
            ':tl_user_id' => $data['tl_user_id'],
            ':tla_user_id' => $data['tla_user_id'],
            ':assigned_by' => $data['assigned_by'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function isManagedByLead(int $projectId, int $leadUserId): bool
    {
        $stmt = $this->pdo->prepare('
            SELECT id
            FROM projects
            WHERE id = :id AND (tl_user_id = :uid OR tla_user_id = :uid)
            LIMIT 1
        ');
        $stmt->execute([
            ':id' => $projectId,
            ':uid' => $leadUserId,
        ]);
        return (bool)$stmt->fetchColumn();
    }
}
