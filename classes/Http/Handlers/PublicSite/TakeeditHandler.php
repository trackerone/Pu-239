<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\PublicSite;

final class TakeeditHandler
{
    public function handle(array $meta = []): mixed
    {
        if (!defined('PU239_ROUTED')) {
            define('PU239_ROUTED', true);
        }

        require \dirname(__DIR__, 4) . '/public/takeedit.php';

        return null;
    }
}
