<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\ResourceModel;
use App\Models\TaskModel;

final class ResourceController extends Controller
{
    /** @var array<int, string> */
    private array $categorySuggestions = [
        'RH - Developpeur',
        'RH - Designer',
        'RH - Chef de projet',
        'RH - Support',
        'RM - Informatique',
        'RM - Mobilier',
        'RM - Reseau',
        'RM - Vehicule',
    ];

    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }

        $resourceModel = new ResourceModel(Database::connection());
        $taskModel = new TaskModel(Database::connection());

        $search = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 8;
        $pagination = $resourceModel->paginate($search, $page, $perPage);

        $this->render('resources/index', [
            'sessionUser' => Auth::user(),
            'canManage' => Auth::hasRole(['admin', 'gestionnaire']),
            'search' => $search,
            'resources' => $pagination['items'],
            'page' => $pagination['page'],
            'totalRows' => $pagination['totalRows'],
            'totalPages' => $pagination['totalPages'],
            'rhCount' => $resourceModel->countByCategoryPrefix('RH - '),
            'rmCount' => $resourceModel->countByCategoryPrefix('RM - '),
            'planningOpenCount' => $taskModel->countOpen(),
            'created' => isset($_GET['created']),
            'updated' => isset($_GET['updated']),
            'deleted' => isset($_GET['deleted']),
        ]);
    }

    public function create(): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }
        if (!Auth::hasRole(['admin', 'gestionnaire'])) {
            http_response_code(403);
            echo 'Acces refuse.';
            return;
        }

        $resourceModel = new ResourceModel(Database::connection());
        $errors = [];
        $data = [
            'nom' => '',
            'categorie' => '',
            'quantite' => 0,
            'statut' => 'Disponible',
            'localisation' => '',
        ];

        if ($this->requestMethod() === 'POST') {
            $normalized = $this->normalizeResourceInput($_POST);
            $data = $normalized['data'];
            $errors = $normalized['errors'];
            if (empty($errors)) {
                $resourceModel->create($data);
                $this->redirect('index.php?created=1');
            }
        }

        $this->render('resources/create', [
            'sessionUser' => Auth::user(),
            'errors' => $errors,
            'data' => $data,
            'allowedStatuts' => ResourceModel::ALLOWED_STATUS,
            'categorySuggestions' => $this->categorySuggestions,
        ]);
    }

    public function edit(int $id): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }
        if (!Auth::hasRole(['admin', 'gestionnaire'])) {
            http_response_code(403);
            echo 'Acces refuse.';
            return;
        }

        $resourceModel = new ResourceModel(Database::connection());
        $resource = $resourceModel->findById($id);
        if ($resource === null) {
            $this->redirect('index.php');
        }

        $errors = [];
        $data = [
            'nom' => (string)$resource['nom'],
            'categorie' => (string)$resource['categorie'],
            'quantite' => (int)$resource['quantite'],
            'statut' => (string)$resource['statut'],
            'localisation' => (string)$resource['localisation'],
        ];

        if ($this->requestMethod() === 'POST') {
            $normalized = $this->normalizeResourceInput($_POST);
            $data = $normalized['data'];
            $errors = $normalized['errors'];
            if (empty($errors)) {
                $resourceModel->update($id, $data);
                $this->redirect('index.php?updated=1');
            }
        }

        $this->render('resources/edit', [
            'sessionUser' => Auth::user(),
            'errors' => $errors,
            'id' => $id,
            'data' => $data,
            'allowedStatuts' => ResourceModel::ALLOWED_STATUS,
            'categorySuggestions' => $this->categorySuggestions,
        ]);
    }

    public function delete(int $id): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }
        if (!Auth::hasRole(['admin', 'gestionnaire'])) {
            http_response_code(403);
            echo 'Acces refuse.';
            return;
        }

        $resourceModel = new ResourceModel(Database::connection());
        $resource = $resourceModel->findById($id);
        if ($resource === null) {
            $this->redirect('index.php');
        }

        if ($this->requestMethod() === 'POST' && (string)($_POST['action'] ?? '') === 'delete') {
            $resourceModel->delete($id);
            $this->redirect('index.php?deleted=1');
        }

        $this->render('resources/delete', [
            'sessionUser' => Auth::user(),
            'resource' => $resource,
        ]);
    }

    public function export(): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }

        $format = strtolower(trim((string)($_GET['format'] ?? '')));
        $search = trim((string)($_GET['q'] ?? ''));
        if (!in_array($format, ['excel', 'pdf'], true)) {
            http_response_code(400);
            echo 'Format non supporte. Utilise: excel ou pdf.';
            return;
        }

        $resources = (new ResourceModel(Database::connection()))->all($search);
        if ($format === 'excel') {
            $this->exportExcel($resources);
            return;
        }
        $this->exportPdf($resources, $search);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{data: array{nom: string, categorie: string, quantite: int, statut: string, localisation: string}, errors: array<int, string>}
     */
    private function normalizeResourceInput(array $input): array
    {
        $nom = trim((string)($input['nom'] ?? ''));
        $categorie = trim((string)($input['categorie'] ?? ''));
        $quantite = filter_var($input['quantite'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $statut = trim((string)($input['statut'] ?? ''));
        $localisation = trim((string)($input['localisation'] ?? ''));

        $errors = [];
        if ($nom === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if ($categorie === '') {
            $errors[] = 'La categorie est obligatoire.';
        }
        if ($quantite === false) {
            $errors[] = 'La quantite doit etre un nombre entier positif ou nul.';
            $quantite = 0;
        }
        if (!in_array($statut, ResourceModel::ALLOWED_STATUS, true)) {
            $errors[] = 'Le statut selectionne est invalide.';
        }
        if ($localisation === '') {
            $errors[] = 'La localisation est obligatoire.';
        }

        return [
            'data' => [
                'nom' => $nom,
                'categorie' => $categorie,
                'quantite' => (int)$quantite,
                'statut' => $statut !== '' ? $statut : 'Disponible',
                'localisation' => $localisation,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $resources
     */
    private function exportExcel(array $resources): void
    {
        $fileName = 'ressources_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $fileName);
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, ['ID', 'Nom', 'Categorie', 'Quantite', 'Statut', 'Localisation', 'Date creation'], ';');
        foreach ($resources as $resource) {
            fputcsv($out, [
                (int)$resource['id'],
                (string)$resource['nom'],
                (string)$resource['categorie'],
                (int)$resource['quantite'],
                (string)$resource['statut'],
                (string)$resource['localisation'],
                (string)$resource['created_at'],
            ], ';');
        }
        fclose($out);
    }

    /**
     * @param array<int, array<string, mixed>> $resources
     */
    private function exportPdf(array $resources, string $search): void
    {
        $lines = [
            'Export des ressources',
            'Date: ' . date('Y-m-d H:i:s'),
            'Recherche: ' . ($search !== '' ? $search : 'Aucune'),
            str_repeat('-', 120),
            'ID | Nom | Categorie | Quantite | Statut | Localisation | Date creation',
        ];

        if (empty($resources)) {
            $lines[] = 'Aucune ressource trouvee.';
        } else {
            foreach ($resources as $resource) {
                $lines[] = sprintf(
                    '%d | %s | %s | %d | %s | %s | %s',
                    (int)$resource['id'],
                    $this->cleanPdfText((string)$resource['nom']),
                    $this->cleanPdfText((string)$resource['categorie']),
                    (int)$resource['quantite'],
                    $this->cleanPdfText((string)$resource['statut']),
                    $this->cleanPdfText((string)$resource['localisation']),
                    $this->cleanPdfText((string)$resource['created_at'])
                );
            }
        }

        $this->streamSimplePdf($lines, 'ressources_' . date('Ymd_His') . '.pdf');
    }

    private function cleanPdfText(string $text): string
    {
        $text = str_replace('|', '/', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }

    private function pdfEscape(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', ' ', ' '],
            $text
        );
    }

    /**
     * @param array<int, string> $lines
     */
    private function streamSimplePdf(array $lines, string $fileName): void
    {
        $maxLines = 45;
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines - 1);
            $lines[] = '... export tronque: trop de lignes pour le format PDF simple.';
        }

        $stream = "BT\n/F1 10 Tf\n14 TL\n40 800 Td\n";
        $first = true;
        foreach ($lines as $line) {
            if (!$first) {
                $stream .= "T*\n";
            }
            $stream .= '(' . $this->pdfEscape($line) . ") Tj\n";
            $first = false;
        }
        $stream .= "ET";

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";
        $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }
}
