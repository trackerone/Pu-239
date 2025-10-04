<?php
declare(strict_types=1);

namespace PU239\Http;

final class Router
{
    /** @var array<string, array<int, array{path:string,handler:string,meta:array}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, string $handler, array $meta = []): void
    {
        $this->routes['GET'][] = ['path' => $path, 'handler' => $handler, 'meta' => $meta];
    }

    public function post(string $path, string $handler, array $meta = []): void
    {
        $this->routes['POST'][] = ['path' => $path, 'handler' => $handler, 'meta' => $meta];
    }

    /**
     * @return array{0: string, 1: array}
     */
    public function dispatch(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($uri) ? $uri : '/';

        foreach ($this->routes[$method] ?? [] as $route) {
            if ($route['path'] === $path) {
                return [$route['handler'], $route['meta']];
            }
        }

        http_response_code(404);
        exit('Not Found');
    }
}

// >>>>>> PU239:http-router-2
