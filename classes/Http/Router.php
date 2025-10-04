<?php
declare(strict_types=1);

namespace PU239\Http;

final class Router
{
    /** @var array<string, array<int, array{handler: string, meta: array, path: string}>> */
    private array $routes = ['GET' => [], 'POST' => []];

    // >>>>>> PU239:http-router-2

    public function get(string $path, string $handler, array $meta = []): void
    {
        $this->routes['GET'][] = ['handler' => $handler, 'meta' => $meta, 'path' => $path];
    }

    public function post(string $path, string $handler, array $meta = []): void
    {
        $this->routes['POST'][] = ['handler' => $handler, 'meta' => $meta, 'path' => $path];
    }

    /**
     * @return array{0: string, 1: array}
     */
    public function dispatch(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        foreach ($this->routes[$method] ?? [] as $route) {
            if ($route['path'] === $uri) {
                return [$route['handler'], $route['meta']];
            }
        }
        // >>>>>> PU239:http-router-2
        http_response_code(404);
        exit('Not Found');
    }
}

// >>>>>> PU239:http-router-2






