<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\AppConfig;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\NotificationService;
use App\Models\TeamModel;
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
        $teamModel = new TeamModel(Database::connection());
        $creator = Auth::user();
        $creatorRole = (string)($creator['role'] ?? '');

        $allowedRoles = ['team_leader', 'team_leader_adjoint', 'developpeur'];
        if ($creatorRole === 'admin') {
            $allowedRoles[] = 'gestionnaire';
        }

        $errors = [];
        $nom = '';
        $email = '';
        $role = 'team_leader';
        $teamId = '';
        $notificationService = new NotificationService();

        if ($this->requestMethod() === 'POST') {
            $this->validateCsrf();
            $action = trim((string)($_POST['action'] ?? 'create_user'));

            if ($action === 'create_user') {
                $nom = trim((string)($_POST['nom'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                $role = trim((string)($_POST['role'] ?? 'developpeur'));
                $teamId = trim((string)($_POST['team_id'] ?? ''));
                $normalizedTeamId = $teamId !== '' ? (int)$teamId : null;

                if ($nom === '') {
                    $errors[] = 'Le nom est obligatoire.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email invalide.';
                }
                if (strlen($password) < 12) {
                    $errors[] = 'Le mot de passe doit contenir au moins 12 caracteres.';
                }
                if (!in_array($role, $allowedRoles, true)) {
                    $errors[] = 'Role invalide pour ton profil.';
                }
                if (!$userModel->canCreateRole($creatorRole, $role)) {
                    $errors[] = 'Tu ne peux pas creer ce role.';
                }
                if (in_array($role, ['team_leader', 'team_leader_adjoint', 'developpeur'], true)) {
                    if ($normalizedTeamId === null || $teamModel->findById($normalizedTeamId) === null) {
                        $errors[] = 'Une team valide est obligatoire pour ce role.';
                    }
                }

                if (empty($errors)) {
                    if ($userModel->findByEmail($email) !== null) {
                        $errors[] = 'Cet email existe deja.';
                    } else {
                        $userId = $userModel->create([
                            'nom' => $nom,
                            'email' => $email,
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'role' => $role,
                            'team_id' => $normalizedTeamId,
                            'is_active' => true,
                            'must_change_password' => true,
                        ]);
                        Audit::log('user_created', 'user', $userId, ['role' => $role]);
                        $notificationService->notifyUser(
                            $userId,
                            'Compte cree',
                            'Ton compte a ete cree. Connecte-toi et change ton mot de passe.',
                            'account.php',
                            'Bienvenue sur ' . AppConfig::appName(),
                            '<p>Bonjour ' . $this->escapeHtml($nom) . ',</p><p>Ton compte a ete cree sur ' . $this->escapeHtml(AppConfig::appName()) . '.</p><p>Email: <strong>' . $this->escapeHtml($email) . '</strong><br>Mot de passe temporaire: <strong>' . $this->escapeHtml($password) . '</strong></p><p>Connecte-toi ici: <a href="' . $this->escapeHtml(AppConfig::appUrl() . '/login.php') . '">' . $this->escapeHtml(AppConfig::appUrl() . '/login.php') . '</a></p><p>Le changement du mot de passe sera obligatoire a la premiere connexion.</p>'
                        );
                        $this->redirect('register.php?created=1');
                    }
                }
            } elseif ($action === 'toggle_active') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $enable = (int)($_POST['enable'] ?? 0) === 1;
                $targetUser = $userModel->findById($userId);

                if ($targetUser === null) {
                    $errors[] = 'Utilisateur introuvable.';
                } elseif ((int)($creator['id'] ?? 0) === $userId && !$enable) {
                    $errors[] = 'Tu ne peux pas desactiver ton propre compte.';
                } else {
                    $userModel->setActive($userId, $enable);
                    Audit::log($enable ? 'user_activated' : 'user_deactivated', 'user', $userId);
                    if ($targetUser !== null) {
                        $notificationService->notifyUser(
                            $userId,
                            $enable ? 'Compte active' : 'Compte desactive',
                            $enable ? 'Ton acces a l application a ete reactive.' : 'Ton acces a l application a ete suspendu.',
                            'account.php',
                            $enable ? 'Compte reactive' : 'Compte suspendu',
                            '<p>Bonjour ' . $this->escapeHtml((string)$targetUser['nom']) . ',</p><p>' . ($enable ? 'Ton compte a ete reactive.' : 'Ton compte a ete suspendu.') . '</p>'
                        );
                    }
                    $this->redirect('register.php?' . ($enable ? 'activated=1' : 'deactivated=1'));
                }
            } elseif ($action === 'reset_password') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $newPassword = (string)($_POST['reset_password'] ?? '');
                $targetUser = $userModel->findById($userId);

                if ($targetUser === null) {
                    $errors[] = 'Utilisateur introuvable.';
                } elseif (strlen($newPassword) < 12) {
                    $errors[] = 'Le mot de passe temporaire doit contenir au moins 12 caracteres.';
                } else {
                    $userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT), true);
                    Audit::log('password_reset', 'user', $userId);
                    $notificationService->notifyUser(
                        $userId,
                        'Mot de passe reinitialise',
                        'Un administrateur a reinitialise ton mot de passe. Le changement sera obligatoire a la prochaine connexion.',
                        'account.php',
                        'Reinitialisation de mot de passe',
                        '<p>Bonjour ' . $this->escapeHtml((string)$targetUser['nom']) . ',</p><p>Ton mot de passe a ete reinitialise.</p><p>Mot de passe temporaire: <strong>' . $this->escapeHtml($newPassword) . '</strong></p><p>Connecte-toi ici: <a href="' . $this->escapeHtml(AppConfig::appUrl() . '/login.php') . '">' . $this->escapeHtml(AppConfig::appUrl() . '/login.php') . '</a></p>'
                    );
                    $this->redirect('register.php?password_reset=1');
                }
            }
        }

        $this->render('users/index', [
            'created' => isset($_GET['created']),
            'activated' => isset($_GET['activated']),
            'deactivated' => isset($_GET['deactivated']),
            'passwordReset' => isset($_GET['password_reset']),
            'errors' => $errors,
            'nom' => $nom,
            'email' => $email,
            'role' => $role,
            'teamId' => $teamId,
            'allowedRoles' => $allowedRoles,
            'users' => $userModel->all(),
            'teams' => $teamModel->selectList(),
            'sessionUser' => Auth::user(),
        ]);
    }
}
