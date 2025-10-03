<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Staffpanel;

final class IndexHandler
{
    public function handle(array $meta = []): mixed
    {
        if (!defined('PU239_ROUTED')) {
            define('PU239_ROUTED', true);
        }

        require \dirname(__DIR__, 4) . '/staffpanel/index.php';

        return null;
    }
}
