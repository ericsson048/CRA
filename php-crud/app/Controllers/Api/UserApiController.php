<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\UserModel;

final class UserApiController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['admin', 'gestionnaire']);
        $this->json(['items' => (new UserModel(Database::connection()))->all()]);
    }

    public function store(): void
    {
        $this->requireRole(['admin', 'gestionnaire']);
        $sessionUser = Auth::user();
        $creatorRole = (string)($sessionUser['role'] ?? '');

        $payload = $this->getJsonInput();
        $nom = trim((string)($payload['nom'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $password = (string)($payload['password'] ?? '');
        $role = trim((string)($payload['role'] ?? 'developpeur'));

        $errors = [];
        if ($nom === '') {
            $errors[] = 'nom requis';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email invalide';
        }
        if (strlen($password) < 8) {
            $errors[] = 'password min 8 caracteres';
        }

        $userModel = new UserModel(Database::connection());
        if (!$userModel->canCreateRole($creatorRole, $role)) {
            $errors[] = 'role interdit pour ce createur';
        }
        if ($userModel->findByEmail($email) !== null) {
            $errors[] = 'email deja utilise';
        }

        if (!empty($errors)) {
            $this->json(['message' => 'Validation echouee.', 'errors' => $errors], 422);
        }

        $id = $userModel->create([
            'nom' => $nom,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        $this->json($userModel->findById($id), 201);
    }

    /**
     * @param array<int, string> $roles
     */
    private function requireRole(array $roles): void
    {
        if (!Auth::check()) {
            $this->json(['message' => 'Non authentifie.'], 401);
        }
        if (!Auth::hasRole($roles)) {
            $this->json(['message' => 'Acces refuse.'], 403);
        }
    }
}
