<?php
declare(strict_types=1);

namespace PU239\Http;

final class MiddlewarePipeline
{
    /** @var array<int, object> */
    private array $stack;

    public function __construct(array $stack) { $this->stack = $stack; }

    public function handle(Router $router): void {
        $next = function () use ($router) {
            [$handler, $meta] = $router->dispatch();
            (new $handler())->handle($meta);
        };
        foreach (array_reverse($this->stack) as $mw) {
            $prev = $next;
            $next = function () use ($mw, $prev) {
                if (method_exists($mw, 'process')) { $mw->process($prev); }
                else { $prev(); }
            };
        }
        $next();
    }
}

// >>>>>> PU239:http-pipeline-3
