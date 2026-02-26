<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
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
        if (!Auth::hasRole(['admin', 'gestionnaire', 'developpeur'])) {
            http_response_code(403);
            echo 'Acces refuse.';
            return;
        }

        $pdo = Database::connection();
        $taskModel = new TaskModel($pdo);
        $resourceModel = new ResourceModel($pdo);
        $userModel = new UserModel($pdo);

        $sessionUser = Auth::user();
        $canCreateTasks = Auth::hasRole(['admin', 'gestionnaire']);
        $canUpdateAnyTask = Auth::hasRole(['admin']);
        $canUpdateOwnTask = Auth::hasRole(['developpeur']);

        $errors = [];

        if ($this->requestMethod() === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));

            if ($action === 'create_task' && $canCreateTasks) {
                $title = trim((string)($_POST['title'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $priority = trim((string)($_POST['priority'] ?? 'Moyenne'));
                $dueDate = trim((string)($_POST['due_date'] ?? ''));
                $resourceId = (int)($_POST['resource_id'] ?? 0);
                $assignedUserId = (int)($_POST['assigned_user_id'] ?? 0);

                if ($title === '') {
                    $errors[] = 'Le titre est obligatoire.';
                }
                if (!in_array($priority, TaskModel::PRIORITY, true)) {
                    $errors[] = 'Priorite invalide.';
                }
                if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                    $errors[] = 'Format de date invalide.';
                }
                $devExists = false;
                foreach ($userModel->developers() as $developer) {
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
                        'resource_id' => $resourceId > 0 ? $resourceId : null,
                        'assigned_user_id' => $assignedUserId,
                        'created_by' => isset($sessionUser['id']) ? (int)$sessionUser['id'] : null,
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
                        $isAssignedDev = $canUpdateOwnTask && (int)$task['assigned_user_id'] === (int)($sessionUser['id'] ?? 0);
                        if (!($canUpdateAnyTask || $isAssignedDev)) {
                            $errors[] = 'Tu ne peux pas modifier cette tache.';
                        } else {
                            $taskModel->updateStatus($taskId, $status);
                            $this->redirect('planning.php?updated=1');
                        }
                    }
                }
            }
        }

        $onlyUserId = Auth::hasRole(['developpeur']) ? (int)($sessionUser['id'] ?? 0) : null;
        $tasks = $taskModel->listWithRelations($onlyUserId);

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
            'statusOptions' => TaskModel::STATUS,
            'priorityOptions' => TaskModel::PRIORITY,
            'developers' => $userModel->developers(),
            'resources' => $resourceModel->selectList(),
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }
}
