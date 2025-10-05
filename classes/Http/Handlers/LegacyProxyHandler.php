<?php
declare(strict_types=1);

namespace PU239\Http\Handlers;

final class LegacyProxyHandler
{
    /**
     * @param array{legacy?:string} $meta
     */
    public function handle(array $meta = []): void
    {
        $legacy = $meta['legacy'] ?? null;
        if (!is_string($legacy) || !is_file($legacy)) {
            http_response_code(500);
            echo 'Legacy target missing';
            return;
        }

        // Isolate scope, prevent variable leakage
        (static function (string $file): void {
            require $file;
        })($legacy);
    }
}
