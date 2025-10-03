<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap_web.php';

use PU239\Http\Handlers\Admin\NamechangerHandler;
use PU239\Http\Handlers\Admin\ReportsHandler;
use PU239\Http\Handlers\HomeHandler;
use PU239\Http\Handlers\PublicSite\CoinsHandler;
use PU239\Http\Handlers\PublicSite\CreditsHandler;
use PU239\Http\Handlers\PublicSite\FriendsHandler;
use PU239\Http\Handlers\PublicSite\GiftHandler;
use PU239\Http\Handlers\PublicSite\InviteHandler;
use PU239\Http\Handlers\PublicSite\MessagesHandler;
use PU239\Http\Handlers\PublicSite\ReputationHandler;
use PU239\Http\Handlers\Staffpanel\IndexHandler;
use PU239\Http\MiddlewarePipeline;
use PU239\Http\Middlewares\CsrfGate;
use PU239\Http\Middlewares\JsonOut;
use PU239\Http\Middlewares\RateLimitPost;
use PU239\Http\Middlewares\SecurityHeaders;
use PU239\Http\Router;

if (!defined('PU239_ROUTED')) {
    define('PU239_ROUTED', true);
}

$router = new Router();
$pipeline = new MiddlewarePipeline([
    new SecurityHeaders(),
    new RateLimitPost(10, 60),
    new CsrfGate(),
    new JsonOut(),
]);

$router->get('/', HomeHandler::class);
$router->get('/index.php', HomeHandler::class);
$router->get('/coins.php', CoinsHandler::class);
$router->get('/credits.php', CreditsHandler::class);
$router->get('/friends.php', FriendsHandler::class);
$router->get('/gift.php', GiftHandler::class);
$router->get('/invite.php', InviteHandler::class);
$router->get('/messages.php', MessagesHandler::class);
$router->get('/reputation.php', ReputationHandler::class);
$router->get('/admin/namechanger.php', NamechangerHandler::class, ['authz' => 'admin']);
$router->get('/admin/reports.php', ReportsHandler::class, ['authz' => 'admin']);
$router->get('/staffpanel.php', IndexHandler::class, ['authz' => ['any' => ['staff', 'admin']]]);
$router->get('/staffpanel/index.php', IndexHandler::class, ['authz' => ['any' => ['staff', 'admin']]]);

$pipeline->handle($router);

// >>>>>> PU239:http-front-1
