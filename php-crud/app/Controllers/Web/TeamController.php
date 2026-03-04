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

final class TeamController extends Controller
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

        $pdo = Database::connection();
        $teamModel = new TeamModel($pdo);
        $userModel = new UserModel($pdo);
        $sessionUser = Auth::user() ?? [];

        $errors = [];
        $teamNom = '';
        $teamDescription = '';
        $tlUserId = '';
        $tlaUserId = '';
        $notificationService = new NotificationService();

        if ($this->requestMethod() === 'POST') {
            $this->validateCsrf();
            $action = trim((string)($_POST['action'] ?? ''));

            if ($action === 'create_team') {
                $teamNom = trim((string)($_POST['nom'] ?? ''));
                $teamDescription = trim((string)($_POST['description'] ?? ''));
                $tlUserId = trim((string)($_POST['tl_user_id'] ?? ''));
                $tlaUserId = trim((string)($_POST['tla_user_id'] ?? ''));

                $tlId = $tlUserId !== '' ? (int)$tlUserId : null;
                $tlaId = $tlaUserId !== '' ? (int)$tlaUserId : null;

                if ($teamNom === '') {
                    $errors[] = 'Le nom de la team est obligatoire.';
                } elseif ($teamModel->existsName($teamNom)) {
                    $errors[] = 'Ce nom de team existe deja.';
                }
                if ($tlId === null) {
                    $errors[] = 'Le Team Leader est obligatoire.';
                } else {
                    $tlUser = $userModel->findById($tlId);
                    if ($tlUser === null || (string)$tlUser['role'] !== 'team_leader') {
                        $errors[] = 'Le Team Leader selectionne est invalide.';
                    }
                }
                if ($tlaId !== null) {
                    $tlaUser = $userModel->findById($tlaId);
                    if ($tlaUser === null || (string)$tlaUser['role'] !== 'team_leader_adjoint') {
                        $errors[] = 'Le Team Leader Adjoint selectionne est invalide.';
                    }
                }
                if ($tlId !== null && $tlaId !== null && $tlId === $tlaId) {
                    $errors[] = 'Le TL et le TLA doivent etre differents.';
                }

                if (empty($errors)) {
                    $teamId = $teamModel->create([
                        'nom' => $teamNom,
                        'description' => $teamDescription !== '' ? $teamDescription : null,
                        'tl_user_id' => $tlId,
                        'tla_user_id' => $tlaId,
                    ]);
                    if ($tlId !== null) {
                        $userModel->setTeam($tlId, $teamId);
                    }
                    if ($tlaId !== null) {
                        $userModel->setTeam($tlaId, $teamId);
                    }
                    Audit::log('team_created', 'team', $teamId, ['nom' => $teamNom]);
                    $notificationService->notifyMany(
                        array_filter([(int)$tlId, (int)$tlaId]),
                        'Nouvelle team',
                        'Tu as ete rattache a la team ' . $teamNom . '.',
                        'teams.php',
                        'Affectation a une team',
                        '<p>Bonjour,</p><p>Tu as ete rattache a la team <strong>' . $this->escapeHtml($teamNom) . '</strong>.</p><p>Acces: <a href="' . $this->escapeHtml(AppConfig::appUrl() . '/teams.php') . '">' . $this->escapeHtml(AppConfig::appUrl() . '/teams.php') . '</a></p>'
                    );
                    $this->redirect('teams.php?created=1');
                }
            } elseif ($action === 'assign_member') {
                $teamId = (int)($_POST['team_id'] ?? 0);
                $userId = (int)($_POST['user_id'] ?? 0);

                $team = $teamModel->findById($teamId);
                $user = $userModel->findById($userId);
                if ($team === null) {
                    $errors[] = 'Team invalide.';
                }
                if ($user === null) {
                    $errors[] = 'Utilisateur invalide.';
                } elseif (!in_array((string)$user['role'], ['team_leader', 'team_leader_adjoint', 'developpeur'], true)) {
                    $errors[] = 'Seuls TL, TLA et developpeurs peuvent etre assignes a une team.';
                }

                if (empty($errors)) {
                    $userModel->setTeam($userId, $teamId);
                    if ((string)$user['role'] === 'team_leader') {
                        $teamModel->update($teamId, [
                            'nom' => (string)$team['nom'],
                            'description' => (string)($team['description'] ?? ''),
                            'tl_user_id' => $userId,
                            'tla_user_id' => isset($team['tla_user_id']) ? (int)$team['tla_user_id'] : null,
                        ]);
                    } elseif ((string)$user['role'] === 'team_leader_adjoint') {
                        $teamModel->update($teamId, [
                            'nom' => (string)$team['nom'],
                            'description' => (string)($team['description'] ?? ''),
                            'tl_user_id' => isset($team['tl_user_id']) ? (int)$team['tl_user_id'] : null,
                            'tla_user_id' => $userId,
                        ]);
                    }
                    Audit::log('team_member_assigned', 'team', $teamId, ['user_id' => $userId]);
                    $notificationService->notifyUser(
                        $userId,
                        'Affectation team',
                        'Tu as ete affecte a la team ' . (string)$team['nom'] . '.',
                        'teams.php',
                        'Nouvelle affectation team',
                        '<p>Bonjour ' . $this->escapeHtml((string)$user['nom']) . ',</p><p>Tu as ete affecte a la team <strong>' . $this->escapeHtml((string)$team['nom']) . '</strong>.</p><p>Acces: <a href="' . $this->escapeHtml(AppConfig::appUrl() . '/teams.php') . '">' . $this->escapeHtml(AppConfig::appUrl() . '/teams.php') . '</a></p>'
                    );
                    $this->redirect('teams.php?assigned=1');
                }
            }
        }

        $allUsers = $userModel->all();
        $assignableUsers = [];
        foreach ($allUsers as $user) {
            if ((bool)($user['is_active'] ?? false) && in_array((string)$user['role'], ['team_leader', 'team_leader_adjoint', 'developpeur'], true)) {
                $assignableUsers[] = $user;
            }
        }

        $this->render('teams/index', [
            'sessionUser' => $sessionUser,
            'errors' => $errors,
            'created' => isset($_GET['created']),
            'assigned' => isset($_GET['assigned']),
            'teams' => $teamModel->all(),
            'leaders' => $userModel->leaders(),
            'assignableUsers' => $assignableUsers,
            'teamNom' => $teamNom,
            'teamDescription' => $teamDescription,
            'tlUserId' => $tlUserId,
            'tlaUserId' => $tlaUserId,
        ]);
    }
}
