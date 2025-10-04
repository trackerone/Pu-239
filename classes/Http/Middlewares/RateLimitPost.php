<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

use PU239\Security\RateLimiter;

final class RateLimitPost
{
    public function __construct(private int $limit, private int $window)
    {
    }

    public function process(callable $next)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            if (!RateLimiter::check($ip . ':' . $path, $this->limit, $this->window)) {
                http_response_code(429);
                header('Retry-After: ' . $this->window);
                exit('Too Many Requests');
            }
        }

        return $next();
    }
}

// >>>>>> PU239:http-mw-5



