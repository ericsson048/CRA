<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\NotificationModel;

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

        if (!array_key_exists('sessionUser', $viewData)) {
            $viewData['sessionUser'] = Auth::user();
        }

        $sessionUser = $viewData['sessionUser'] ?? null;
        if (is_array($sessionUser) && isset($sessionUser['id']) && !array_key_exists('notificationUnreadCount', $viewData)) {
            try {
                $notificationModel = new NotificationModel(Database::connection());
                $viewData['notificationUnreadCount'] = $notificationModel->countUnreadForUser((int)$sessionUser['id']);
            } catch (\Throwable $exception) {
                $viewData['notificationUnreadCount'] = 0;
            }
        }

        extract($viewData, EXTR_SKIP);
        require $path;
    }
}
