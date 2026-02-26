<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\ResourceApiController;
use App\Controllers\Api\TaskApiController;
use App\Controllers\Api\UserApiController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;

Auth::start();
Database::connection();

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'POST') {
    $override = strtoupper((string)($_POST['_method'] ?? ($_GET['_method'] ?? ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ''))));
    if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $override;
    }
}

$route = trim((string)($_GET['route'] ?? ''), '/');
if ($route === '') {
    $pathInfo = (string)($_SERVER['PATH_INFO'] ?? '');
    if ($pathInfo !== '') {
        $route = trim($pathInfo, '/');
    } else {
        $requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if ($requestPath !== '' && strpos($requestPath, $scriptName) === 0) {
            $route = trim(substr($requestPath, strlen($scriptName)), '/');
        }
    }
}

$router = new Router();

$router->add(['POST'], 'auth/login', static function (): void {
    (new AuthApiController())->login();
});
$router->add(['POST'], 'auth/logout', static function (): void {
    (new AuthApiController())->logout();
});
$router->add(['GET'], 'auth/me', static function (): void {
    (new AuthApiController())->me();
});

$router->add(['GET'], 'resources', static function (): void {
    (new ResourceApiController())->index();
});
$router->add(['POST'], 'resources', static function (): void {
    (new ResourceApiController())->store();
});
$router->add(['GET'], 'resources/{id}', static function (array $params): void {
    (new ResourceApiController())->show((int)($params['id'] ?? 0));
});
$router->add(['PUT', 'PATCH'], 'resources/{id}', static function (array $params): void {
    (new ResourceApiController())->update((int)($params['id'] ?? 0));
});
$router->add(['DELETE'], 'resources/{id}', static function (array $params): void {
    (new ResourceApiController())->destroy((int)($params['id'] ?? 0));
});

$router->add(['GET'], 'tasks', static function (): void {
    (new TaskApiController())->index();
});
$router->add(['POST'], 'tasks', static function (): void {
    (new TaskApiController())->store();
});
$router->add(['GET'], 'tasks/{id}', static function (array $params): void {
    (new TaskApiController())->show((int)($params['id'] ?? 0));
});
$router->add(['PUT', 'PATCH'], 'tasks/{id}', static function (array $params): void {
    (new TaskApiController())->update((int)($params['id'] ?? 0));
});
$router->add(['DELETE'], 'tasks/{id}', static function (array $params): void {
    (new TaskApiController())->destroy((int)($params['id'] ?? 0));
});

$router->add(['GET'], 'users', static function (): void {
    (new UserApiController())->index();
});
$router->add(['POST'], 'users', static function (): void {
    (new UserApiController())->store();
});

$router->dispatch($method, $route);
