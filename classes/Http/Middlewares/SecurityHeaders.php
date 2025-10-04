<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

final class SecurityHeaders
{
    public function process(callable $next): void {
    public function process(callable $next): void
    {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: no-referrer-when-downgrade');
            header('X-Frame-Options: DENY');
        }

        $next();
    }
}

// >>>>>> PU239:http-mw-4
