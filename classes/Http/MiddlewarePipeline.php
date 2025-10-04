<?php
declare(strict_types=1);

namespace PU239\Http;

use function array_reverse;
use function method_exists;

final class MiddlewarePipeline
{
    /** @var array<int, object> */
    private array $stack;

    public function __construct(array $stack)
    {
        $this->stack = $stack;
    }

    public function handle(Router $router): mixed
    {
        $next = static function () use ($router) {
            [$handler, $meta] = $router->dispatch();

            return (new $handler())->handle($meta);
        };

        foreach (array_reverse($this->stack) as $middleware) {
            $previous = $next;
            $next = static function () use ($middleware, $previous) {
                if (method_exists($middleware, 'process')) {
                    return $middleware->process($previous);
                }

                return $previous();
            };
        }

        return $next();
    }
}

// >>>>>> PU239:http-pipeline-3
