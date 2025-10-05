<?php
declare(strict_types=1);

namespace PU239\Http\Handlers;

use function dirname;
use function file_exists;
use function is_string;

final class HomeHandler
{
    public function handle(array $meta = []): mixed
    public function handle(array $meta = []): void {
    public function handle(array $meta = []): void
    {
        if (!defined('PU239_ROUTED')) {
            define('PU239_ROUTED', true);
        }

        $legacy = $meta['legacy'] ?? dirname(__DIR__, 3) . '/public/index.legacy.php';
        require $legacy;
        if (!is_string($legacy) || $legacy === '' || !file_exists($legacy)) {
            $legacy = dirname(__DIR__, 3) . '/public/index.legacy.php';
        }

        require $legacy;

        return null;
        if (isset($meta['legacy']) && is_string($meta['legacy']) && is_file($meta['legacy'])) {
            require $meta['legacy'];

            return;
        }

        echo 'OK';
    }
}

// >>>>>> PU239:http-handler-6
