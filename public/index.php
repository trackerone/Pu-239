<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap_web.php';

use PU239\Http\Router;
use PU239\Http\MiddlewarePipeline;
use PU239\Http\Middlewares\SecurityHeaders;
use PU239\Http\Middlewares\CsrfGate;
use PU239\Http\Middlewares\RateLimitPost;

$router = new Router();
$pipeline = new MiddlewarePipeline([
    new SecurityHeaders(),
    new RateLimitPost(10, 60),
    new CsrfGate(),
]);

$router->get('/', \PU239\Http\Handlers\HomeHandler::class);
$router->get('/index.php', \PU239\Http\Handlers\HomeHandler::class);

$pipeline->handle($router);

// >>>>>> PU239:http-front-1
