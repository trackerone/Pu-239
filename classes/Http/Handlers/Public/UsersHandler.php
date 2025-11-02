<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Public;

use PU239\Http\Handlers\PublicSite\UsersHandler as PublicSiteUsersHandler;

final class UsersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        (new PublicSiteUsersHandler())->handle($meta);
    }
}
