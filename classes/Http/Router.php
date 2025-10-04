<?php
declare(strict_types=1);

namespace PU239\Http;

use function array_key_exists;
use function class_exists;
use function http_response_code;
use function parse_url;
use function strtoupper;

final class Router
{
    /** @var array<string, array<int, array{path:string,handler:string,meta:array}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, string $handler, array $meta = []): void
    {
        $this->routes['GET'][] = ['path' => $path, 'handler' => $handler, 'meta' => $meta];
    }

    public function post(string $path, string $handler, array $meta = []): void
    {
        $this->routes['POST'][] = ['path' => $path, 'handler' => $handler, 'meta' => $meta];
    }

    /**
     * @return array{0:string,1:array}
     */
    public function dispatch(): array
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $candidates = $method === 'HEAD' ? ['GET'] : [$method];

        foreach ($candidates as $candidate) {
            if (!array_key_exists($candidate, $this->routes)) {
                continue;
            }

            foreach ($this->routes[$candidate] as $route) {
                if ($route['path'] !== $uri) {
                    continue;
                }

                if (!class_exists($route['handler'])) {
                    continue;
                }

                return [$route['handler'], $route['meta']];
            }
        }

        http_response_code(404);
        exit('Not Found');
    }
}

// >>>>>> PU239:http-router-2
