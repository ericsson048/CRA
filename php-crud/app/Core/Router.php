<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{methods: array<int, string>, pattern: string, regex: string, handler: callable}> */
    private array $routes = [];

    /**
     * @param array<int, string> $methods
     */
    public function add(array $methods, string $pattern, callable $handler): void
    {
        $cleanPattern = trim($pattern, '/');
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $cleanPattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $cleanPattern,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $route): void
    {
        $cleanRoute = trim($route, '/');
        foreach ($this->routes as $entry) {
            if (!in_array(strtoupper($method), $entry['methods'], true)) {
                continue;
            }
            if (!preg_match($entry['regex'], $cleanRoute, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            if (empty($params)) {
                call_user_func($entry['handler']);
            } else {
                call_user_func($entry['handler'], $params);
            }
            return;
        }

        http_response_code(404);
        echo 'Route introuvable.';
    }
}
