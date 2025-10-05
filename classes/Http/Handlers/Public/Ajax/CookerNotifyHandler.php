<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Public\Ajax;

final class CookerNotifyHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // Temporary: execute legacy script inside isolated scope
        (static function (): void {
            require __DIR__ . '/../../../../../public/ajax/cooker_notify.php';
        })();
    }
}
