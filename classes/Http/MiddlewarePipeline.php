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

    public function handle(Router $router): void
    {
    public function __construct(array $stack) { $this->stack = $stack; }

    public function handle(Router $router): void {
        $next = function () use ($router) {
            [$handler, $meta] = $router->dispatch();
            (new $handler())->handle($meta);
        };
        foreach (array_reverse($this->stack) as $middleware) {
            $previous = $next;
            $next = function () use ($middleware, $previous) {
                if (method_exists($middleware, 'process')) {
                    $middleware->process($previous);
                } else {
                    $previous();
                }
            };
        }
        foreach (array_reverse($this->stack) as $mw) {
            $prev = $next;
            $next = function () use ($mw, $prev) {
                if (method_exists($mw, 'process')) { $mw->process($prev); }
                else { $prev(); }
            };
        }
    public function __construct(array $stack)
    {
        $this->stack = $stack;
    }

    public function handle(Router $router): mixed
    {
        $next = static function () use ($router) {
            [$handler, $meta] = $router->dispatch();

            return (new $handler())->handle($meta);
    public function handle(Router $router): void
    {
        $next = static function () use ($router): void {
            [$handler, $meta] = $router->dispatch();
            (new $handler())->handle($meta);
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
            $next = static function () use ($middleware, $previous): void {
                if (method_exists($middleware, 'process')) {
                    $middleware->process($previous);
                    return;
                }

                $previous();
            };
        }

        $next();
    }
}

// >>>>>> PU239:http-pipeline-3
