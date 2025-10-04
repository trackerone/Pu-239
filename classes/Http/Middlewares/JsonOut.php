<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

use JsonSerializable;

final class JsonOut
{
    public function process(callable $next)
    {
        $result = $next();
        if (is_array($result) || $result instanceof JsonSerializable) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }

            return json_encode($result, JSON_THROW_ON_ERROR);
        }

        return $result;
    }
}
