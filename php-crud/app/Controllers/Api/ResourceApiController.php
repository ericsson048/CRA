<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\ResourceModel;

final class ResourceApiController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $search = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));

        $pagination = (new ResourceModel(Database::connection()))->paginate($search, $page, $perPage);
        $this->json([
            'items' => $pagination['items'],
            'meta' => [
                'page' => $pagination['page'],
                'per_page' => $perPage,
                'total_rows' => $pagination['totalRows'],
                'total_pages' => $pagination['totalPages'],
            ],
        ]);
    }

    public function show(int $id): void
    {
        $this->requireAuth();
        $item = (new ResourceModel(Database::connection()))->findById($id);
        if ($item === null) {
            $this->json(['message' => 'Ressource introuvable.'], 404);
        }
        $this->json($item);
    }

    public function store(): void
    {
        $this->requireRole(['admin', 'gestionnaire']);
        $payload = $this->getJsonInput();
        $normalized = $this->normalizePayload($payload);
        if (!empty($normalized['errors'])) {
            $this->json(['message' => 'Validation echouee.', 'errors' => $normalized['errors']], 422);
        }

        $model = new ResourceModel(Database::connection());
        $id = $model->create($normalized['data']);
        $this->json($model->findById($id), 201);
    }

    public function update(int $id): void
    {
        $this->requireRole(['admin', 'gestionnaire']);
        $model = new ResourceModel(Database::connection());
        if ($model->findById($id) === null) {
            $this->json(['message' => 'Ressource introuvable.'], 404);
        }

        $payload = $this->getJsonInput();
        $normalized = $this->normalizePayload($payload);
        if (!empty($normalized['errors'])) {
            $this->json(['message' => 'Validation echouee.', 'errors' => $normalized['errors']], 422);
        }

        $model->update($id, $normalized['data']);
        $this->json($model->findById($id));
    }

    public function destroy(int $id): void
    {
        $this->requireRole(['admin', 'gestionnaire']);
        $model = new ResourceModel(Database::connection());
        if ($model->findById($id) === null) {
            $this->json(['message' => 'Ressource introuvable.'], 404);
        }
        $model->delete($id);
        $this->json(['message' => 'Ressource supprimee.']);
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
     * @return array{data: array{nom: string, categorie: string, quantite: int, statut: string, localisation: string}, errors: array<int, string>}
     */
    private function normalizePayload(array $payload): array
    {
        $nom = trim((string)($payload['nom'] ?? ''));
        $categorie = trim((string)($payload['categorie'] ?? ''));
        $quantite = filter_var($payload['quantite'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $statut = trim((string)($payload['statut'] ?? ''));
        $localisation = trim((string)($payload['localisation'] ?? ''));

        $errors = [];
        if ($nom === '') {
            $errors[] = 'nom requis';
        }
        if ($categorie === '') {
            $errors[] = 'categorie requise';
        }
        if ($quantite === false) {
            $errors[] = 'quantite invalide';
            $quantite = 0;
        }
        if (!in_array($statut, ResourceModel::ALLOWED_STATUS, true)) {
            $errors[] = 'statut invalide';
        }
        if ($localisation === '') {
            $errors[] = 'localisation requise';
        }

        return [
            'data' => [
                'nom' => $nom,
                'categorie' => $categorie,
                'quantite' => (int)$quantite,
                'statut' => $statut,
                'localisation' => $localisation,
            ],
            'errors' => $errors,
        ];
    }
}
