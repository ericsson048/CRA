<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\NotificationModel;

final class NotificationController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            $this->redirect('login.php');
        }

        $sessionUser = Auth::user() ?? [];
        $userId = (int)($sessionUser['id'] ?? 0);
        $model = new NotificationModel(Database::connection());

        if ($this->requestMethod() === 'POST') {
            $this->validateCsrf();
            $action = trim((string)($_POST['action'] ?? ''));

            if ($action === 'mark_read') {
                $notificationId = (int)($_POST['notification_id'] ?? 0);
                if ($notificationId > 0) {
                    $model->markAsRead($notificationId, $userId);
                }
                $this->redirect('notifications.php');
            }

            if ($action === 'mark_all_read') {
                $model->markAllAsRead($userId);
                $this->redirect('notifications.php?all_read=1');
            }
        }

        $this->render('notifications/index', [
            'sessionUser' => $sessionUser,
            'notifications' => $model->listForUser($userId),
            'allRead' => isset($_GET['all_read']),
        ]);
    }
}
