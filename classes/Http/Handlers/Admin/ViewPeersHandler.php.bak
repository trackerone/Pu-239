<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Admin;

final class ViewPeersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // Temporary: execute legacy script inside isolated scope
        (static function (): void {
            require __DIR__ . '/../../../../admin/view_peers.php';
        })();
    }
}
