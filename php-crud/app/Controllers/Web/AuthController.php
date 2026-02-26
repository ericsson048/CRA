<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
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
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                $errors[] = 'Email et mot de passe sont obligatoires.';
            }

            if (empty($errors)) {
                $userModel = new UserModel(Database::connection());
                $user = $userModel->findByEmail($email);
                if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
                    $errors[] = 'Identifiants invalides.';
                } else {
                    Auth::login([
                        'id' => (int)$user['id'],
                        'name' => (string)$user['nom'],
                        'email' => (string)$user['email'],
                        'role' => (string)$user['role'],
                    ]);
                    $this->redirect(((string)$user['role'] === 'developpeur') ? 'planning.php' : 'index.php');
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
        Auth::logout();
        $this->redirect('login.php');
    }
}
