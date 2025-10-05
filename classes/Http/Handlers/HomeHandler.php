<?php
declare(strict_types=1);

namespace PU239\Http\Handlers;

final class HomeHandler
{
    public function handle(array $meta = []): void
    {
        if (!defined('PU239_ROUTED')) {
            define('PU239_ROUTED', true);
        }

        $legacy = $meta['legacy'] ?? dirname(__DIR__, 3) . '/public/index.legacy.php';
        require $legacy;
    }
}

// >>>>>> PU239:http-handler-6
