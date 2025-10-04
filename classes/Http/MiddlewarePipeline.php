<?php
declare(strict_types=1);

namespace PU239\Http;

use PU239\Http\Middlewares\AuthZGate;

final class MiddlewarePipeline
{
    /** @var array<int, object> */
    private array $stack;

    // >>>>>> PU239:http-pipeline-3

    public function __construct(array $stack)
    {
        $this->stack = $stack;
    }

    public function handle(Router $router): void
    {
        $next = static function () use ($router): void {
            [$handler, $meta] = $router->dispatch();
            (new $handler())->handle($meta);
        };

        foreach (array_reverse($this->stack) as $middleware) {
            $previous = $next;
            $next = static function () use ($middleware, $previous): void {
                if (method_exists($middleware, 'process')) {
                    $middleware->process($previous);
                    return;
                }

                $previous();
            };
        }

        $next();
        $next = static function () use ($router) {
            [$handler, $meta] = $router->dispatch();
            $runner = static function () use ($handler, $meta) {
                if (!class_exists($handler)) {
                    throw new \RuntimeException('Route handler not found: ' . $handler);
                }
                $instance = new $handler();
                if (!method_exists($instance, 'handle')) {
                    throw new \RuntimeException('Handler missing handle() method: ' . $handler);
                }
                return $instance->handle($meta);
            };

            if (isset($meta['authz'])) {
                $gate = $meta['authz'];
                if (!$gate instanceof AuthZGate) {
                    $gate = new AuthZGate($gate);
                }

                return $gate->process($runner);
            }

            return $runner();
        };

        foreach (array_reverse($this->stack) as $middleware) {
            $prev = $next;
            $next = static function () use ($middleware, $prev) {
                if (method_exists($middleware, 'process')) {
                    return $middleware->process($prev);
                }

                return $prev();
            };
        }

        $response = $next();
        // >>>>>> PU239:http-pipeline-3

        if ($response instanceof \Stringable) {
            echo (string) $response;

            return;
        }

        if (is_string($response)) {
            echo $response;

            return;
        }

        if ($response !== null && !is_bool($response)) {
            echo (string) $response;
        }
    }
}

// >>>>>> PU239:http-pipeline-3






