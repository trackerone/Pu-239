<?php
declare(strict_types=1);

namespace PU239\Http\Handlers;

final class HomeHandler
{
    public function handle(array $meta = []): void
    public function handle(array $meta = []): mixed
    {
        if (!defined('PU239_ROUTED')) {
            define('PU239_ROUTED', true);
        }

        if (isset($meta['legacy']) && is_string($meta['legacy'])) {
            require $meta['legacy'];
            return;
        }

        echo 'OK';
        require \dirname(__DIR__, 3) . '/public/index.legacy.php';

        return null;
    }
}

// >>>>>> PU239:http-handler-6
