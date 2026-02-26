<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\ProjectModel;
use App\Models\TeamModel;
use App\Models\UserModel;

final class ProjectController extends Controller
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
        $projectModel = new ProjectModel($pdo);
        $teamModel = new TeamModel($pdo);
        $userModel = new UserModel($pdo);
        $sessionUser = Auth::user() ?? [];
        $sessionUserId = (int)($sessionUser['id'] ?? 0);

        $errors = [];
        $nom = '';
        $description = '';
        $statut = 'Planifie';
        $startDate = '';
        $endDate = '';
        $teamId = '';
        $tlUserId = '';
        $tlaUserId = '';

        if ($this->requestMethod() === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));
            if ($action === 'create_project') {
                $nom = trim((string)($_POST['nom'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $statut = trim((string)($_POST['statut'] ?? 'Planifie'));
                $startDate = trim((string)($_POST['start_date'] ?? ''));
                $endDate = trim((string)($_POST['end_date'] ?? ''));
                $teamId = trim((string)($_POST['team_id'] ?? ''));
                $tlUserId = trim((string)($_POST['tl_user_id'] ?? ''));
                $tlaUserId = trim((string)($_POST['tla_user_id'] ?? ''));

                $normalizedTeamId = $teamId !== '' ? (int)$teamId : 0;
                $normalizedTlUserId = $tlUserId !== '' ? (int)$tlUserId : 0;
                $normalizedTlaUserId = $tlaUserId !== '' ? (int)$tlaUserId : null;

                if ($nom === '') {
                    $errors[] = 'Le nom du projet est obligatoire.';
                }
                if (!in_array($statut, ProjectModel::STATUS, true)) {
                    $errors[] = 'Statut du projet invalide.';
                }
                if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                    $errors[] = 'Format start_date invalide.';
                }
                if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $errors[] = 'Format end_date invalide.';
                }
                if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
                    $errors[] = 'La date de fin doit etre superieure a la date de debut.';
                }

                $team = $teamModel->findById($normalizedTeamId);
                if ($team === null) {
                    $errors[] = 'La team selectionnee est invalide.';
                }

                $tlUser = $userModel->findById($normalizedTlUserId);
                if ($tlUser === null || (string)$tlUser['role'] !== 'team_leader') {
                    $errors[] = 'Le Team Leader selectionne est invalide.';
                } elseif ($normalizedTeamId > 0 && (int)($tlUser['team_id'] ?? 0) !== $normalizedTeamId) {
                    $errors[] = 'Le Team Leader doit appartenir a la team choisie.';
                }

                if ($normalizedTlaUserId !== null) {
                    $tlaUser = $userModel->findById($normalizedTlaUserId);
                    if ($tlaUser === null || (string)$tlaUser['role'] !== 'team_leader_adjoint') {
                        $errors[] = 'Le Team Leader Adjoint selectionne est invalide.';
                    } elseif ($normalizedTeamId > 0 && (int)($tlaUser['team_id'] ?? 0) !== $normalizedTeamId) {
                        $errors[] = 'Le Team Leader Adjoint doit appartenir a la team choisie.';
                    }
                }
                if ($normalizedTlaUserId !== null && $normalizedTlUserId === $normalizedTlaUserId) {
                    $errors[] = 'Le TL et le TLA doivent etre differents.';
                }

                if (empty($errors)) {
                    $projectModel->create([
                        'nom' => $nom,
                        'description' => $description !== '' ? $description : null,
                        'statut' => $statut,
                        'start_date' => $startDate !== '' ? $startDate : null,
                        'end_date' => $endDate !== '' ? $endDate : null,
                        'team_id' => $normalizedTeamId,
                        'tl_user_id' => $normalizedTlUserId,
                        'tla_user_id' => $normalizedTlaUserId,
                        'assigned_by' => $sessionUserId,
                    ]);
                    $this->redirect('projects.php?created=1');
                }
            }
        }

        $leaders = $userModel->leaders();
        $teamLeaders = [];
        $teamLeaderAdjoints = [];
        foreach ($leaders as $leader) {
            if ((string)$leader['role'] === 'team_leader') {
                $teamLeaders[] = $leader;
            } elseif ((string)$leader['role'] === 'team_leader_adjoint') {
                $teamLeaderAdjoints[] = $leader;
            }
        }

        $this->render('projects/index', [
            'sessionUser' => $sessionUser,
            'created' => isset($_GET['created']),
            'errors' => $errors,
            'projects' => $projectModel->all(),
            'teams' => $teamModel->selectList(),
            'teamLeaders' => $teamLeaders,
            'teamLeaderAdjoints' => $teamLeaderAdjoints,
            'statusOptions' => ProjectModel::STATUS,
            'nom' => $nom,
            'description' => $description,
            'statut' => $statut,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'teamId' => $teamId,
            'tlUserId' => $tlUserId,
            'tlaUserId' => $tlaUserId,
        ]);
    }
}
