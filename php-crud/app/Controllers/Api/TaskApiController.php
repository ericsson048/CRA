<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\ProjectModel;
use App\Models\ResourceModel;
use App\Models\TaskModel;
use App\Models\UserModel;

final class TaskApiController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $onlyUserId = Auth::hasRole(['developpeur']) ? (int)($user['id'] ?? 0) : null;
        $tasks = (new TaskModel(Database::connection()))->listWithRelations($onlyUserId);
        $this->json(['items' => $tasks]);
    }

    public function show(int $id): void
    {
        $this->requireAuth();
        $model = new TaskModel(Database::connection());
        $task = $model->findById($id);
        if ($task === null) {
            $this->json(['message' => 'Tache introuvable.'], 404);
        }
        if (!$this->canAccessTask($task)) {
            $this->json(['message' => 'Acces refuse.'], 403);
        }
        $this->json($task);
    }

    public function store(): void
    {
        $this->requireRole(['admin', 'gestionnaire']);
        $payload = $this->getJsonInput();
        $normalized = $this->normalizePayload($payload, true);
        if (!empty($normalized['errors'])) {
            $this->json(['message' => 'Validation echouee.', 'errors' => $normalized['errors']], 422);
        }

        $model = new TaskModel(Database::connection());
        $id = $model->create($normalized['data']);
        Audit::log('api_task_created', 'task', $id, ['titre' => $normalized['data']['titre']]);
        $this->json($model->findById($id), 201);
    }

    public function update(int $id): void
    {
        $this->requireAuth();
        $model = new TaskModel(Database::connection());
        $task = $model->findById($id);
        if ($task === null) {
            $this->json(['message' => 'Tache introuvable.'], 404);
        }

        if (Auth::hasRole(['developpeur'])) {
            $statusOnly = $this->getJsonInput();
            $status = trim((string)($statusOnly['statut'] ?? ''));
            if (!in_array($status, TaskModel::STATUS, true)) {
                $this->json(['message' => 'Statut invalide.'], 422);
            }
            if ((int)$task['assigned_user_id'] !== (int)(Auth::user()['id'] ?? 0)) {
                $this->json(['message' => 'Acces refuse.'], 403);
            }
            $model->updateStatus($id, $status);
            Audit::log('api_task_status_updated', 'task', $id, ['status' => $status]);
            $this->json($model->findById($id));
        }

        $this->requireRole(['admin', 'gestionnaire']);
        $payload = $this->getJsonInput();
        $normalized = $this->normalizePayload($payload, false);
        if (!empty($normalized['errors'])) {
            $this->json(['message' => 'Validation echouee.', 'errors' => $normalized['errors']], 422);
        }

        $model->update($id, $normalized['data']);
        if (isset($payload['statut']) && in_array((string)$payload['statut'], TaskModel::STATUS, true)) {
            $model->updateStatus($id, (string)$payload['statut']);
        }
        Audit::log('api_task_updated', 'task', $id);
        $this->json($model->findById($id));
    }

    public function destroy(int $id): void
    {
        $this->requireRole(['admin', 'gestionnaire']);
        $model = new TaskModel(Database::connection());
        if ($model->findById($id) === null) {
            $this->json(['message' => 'Tache introuvable.'], 404);
        }
        $model->delete($id);
        Audit::log('api_task_deleted', 'task', $id);
        $this->json(['message' => 'Tache supprimee.']);
    }

    private function canAccessTask(array $task): bool
    {
        if (Auth::hasRole(['admin', 'gestionnaire'])) {
            return true;
        }
        return Auth::hasRole(['developpeur']) && (int)$task['assigned_user_id'] === (int)(Auth::user()['id'] ?? 0);
    }

    private function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->json(['message' => 'Non authentifie.'], 401);
        }
    }

    /**
     * @param array<int, string> $roles
     */
    private function requireRole(array $roles): void
    {
        if (!Auth::check()) {
            $this->json(['message' => 'Non authentifie.'], 401);
        }
        if (!Auth::hasRole($roles)) {
            $this->json(['message' => 'Acces refuse.'], 403);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{data: array{titre: string, description: ?string, priorite: string, due_date: ?string, project_id: ?int, resource_id: ?int, assigned_user_id: int, statut?: string, created_by?: ?int}, errors: array<int, string>}
     */
    private function normalizePayload(array $payload, bool $isCreate): array
    {
        $title = trim((string)($payload['titre'] ?? ''));
        $description = trim((string)($payload['description'] ?? ''));
        $priority = trim((string)($payload['priorite'] ?? 'Moyenne'));
        $dueDate = trim((string)($payload['due_date'] ?? ''));
        $projectId = (int)($payload['project_id'] ?? 0);
        $resourceId = (int)($payload['resource_id'] ?? 0);
        $assignedUserId = (int)($payload['assigned_user_id'] ?? 0);

        $errors = [];
        if ($title === '') {
            $errors[] = 'titre requis';
        }
        if (!in_array($priority, TaskModel::PRIORITY, true)) {
            $errors[] = 'priorite invalide';
        }
        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $errors[] = 'due_date invalide';
        }
        if ($projectId <= 0) {
            $errors[] = 'project_id invalide';
        }

        $pdo = Database::connection();
        $userModel = new UserModel($pdo);
        $projectModel = new ProjectModel($pdo);
        $devExists = false;
        foreach ($userModel->developers() as $developer) {
            if ((int)$developer['id'] === $assignedUserId) {
                $devExists = true;
                break;
            }
        }
        if (!$devExists) {
            $errors[] = 'assigned_user_id invalide';
        }
        if ($projectId > 0 && $projectModel->findById($projectId) === null) {
            $errors[] = 'project_id invalide';
        }

        if ($resourceId > 0 && (new ResourceModel($pdo))->findById($resourceId) === null) {
            $errors[] = 'resource_id invalide';
        }

        $data = [
            'titre' => $title,
            'description' => $description !== '' ? $description : null,
            'priorite' => $priority,
            'due_date' => $dueDate !== '' ? $dueDate : null,
            'project_id' => $projectId > 0 ? $projectId : null,
            'resource_id' => $resourceId > 0 ? $resourceId : null,
            'assigned_user_id' => $assignedUserId,
        ];

        if ($isCreate) {
            $data['statut'] = 'A faire';
            $data['created_by'] = (int)(Auth::user()['id'] ?? 0) > 0 ? (int)Auth::user()['id'] : null;
        }

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }
}
