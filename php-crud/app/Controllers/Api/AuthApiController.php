<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\UserModel;

final class AuthApiController extends Controller
{
    public function login(): void
    {
        $payload = $this->getJsonInput();
        $email = trim((string)($payload['email'] ?? ''));
        $password = (string)($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->json(['message' => 'Email et mot de passe requis.'], 422);
        }

        $user = (new UserModel(Database::connection()))->findByEmail($email);
        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            $this->json(['message' => 'Identifiants invalides.'], 401);
        }

        Auth::login([
            'id' => (int)$user['id'],
            'name' => (string)$user['nom'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
            'team_id' => isset($user['team_id']) ? (int)$user['team_id'] : null,
            'team_name' => isset($user['team_name']) ? (string)$user['team_name'] : null,
        ]);

        $this->json([
            'message' => 'Connexion reussie.',
            'user' => Auth::user(),
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->json(['message' => 'Deconnexion reussie.']);
    }

    public function me(): void
    {
        if (!Auth::check()) {
            $this->json(['message' => 'Non authentifie.'], 401);
        }
        $this->json(['user' => Auth::user()]);
    }
}
