<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

use function headers_sent;
use function header;

final class SecurityHeaders
{
    public function process(callable $next): mixed
    {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: no-referrer-when-downgrade');
            header('X-Frame-Options: DENY');
        }

        return $next();
    }
}

// >>>>>> PU239:http-mw-4
