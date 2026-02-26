<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class TaskModel
{
    public const STATUS = ['A faire', 'En cours', 'Terminee'];
    public const PRIORITY = ['Basse', 'Moyenne', 'Haute'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listWithRelations(?int $onlyAssignedUserId = null, ?int $teamId = null, ?int $leadUserId = null): array
    {
        $sql = '
            SELECT
                t.id,
                t.titre,
                t.description,
                t.statut,
                t.priorite,
                t.due_date,
                t.project_id,
                t.resource_id,
                t.assigned_user_id,
                t.created_by,
                t.created_at,
                u.nom AS assigned_name,
                u.team_id AS assigned_team_id,
                r.nom AS resource_name,
                p.nom AS project_name,
                p.team_id AS project_team_id,
                c.nom AS created_by_name
            FROM planning_tasks t
            INNER JOIN users u ON u.id = t.assigned_user_id
            LEFT JOIN resources r ON r.id = t.resource_id
            LEFT JOIN projects p ON p.id = t.project_id
            LEFT JOIN users c ON c.id = t.created_by
        ';
        $params = [];
        $where = [];

        if ($onlyAssignedUserId !== null) {
            $where[] = 't.assigned_user_id = :user_id';
            $params[':user_id'] = $onlyAssignedUserId;
        }
        if ($teamId !== null) {
            $where[] = 'u.team_id = :team_id';
            $params[':team_id'] = $teamId;
        }
        if ($leadUserId !== null) {
            $where[] = '(p.tl_user_id = :lead_id OR p.tla_user_id = :lead_id OR t.created_by = :lead_id)';
            $params[':lead_id'] = $leadUserId;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY (t.due_date IS NULL), t.due_date ASC, t.id DESC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                t.id,
                t.titre,
                t.description,
                t.statut,
                t.priorite,
                t.due_date,
                t.project_id,
                t.resource_id,
                t.assigned_user_id,
                t.created_by,
                t.created_at,
                u.nom AS assigned_name,
                u.team_id AS assigned_team_id,
                r.nom AS resource_name,
                p.nom AS project_name
            FROM planning_tasks t
            INNER JOIN users u ON u.id = t.assigned_user_id
            LEFT JOIN resources r ON r.id = t.resource_id
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    /**
     * @param array{titre:string, description:?string, statut:string, priorite:string, due_date:?string, project_id:?int, resource_id:?int, assigned_user_id:int, created_by:?int} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO planning_tasks (titre, description, statut, priorite, due_date, project_id, resource_id, assigned_user_id, created_by)
            VALUES (:titre, :description, :statut, :priorite, :due_date, :project_id, :resource_id, :assigned_user_id, :created_by)
        ');
        $stmt->execute([
            ':titre' => $data['titre'],
            ':description' => $data['description'],
            ':statut' => $data['statut'],
            ':priorite' => $data['priorite'],
            ':due_date' => $data['due_date'],
            ':project_id' => $data['project_id'],
            ':resource_id' => $data['resource_id'],
            ':assigned_user_id' => $data['assigned_user_id'],
            ':created_by' => $data['created_by'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE planning_tasks SET statut = :statut WHERE id = :id');
        return $stmt->execute([
            ':statut' => $status,
            ':id' => $id,
        ]);
    }

    /**
     * @param array{titre:string, description:?string, priorite:string, due_date:?string, project_id:?int, resource_id:?int, assigned_user_id:int} $data
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE planning_tasks
            SET titre = :titre,
                description = :description,
                priorite = :priorite,
                due_date = :due_date,
                project_id = :project_id,
                resource_id = :resource_id,
                assigned_user_id = :assigned_user_id
            WHERE id = :id
        ');
        return $stmt->execute([
            ':titre' => $data['titre'],
            ':description' => $data['description'],
            ':priorite' => $data['priorite'],
            ':due_date' => $data['due_date'],
            ':project_id' => $data['project_id'],
            ':resource_id' => $data['resource_id'],
            ':assigned_user_id' => $data['assigned_user_id'],
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM planning_tasks WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function countOpen(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM planning_tasks WHERE statut <> 'Terminee'");
        return (int)$stmt->fetchColumn();
    }
}
