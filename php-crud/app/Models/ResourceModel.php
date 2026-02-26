<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class ResourceModel
{
    public const ALLOWED_STATUS = ['Disponible', 'En maintenance', 'Indisponible'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, totalRows: int, totalPages: int, page: int}
     */
    public function paginate(string $search, int $page, int $perPage): array
    {
        $whereSql = '';
        $params = [];
        if ($search !== '') {
            $whereSql = ' WHERE nom LIKE :q OR categorie LIKE :q OR statut LIKE :q OR localisation LIKE :q';
            $params[':q'] = '%' . $search . '%';
        }

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM resources' . $whereSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalRows = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $listStmt = $this->pdo->prepare('SELECT id, nom, categorie, quantite, statut, localisation, created_at FROM resources' . $whereSql . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $listStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();

        return [
            'items' => $listStmt->fetchAll(PDO::FETCH_ASSOC),
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'page' => $page,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $search = ''): array
    {
        $whereSql = '';
        $params = [];
        if ($search !== '') {
            $whereSql = ' WHERE nom LIKE :q OR categorie LIKE :q OR statut LIKE :q OR localisation LIKE :q';
            $params[':q'] = '%' . $search . '%';
        }

        $stmt = $this->pdo->prepare('SELECT id, nom, categorie, quantite, statut, localisation, created_at FROM resources' . $whereSql . ' ORDER BY id DESC');
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom, categorie, quantite, statut, localisation, created_at FROM resources WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    /**
     * @param array{nom: string, categorie: string, quantite: int, statut: string, localisation: string} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO resources (nom, categorie, quantite, statut, localisation) VALUES (:nom, :categorie, :quantite, :statut, :localisation)');
        $stmt->execute([
            ':nom' => $data['nom'],
            ':categorie' => $data['categorie'],
            ':quantite' => $data['quantite'],
            ':statut' => $data['statut'],
            ':localisation' => $data['localisation'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array{nom: string, categorie: string, quantite: int, statut: string, localisation: string} $data
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare('UPDATE resources SET nom = :nom, categorie = :categorie, quantite = :quantite, statut = :statut, localisation = :localisation WHERE id = :id');
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':categorie' => $data['categorie'],
            ':quantite' => $data['quantite'],
            ':statut' => $data['statut'],
            ':localisation' => $data['localisation'],
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM resources WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function countByCategoryPrefix(string $prefix): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM resources WHERE categorie LIKE :prefix');
        $stmt->execute([':prefix' => $prefix . '%']);
        return (int)$stmt->fetchColumn();
    }

    /**
     * @return array<int, array{id:int, nom:string, categorie:string}>
     */
    public function selectList(): array
    {
        $stmt = $this->pdo->query('SELECT id, nom, categorie FROM resources ORDER BY categorie ASC, nom ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
