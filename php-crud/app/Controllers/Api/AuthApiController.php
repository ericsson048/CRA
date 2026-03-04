<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\LoginThrottle;
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

        $pdo = Database::connection();
        if (LoginThrottle::isBlocked($pdo, $email, $this->clientIp())) {
            $this->json(['message' => 'Trop de tentatives. Reessaye plus tard.'], 429);
        }

        $userModel = new UserModel($pdo);
        $user = $userModel->findByEmail($email);
        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            LoginThrottle::record($pdo, $email, $this->clientIp(), false);
            Audit::log('api_login_failed', 'user', $user !== null ? (int)$user['id'] : null, ['email' => $email]);
            $this->json(['message' => 'Identifiants invalides.'], 401);
        }
        if (!(bool)($user['is_active'] ?? false)) {
            LoginThrottle::record($pdo, $email, $this->clientIp(), false);
            Audit::log('api_login_disabled', 'user', (int)$user['id'], ['email' => $email]);
            $this->json(['message' => 'Compte desactive.'], 403);
        }

        LoginThrottle::record($pdo, $email, $this->clientIp(), true);
        LoginThrottle::clearFailures($pdo, $email, $this->clientIp());
        $userModel->recordSuccessfulLogin((int)$user['id']);

        Auth::login([
            'id' => (int)$user['id'],
            'name' => (string)$user['nom'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
            'team_id' => isset($user['team_id']) ? (int)$user['team_id'] : null,
            'team_name' => isset($user['team_name']) ? (string)$user['team_name'] : null,
            'is_active' => (bool)($user['is_active'] ?? false),
            'must_change_password' => (bool)($user['must_change_password'] ?? false),
        ]);
        Audit::log('api_login_success', 'user', (int)$user['id'], ['role' => (string)$user['role']]);

        $this->json([
            'message' => 'Connexion reussie.',
            'user' => Auth::user(),
        ]);
    }

    public function logout(): void
    {
        $sessionUser = Auth::user();
        if ($sessionUser !== null) {
            Audit::log('api_logout', 'user', (int)($sessionUser['id'] ?? 0));
        }
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
