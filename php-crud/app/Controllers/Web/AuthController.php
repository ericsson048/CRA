<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\LoginThrottle;
use App\Models\UserModel;

final class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('index.php');
        }

        $errors = [];
        $email = '';

        if ($this->requestMethod() === 'POST') {
            $this->validateCsrf();
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                $errors[] = 'Email et mot de passe sont obligatoires.';
            }

            if (empty($errors)) {
                $pdo = Database::connection();
                if (LoginThrottle::isBlocked($pdo, $email, $this->clientIp())) {
                    $errors[] = 'Trop de tentatives. Reessaye plus tard.';
                    Audit::log('login_blocked', 'user', null, ['email' => $email]);
                }

                $userModel = new UserModel($pdo);
                $user = $userModel->findByEmail($email);
                if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
                    LoginThrottle::record($pdo, $email, $this->clientIp(), false);
                    Audit::log('login_failed', 'user', $user !== null ? (int)$user['id'] : null, ['email' => $email]);
                    $errors[] = 'Identifiants invalides.';
                } elseif (!(bool)($user['is_active'] ?? false)) {
                    LoginThrottle::record($pdo, $email, $this->clientIp(), false);
                    Audit::log('login_disabled', 'user', (int)$user['id'], ['email' => $email]);
                    $errors[] = 'Compte desactive.';
                } else {
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
                    Audit::log('login_success', 'user', (int)$user['id'], ['role' => (string)$user['role']]);

                    if ((bool)($user['must_change_password'] ?? false)) {
                        $this->redirect('account.php');
                    }

                    $role = (string)$user['role'];
                    if (in_array($role, ['developpeur', 'team_leader', 'team_leader_adjoint'], true)) {
                        $this->redirect('planning.php');
                    }
                    $this->redirect('index.php');
                }
            }
        }

        $this->render('auth/login', [
            'email' => $email,
            'errors' => $errors,
        ]);
    }

    public function logout(): void
    {
        if ($this->requestMethod() !== 'POST') {
            http_response_code(405);
            echo 'Methode non autorisee.';
            return;
        }

        $this->validateCsrf();
        $sessionUser = Auth::user();
        if ($sessionUser !== null) {
            Audit::log('logout', 'user', (int)($sessionUser['id'] ?? 0));
        }
        Auth::logout();
        $this->redirect('login.php');
    }
}
