<?php
declare(strict_types=1);

namespace PU239\Http\Middlewares;

final class Hsts
{
    public function __construct(private int $maxAge = 31536000, private bool $includeSubdomains = true)
    {
    }

    public function process(callable $next)
    public function process(callable $next): void
    {
        if (!headers_sent()) {
            $header = 'Strict-Transport-Security: max-age=' . $this->maxAge;
            if ($this->includeSubdomains) {
                $header .= '; includeSubDomains';
            }

            header($header);
        }

        return $next();
        $next();
    }
}
