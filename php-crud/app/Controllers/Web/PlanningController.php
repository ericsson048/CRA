<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\ProjectModel;
use App\Models\ResourceModel;
use App\Models\TaskModel;
use App\Models\UserModel;

final class PlanningController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }
        if (!Auth::hasRole(['admin', 'gestionnaire', 'team_leader', 'team_leader_adjoint', 'developpeur'])) {
            http_response_code(403);
            echo 'Acces refuse.';
            return;
        }

        $pdo = Database::connection();
        $taskModel = new TaskModel($pdo);
        $projectModel = new ProjectModel($pdo);
        $resourceModel = new ResourceModel($pdo);
        $userModel = new UserModel($pdo);

        $sessionUser = Auth::user() ?? [];
        $sessionUserId = (int)($sessionUser['id'] ?? 0);
        $sessionTeamId = isset($sessionUser['team_id']) ? (int)$sessionUser['team_id'] : null;
        if ($sessionTeamId !== null && $sessionTeamId <= 0) {
            $sessionTeamId = null;
        }

        $isAdmin = Auth::hasRole(['admin']);
        $isManager = Auth::hasRole(['gestionnaire']);
        $isLead = Auth::hasRole(['team_leader', 'team_leader_adjoint']);
        $isDeveloper = Auth::hasRole(['developpeur']);

        if ($isLead && $sessionTeamId === null && $sessionUserId > 0) {
            $freshUser = $userModel->findById($sessionUserId);
            $sessionTeamId = isset($freshUser['team_id']) ? (int)$freshUser['team_id'] : null;
            if ($sessionTeamId !== null && $sessionTeamId <= 0) {
                $sessionTeamId = null;
            }
        }

        $canCreateTasks = $isAdmin || $isLead;
        $canUpdateAnyTask = $isAdmin;
        $canUpdateOwnTask = $isDeveloper;
        $canUpdateLeadScope = $isLead;

        $errors = [];

        $developers = $isLead ? $userModel->developers($sessionTeamId) : $userModel->developers();
        $projects = [];
        if ($isLead) {
            $projects = $projectModel->selectForLead($sessionUserId);
        } elseif ($isAdmin || $isManager) {
            $projects = $projectModel->selectForManager();
        }

        if ($this->requestMethod() === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));

            if ($action === 'create_task' && $canCreateTasks) {
                $title = trim((string)($_POST['title'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $priority = trim((string)($_POST['priority'] ?? 'Moyenne'));
                $dueDate = trim((string)($_POST['due_date'] ?? ''));
                $projectId = (int)($_POST['project_id'] ?? 0);
                $resourceId = (int)($_POST['resource_id'] ?? 0);
                $assignedUserId = (int)($_POST['assigned_user_id'] ?? 0);

                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }
                if ($projectId <= 0) {
                    $errors[] = 'Le projet est obligatoire.';
                }
                if (!in_array($priority, TaskModel::PRIORITY, true)) {
                    $errors[] = 'Priorite invalide.';
                }
                if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                    $errors[] = 'Format de date invalide.';
                }

                if ($projectId > 0 && $projectModel->findById($projectId) === null) {
                    $errors[] = 'Le projet assigne est invalide.';
                }
                if ($isLead && $projectId > 0 && !$projectModel->isManagedByLead($projectId, $sessionUserId)) {
                    $errors[] = 'Tu ne peux creer une tache que sur tes projets.';
                }

                $devExists = false;
                foreach ($developers as $developer) {
                    if ((int)$developer['id'] === $assignedUserId) {
                        $devExists = true;
                        break;
                    }
                }
                if (!$devExists) {
                    $errors[] = 'Le developpeur assigne est invalide.';
                }
                if ($resourceId > 0 && $resourceModel->findById($resourceId) === null) {
                    $errors[] = 'La ressource liee est invalide.';
                }

                if (empty($errors)) {
                    $taskModel->create([
                        'titre' => $title,
                        'description' => $description !== '' ? $description : null,
                        'statut' => 'A faire',
                        'priorite' => $priority,
                        'due_date' => $dueDate !== '' ? $dueDate : null,
                        'project_id' => $projectId > 0 ? $projectId : null,
                        'resource_id' => $resourceId > 0 ? $resourceId : null,
                        'assigned_user_id' => $assignedUserId,
                        'created_by' => $sessionUserId > 0 ? $sessionUserId : null,
                    ]);
                    $this->redirect('planning.php?created=1');
                }
            } elseif ($action === 'update_status') {
                $taskId = (int)($_POST['task_id'] ?? 0);
                $status = trim((string)($_POST['status'] ?? ''));

                if (!in_array($status, TaskModel::STATUS, true)) {
                    $errors[] = 'Statut invalide.';
                } else {
                    $task = $taskModel->findById($taskId);
                    if ($task === null) {
                        $errors[] = 'Tache introuvable.';
                    } else {
                        $isAssignedDev = $canUpdateOwnTask && (int)$task['assigned_user_id'] === $sessionUserId;
                        $isLeadScopeTask = false;
                        if ($canUpdateLeadScope) {
                            $taskProjectId = (int)($task['project_id'] ?? 0);
                            $taskAssignedTeamId = (int)($task['assigned_team_id'] ?? 0);
                            $sameTeam = $sessionTeamId !== null && $taskAssignedTeamId === $sessionTeamId;
                            $managedProject = $taskProjectId > 0 && $projectModel->isManagedByLead($taskProjectId, $sessionUserId);
                            $createdByLead = (int)($task['created_by'] ?? 0) === $sessionUserId;
                            $isLeadScopeTask = $managedProject || ($sameTeam && $createdByLead);
                        }

                        if (!($canUpdateAnyTask || $isAssignedDev || $isLeadScopeTask)) {
                            $errors[] = 'Tu ne peux pas modifier cette tache.';
                        } else {
                            $taskModel->updateStatus($taskId, $status);
                            $this->redirect('planning.php?updated=1');
                        }
                    }
                }
            }
        }

        $onlyUserId = $isDeveloper ? $sessionUserId : null;
        $scopeTeamId = $isLead ? $sessionTeamId : null;
        $scopeLeadUserId = $isLead ? $sessionUserId : null;
        $tasks = $taskModel->listWithRelations($onlyUserId, $scopeTeamId, $scopeLeadUserId);

        $stats = [
            'todo' => 0,
            'doing' => 0,
            'done' => 0,
        ];
        foreach ($tasks as $task) {
            if ($task['statut'] === 'A faire') {
                $stats['todo']++;
            } elseif ($task['statut'] === 'En cours') {
                $stats['doing']++;
            } else {
                $stats['done']++;
            }
        }

        $this->render('planning/index', [
            'created' => isset($_GET['created']),
            'updated' => isset($_GET['updated']),
            'errors' => $errors,
            'sessionUser' => $sessionUser,
            'canCreateTasks' => $canCreateTasks,
            'canUpdateAnyTask' => $canUpdateAnyTask,
            'canUpdateOwnTask' => $canUpdateOwnTask,
            'canUpdateLeadScope' => $canUpdateLeadScope,
            'statusOptions' => TaskModel::STATUS,
            'priorityOptions' => TaskModel::PRIORITY,
            'developers' => $developers,
            'projects' => $projects,
            'resources' => $resourceModel->selectList(),
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }
}
