<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Admin;

final class SitelogHandler
{
    public function handle(array $meta = []): mixed
    {
        if (!defined('PU239_ROUTED')) {
            define('PU239_ROUTED', true);
        }

        require \dirname(__DIR__, 4) . '/admin/sitelog.php';

        return null;
    }
}
