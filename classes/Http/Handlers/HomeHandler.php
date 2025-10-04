<?php
declare(strict_types=1);

namespace PU239\Http\Handlers;

final class HomeHandler
{
    public function handle(array $meta = []): void {
        echo 'OK';
    }
}

// >>>>>> PU239:http-handler-6
