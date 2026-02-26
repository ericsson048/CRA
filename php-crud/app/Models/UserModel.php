<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class UserModel
{
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
        $stmt = $this->pdo->prepare('SELECT id, nom, email, password_hash, role, created_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom, email, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        return $item ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, nom, email, role, created_at FROM users ORDER BY id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function developers(): array
    {
        $stmt = $this->pdo->query("SELECT id, nom FROM users WHERE role = 'developpeur' ORDER BY nom ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array{nom:string, email:string, password_hash:string, role:string} $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (nom, email, password_hash, role) VALUES (:nom, :email, :password_hash, :role)');
        $stmt->execute([
            ':nom' => $data['nom'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role' => $data['role'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function canCreateRole(string $creatorRole, string $targetRole): bool
    {
        if ($creatorRole === 'admin') {
            return in_array($targetRole, ['developpeur', 'gestionnaire'], true);
        }
        if ($creatorRole === 'gestionnaire') {
            return $targetRole === 'developpeur';
        }
        return false;
    }
}
