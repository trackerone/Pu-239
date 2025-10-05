<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\PublicSite;

final class MoviesHandler
{
    public function handle(array $meta = []): mixed
    {
        if (!defined('PU239_ROUTED')) {
            define('PU239_ROUTED', true);
        }

        require \dirname(__DIR__, 4) . '/public/movies.php';

        return null;
    }
}
