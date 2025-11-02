<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Public;

use PU239\Http\Handlers\PublicSite\MessagesHandler as PublicSiteMessagesHandler;

final class MessagesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        (new PublicSiteMessagesHandler())->handle($meta);
    }
}
