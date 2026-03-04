<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\AppConfig;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\NotificationService;
use App\Models\UserModel;

final class AccountController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }

        $userModel = new UserModel(Database::connection());
        $sessionUser = Auth::user() ?? [];
        $userId = (int)($sessionUser['id'] ?? 0);
        $user = $userModel->findAuthById($userId);
        if ($user === null) {
            Auth::logout();
            $this->redirect('login.php');
        }

        $errors = [];
        $notificationService = new NotificationService();

        if ($this->requestMethod() === 'POST') {
            $this->validateCsrf();

            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');
            $mustChangePassword = (bool)($user['must_change_password'] ?? false);

            if (!$mustChangePassword && !password_verify($currentPassword, (string)$user['password_hash'])) {
                $errors[] = 'Le mot de passe actuel est incorrect.';
            }
            if (strlen($newPassword) < 12) {
                $errors[] = 'Le nouveau mot de passe doit contenir au moins 12 caracteres.';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'La confirmation du mot de passe ne correspond pas.';
            }

            if (empty($errors)) {
                $userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT), false);
                $updatedUser = $userModel->findById($userId);
                if ($updatedUser !== null) {
                    Auth::login([
                        'id' => (int)$updatedUser['id'],
                        'name' => (string)$updatedUser['nom'],
                        'email' => (string)$updatedUser['email'],
                        'role' => (string)$updatedUser['role'],
                        'team_id' => isset($updatedUser['team_id']) ? (int)$updatedUser['team_id'] : null,
                        'team_name' => isset($updatedUser['team_name']) ? (string)$updatedUser['team_name'] : null,
                        'is_active' => (bool)($updatedUser['is_active'] ?? false),
                        'must_change_password' => false,
                    ]);
                }

                Audit::log('password_changed', 'user', $userId, ['forced' => $mustChangePassword]);
                $notificationService->notifyUser(
                    $userId,
                    'Mot de passe mis a jour',
                    'Ton mot de passe a ete modifie avec succes.',
                    'account.php',
                    'Confirmation de changement de mot de passe',
                    '<p>Bonjour ' . $this->escapeHtml((string)$user['nom']) . ',</p><p>Ton mot de passe a ete modifie avec succes.</p><p>Si tu n es pas a l origine de cette action, contacte immediatement un administrateur.</p><p>Acces: <a href="' . $this->escapeHtml(AppConfig::appUrl() . '/account.php') . '">' . $this->escapeHtml(AppConfig::appUrl() . '/account.php') . '</a></p>'
                );
                $this->redirect('account.php?password_changed=1');
            }
        }

        $this->render('account/index', [
            'sessionUser' => Auth::user(),
            'errors' => $errors,
            'passwordChanged' => isset($_GET['password_changed']),
            'mustChangePassword' => (bool)($user['must_change_password'] ?? false),
        ]);
    }
}
