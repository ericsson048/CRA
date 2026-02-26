<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $viewData = []): void
    {
        $path = BASE_PATH . '/app/Views/' . $view . '.php';
        if (!is_file($path)) {
            http_response_code(500);
            echo 'Vue introuvable: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            exit;
        }

        $helpersPath = BASE_PATH . '/app/Views/layout/helpers.php';
        if (is_file($helpersPath)) {
            require_once $helpersPath;
        }

        extract($viewData, EXTR_SKIP);
        require $path;
    }
}
