<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap_web.php';

use PU239\Http\MiddlewarePipeline;
use PU239\Http\Middlewares\AuthZGate;
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
$pipe   = new MiddlewarePipeline([
$pipeline = new MiddlewarePipeline([
    new SecurityHeaders(),
    new RateLimitPost(10, 60),
    new CsrfGate(),
    new JsonOut(),
]);

$router->get('/', \PU239\Http\Handlers\HomeHandler::class);
$router->get('/index.php', \PU239\Http\Handlers\HomeHandler::class);
$router->get('/coins.php', \PU239\Http\Handlers\PublicSite\CoinsHandler::class);
$router->get('/credits.php', \PU239\Http\Handlers\PublicSite\CreditsHandler::class);
$router->get('/friends.php', \PU239\Http\Handlers\PublicSite\FriendsHandler::class);
$router->get('/gift.php', \PU239\Http\Handlers\PublicSite\GiftHandler::class);
$router->get('/invite.php', \PU239\Http\Handlers\PublicSite\InviteHandler::class);
$router->get('/messages.php', \PU239\Http\Handlers\PublicSite\MessagesHandler::class);
$router->get('/reputation.php', \PU239\Http\Handlers\PublicSite\ReputationHandler::class);
$router->get('/admin/namechanger.php', \PU239\Http\Handlers\Admin\NamechangerHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/reports.php', \PU239\Http\Handlers\Admin\ReportsHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/staffpanel.php', \PU239\Http\Handlers\Staffpanel\IndexHandler::class, ['authz' => new AuthZGate(['any' => ['staff', 'admin']])]);
$router->get('/staffpanel/index.php', \PU239\Http\Handlers\Staffpanel\IndexHandler::class, ['authz' => new AuthZGate(['any' => ['staff', 'admin']])]);
$router->get('/admin/warn.php', \PU239\Http\Handlers\Admin\WarnHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/class_promo.php', \PU239\Http\Handlers\Admin\ClassPromoHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/sitelog.php', \PU239\Http\Handlers\Admin\SitelogHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/comments.php', \PU239\Http\Handlers\Admin\CommentsHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/reputation_ad.php', \PU239\Http\Handlers\Admin\ReputationAdHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/shit_list.php', \PU239\Http\Handlers\Admin\ShitListHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/system_view.php', \PU239\Http\Handlers\Admin\SystemViewHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/comment.php', \PU239\Http\Handlers\PublicSite\CommentHandler::class);

$pipe->handle($router);
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
