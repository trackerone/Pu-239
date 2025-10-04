<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap_web.php';

use PU239\Http\MiddlewarePipeline;
use PU239\Http\Middlewares\AuthZGate;
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
$router->get('/staffbox.php', \PU239\Http\Handlers\PublicSite\StaffboxHandler::class);
$router->get('/users.php', \PU239\Http\Handlers\PublicSite\UsersHandler::class);
$router->get('/usercp.php', \PU239\Http\Handlers\PublicSite\UsercpHandler::class);
$router->get('/usermood.php', \PU239\Http\Handlers\PublicSite\UsermoodHandler::class);
$router->get('/takeedit.php', \PU239\Http\Handlers\PublicSite\TakeeditHandler::class);
$router->get('/takeeditcp.php', \PU239\Http\Handlers\PublicSite\TakeeditcpHandler::class);
$router->get('/take_theme.php', \PU239\Http\Handlers\PublicSite\TakeThemeHandler::class);
$router->get('/takereseed.php', \PU239\Http\Handlers\PublicSite\TakereseedHandler::class);
$router->post('/takereseed.php', \PU239\Http\Handlers\PublicSite\TakereseedHandler::class);
$router->get('/takethankyou.php', \PU239\Http\Handlers\PublicSite\TakethankyouHandler::class);
$router->post('/takethankyou.php', \PU239\Http\Handlers\PublicSite\TakethankyouHandler::class);
$router->get('/takeupload.php', \PU239\Http\Handlers\PublicSite\TakeuploadHandler::class);
$router->post('/takeupload.php', \PU239\Http\Handlers\PublicSite\TakeuploadHandler::class);
$router->get('/tenpercent.php', \PU239\Http\Handlers\PublicSite\TenpercentHandler::class);
$router->post('/tenpercent.php', \PU239\Http\Handlers\PublicSite\TenpercentHandler::class);
$router->get('/tmovies.php', \PU239\Http\Handlers\PublicSite\TmoviesHandler::class);
$router->get('/topmoods.php', \PU239\Http\Handlers\PublicSite\TopmoodsHandler::class);
$router->get('/topten.php', \PU239\Http\Handlers\PublicSite\ToptenHandler::class);
$router->get('/trivia_results.php', \PU239\Http\Handlers\PublicSite\TriviaResultsHandler::class);
$router->get('/tvshows.php', \PU239\Http\Handlers\PublicSite\TvshowsHandler::class);
$router->get('/user_unlocks.php', \PU239\Http\Handlers\PublicSite\UserUnlocksHandler::class);
$router->post('/user_unlocks.php', \PU239\Http\Handlers\PublicSite\UserUnlocksHandler::class);
$router->get('/useragreement.php', \PU239\Http\Handlers\PublicSite\UseragreementHandler::class);
$router->get('/usercomment.php', \PU239\Http\Handlers\PublicSite\UsercommentHandler::class);
$router->post('/usercomment.php', \PU239\Http\Handlers\PublicSite\UsercommentHandler::class);
$router->get('/userdetails.php', \PU239\Http\Handlers\PublicSite\UserdetailsHandler::class);
$router->get('/userhistory.php', \PU239\Http\Handlers\PublicSite\UserhistoryHandler::class);
$router->get('/verify.php', \PU239\Http\Handlers\PublicSite\VerifyHandler::class);
$router->post('/verify.php', \PU239\Http\Handlers\PublicSite\VerifyHandler::class);
$router->get('/verify_email.php', \PU239\Http\Handlers\PublicSite\VerifyEmailHandler::class);
$router->get('/videoformats.php', \PU239\Http\Handlers\PublicSite\VideoformatsHandler::class);
$router->get('/view_announce_history.php', \PU239\Http\Handlers\PublicSite\ViewAnnounceHistoryHandler::class);
$router->get('/view_sql.php', \PU239\Http\Handlers\PublicSite\ViewSqlHandler::class);
$router->get('/viewnfo.php', \PU239\Http\Handlers\PublicSite\ViewnfoHandler::class);
$router->get('/wiki.php', \PU239\Http\Handlers\PublicSite\WikiHandler::class);
$router->post('/wiki.php', \PU239\Http\Handlers\PublicSite\WikiHandler::class);
$router->get('/achievementbonus.php', \PU239\Http\Handlers\PublicSite\AchievementbonusHandler::class);
$router->get('/achievementhistory.php', \PU239\Http\Handlers\PublicSite\AchievementhistoryHandler::class);
$router->get('/achievementlist.php', \PU239\Http\Handlers\PublicSite\AchievementlistHandler::class);
$router->post('/achievementlist.php', \PU239\Http\Handlers\PublicSite\AchievementlistHandler::class);
$router->get('/ajaxchat.php', \PU239\Http\Handlers\PublicSite\AjaxchatHandler::class);
$router->get('/allsmiles.php', \PU239\Http\Handlers\PublicSite\AllsmilesHandler::class);
$router->get('/anatomy.php', \PU239\Http\Handlers\PublicSite\AnatomyHandler::class);
$router->get('/announcement.php', \PU239\Http\Handlers\PublicSite\AnnouncementHandler::class);
$router->get('/arcade.php', \PU239\Http\Handlers\PublicSite\ArcadeHandler::class);

$pipe->handle($router);

// >>>>>> PU239:http-front-1

