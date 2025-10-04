<?php
declare(strict_types=1);

namespace PU239\Http\Handlers;

final class HomeHandler
{
    public function handle(array $meta = []): void
    {
        if (isset($meta['legacy']) && is_string($meta['legacy'])) {
            $legacy = $meta['legacy'];
            if (is_file($legacy)) {
                require $legacy;
                return;
            }
        }

        echo 'OK';
    }
}

// >>>>>> PU239:http-handler-6
