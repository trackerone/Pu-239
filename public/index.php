<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap_web.php';

use PU239\Http\Handlers\HomeHandler;
use PU239\Http\MiddlewarePipeline;
use PU239\Http\Middlewares\CsrfGate;
use PU239\Http\Middlewares\RateLimitPost;
use PU239\Http\Middlewares\SecurityHeaders;
use PU239\Http\Router;

$router = new Router();
$pipeline = new MiddlewarePipeline([
    new SecurityHeaders(),
    new RateLimitPost(10, 60),
    new CsrfGate(),
]);

// Register a SMALL set of routes in this batch (mikro-batch; no DB logic here)
$router->get('/', HomeHandler::class, ['legacy' => __DIR__ . '/index.legacy.php']);
$router->get('/index.php', HomeHandler::class, ['legacy' => __DIR__ . '/index.legacy.php']);
$router->get('/coins.php', HomeHandler::class, ['legacy' => __DIR__ . '/coins.php']);
$router->get('/credits.php', HomeHandler::class, ['legacy' => __DIR__ . '/credits.php']);
$router->get('/friends.php', HomeHandler::class, ['legacy' => __DIR__ . '/friends.php']);

$pipeline->handle($router);

// >>>>>> PU239:http-front-1
