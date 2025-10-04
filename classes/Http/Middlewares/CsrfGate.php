<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

use PU239\Support\Csrf;
use function http_response_code;

final class CsrfGate
{
    public function process(callable $next): mixed
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            if (!Csrf::verify($_POST['csrf'] ?? '')) {
                http_response_code(419);
                exit('CSRF verification failed.');
            }
        }

        return $next();
    }
}
