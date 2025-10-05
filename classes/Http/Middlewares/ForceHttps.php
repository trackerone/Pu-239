<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

final class ForceHttps
{
    public function process(callable $next)
    {
        $https = $_SERVER['HTTPS'] ?? '';
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

        $isHttps = $https !== '' && strcasecmp((string) $https, 'off') !== 0;
        $isForwardedHttps = strcasecmp($forwardedProto, 'https') === 0;

        if ($isHttps || $isForwardedHttps) {
            return $next();
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        header('Location: https://' . $host . $uri, true, 301);
        exit();
    }
}
