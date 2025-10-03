<?php
declare(strict_types=1);

use PU239\Security\RateLimiter;

if (!function_exists('json_out')) {
    /**
     * Outputs JSON with correct headers and throws on encoding errors.
     *
     * @param array|\JsonSerializable|mixed $payload
     * @param int $code
     * @return never
     */
    function json_out($payload, int $code = 200): never
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }
}

if (!function_exists('http_too_many_requests')) {
    /**
     * Emit a 429 response with a friendly message.
     */
    function http_too_many_requests(int $retryAfter): never
    {
        if (!headers_sent()) {
            http_response_code(429);
            header('Content-Type: text/plain; charset=utf-8');
            header('Retry-After: ' . max(1, $retryAfter));
        }
        echo 'Too many requests. Please try again in ' . max(1, $retryAfter) . ' seconds.';
        exit;
    }
}

if (!function_exists('rate_limit_or_fail')) {
    function rate_limit_or_fail(?int $limit = null, ?int $window = null): void
    {
        [$allowed, $retryAfter] = RateLimiter::check($limit, $window);
        if (!$allowed) {
            http_too_many_requests($retryAfter);
        }
    }
}

