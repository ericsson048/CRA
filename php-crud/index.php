<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Controllers\Web\AuthController;
use App\Controllers\Web\PlanningController;
use App\Controllers\Web\ProjectController;
use App\Controllers\Web\ResourceController;
use App\Controllers\Web\TeamController;
use App\Controllers\Web\UserController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;

Auth::start();
Database::connection();

$router = new Router();

$router->add(['GET'], '', static function (): void {
    (new ResourceController())->index();
});
$router->add(['GET'], 'resources', static function (): void {
    (new ResourceController())->index();
});
$router->add(['GET', 'POST'], 'resources/create', static function (): void {
    (new ResourceController())->create();
});
$router->add(['GET', 'POST'], 'resources/edit/{id}', static function (array $params): void {
    (new ResourceController())->edit((int)($params['id'] ?? 0));
});
$router->add(['GET', 'POST'], 'resources/delete/{id}', static function (array $params): void {
    (new ResourceController())->delete((int)($params['id'] ?? 0));
});
$router->add(['GET'], 'resources/export', static function (): void {
    (new ResourceController())->export();
});

$router->add(['GET', 'POST'], 'planning', static function (): void {
    (new PlanningController())->index();
});
$router->add(['GET', 'POST'], 'users', static function (): void {
    (new UserController())->index();
});
$router->add(['GET', 'POST'], 'teams', static function (): void {
    (new TeamController())->index();
});
$router->add(['GET', 'POST'], 'projects', static function (): void {
    (new ProjectController())->index();
});
$router->add(['GET', 'POST'], 'login', static function (): void {
    (new AuthController())->login();
});
$router->add(['GET'], 'logout', static function (): void {
    (new AuthController())->logout();
});

$route = trim((string)($_GET['route'] ?? ''), '/');
$router->dispatch((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'), $route);
