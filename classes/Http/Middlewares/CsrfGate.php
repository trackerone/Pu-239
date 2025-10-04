<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

use PU239\Support\Csrf;

final class CsrfGate
{
    public function process(callable $next): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            if (!Csrf::verify($_POST['csrf'] ?? '')) {
                http_response_code(419);
                exit('CSRF verification failed.');
            }
        }

        $next();
    }
}
