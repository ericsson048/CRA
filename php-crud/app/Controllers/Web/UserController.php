<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\UserModel;

final class UserController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }
        if (!Auth::hasRole(['admin', 'gestionnaire'])) {
            http_response_code(403);
            echo 'Acces refuse.';
            return;
        }

        $userModel = new UserModel(Database::connection());
        $creator = Auth::user();
        $creatorRole = (string)($creator['role'] ?? '');

        $allowedRoles = ['developpeur'];
        if ($creatorRole === 'admin') {
            $allowedRoles[] = 'gestionnaire';
        }

        $errors = [];
        $nom = '';
        $email = '';
        $role = 'developpeur';

        if ($this->requestMethod() === 'POST') {
            $nom = trim((string)($_POST['nom'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $role = trim((string)($_POST['role'] ?? 'developpeur'));

            if ($nom === '') {
                $errors[] = 'Le nom est obligatoire.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email invalide.';
            }
            if (strlen($password) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
            }
            if (!in_array($role, $allowedRoles, true)) {
                $errors[] = 'Role invalide pour ton profil.';
            }
            if (!$userModel->canCreateRole($creatorRole, $role)) {
                $errors[] = 'Tu ne peux pas creer ce role.';
            }

            if (empty($errors)) {
                if ($userModel->findByEmail($email) !== null) {
                    $errors[] = 'Cet email existe deja.';
                } else {
                    $userModel->create([
                        'nom' => $nom,
                        'email' => $email,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                    ]);
                    $this->redirect('register.php?created=1');
                }
            }
        }

        $this->render('users/index', [
            'created' => isset($_GET['created']),
            'errors' => $errors,
            'nom' => $nom,
            'email' => $email,
            'role' => $role,
            'allowedRoles' => $allowedRoles,
            'users' => $userModel->all(),
            'sessionUser' => Auth::user(),
        ]);
    }
}
