<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap_web.php';

use PU239\Http\Router;
use PU239\Http\MiddlewarePipeline;
use PU239\Http\Middlewares\ForceHttps;
use PU239\Http\Middlewares\Hsts;
use PU239\Http\Middlewares\SecurityHeaders;
use PU239\Http\Middlewares\RateLimitPost;
use PU239\Http\Middlewares\CsrfGate;
use PU239\Http\Middlewares\JsonOut;
use PU239\Http\Middlewares\AuthZGate;

if (!defined('PU239_ROUTED')) {
    define('PU239_ROUTED', true);
}

$router = new Router();

$pipeline = new MiddlewarePipeline([
    new ForceHttps(),
    new Hsts(),
    new SecurityHeaders(),
    new RateLimitPost(10, 60),
    new CsrfGate(),
    new JsonOut(),
]);

// Optional legacy home mapping
$router->get('/', \PU239\Http\Handlers\HomeHandler::class, ['legacy' => __DIR__ . '/index.legacy.php']);
$router->get('/index.php', \PU239\Http\Handlers\HomeHandler::class, ['legacy' => __DIR__ . '/index.legacy.php']);

// Public routes
$router->get('/coins.php', \PU239\Http\Handlers\PublicSite\CoinsHandler::class);
$router->get('/credits.php', \PU239\Http\Handlers\PublicSite\CreditsHandler::class);
$router->get('/friends.php', \PU239\Http\Handlers\PublicSite\FriendsHandler::class);
$router->get('/gift.php', \PU239\Http\Handlers\PublicSite\GiftHandler::class);
$router->get('/invite.php', \PU239\Http\Handlers\PublicSite\InviteHandler::class);
$router->get('/messages.php', \PU239\Http\Handlers\PublicSite\MessagesHandler::class);
$router->get('/reputation.php', \PU239\Http\Handlers\PublicSite\ReputationHandler::class);
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
$router->get('/arcade_top_scores.php', \PU239\Http\Handlers\PublicSite\ArcadeTopScoresHandler::class);
$router->get('/bitbucket.php', \PU239\Http\Handlers\PublicSite\BitbucketHandler::class);
$router->get('/bjstats.php', \PU239\Http\Handlers\PublicSite\BjstatsHandler::class);
$router->get('/blackjack.php', \PU239\Http\Handlers\PublicSite\BlackjackHandler::class);
$router->get('/bookmarks.php', \PU239\Http\Handlers\PublicSite\BookmarksHandler::class);
$router->get('/bot_triggers.php', \PU239\Http\Handlers\PublicSite\BotTriggersHandler::class);
$router->get('/browse.php', \PU239\Http\Handlers\PublicSite\BrowseHandler::class);
$router->get('/bugs.php', \PU239\Http\Handlers\PublicSite\BugsHandler::class);
$router->post('/bugs.php', \PU239\Http\Handlers\PublicSite\BugsHandler::class);
$router->get('/casino.php', \PU239\Http\Handlers\PublicSite\CasinoHandler::class);
$router->get('/catalog.php', \PU239\Http\Handlers\PublicSite\CatalogHandler::class);
$router->get('/categoryids.php', \PU239\Http\Handlers\PublicSite\CategoryidsHandler::class);
$router->get('/chat.php', \PU239\Http\Handlers\PublicSite\ChatHandler::class);
$router->get('/clear_announcement.php', \PU239\Http\Handlers\PublicSite\ClearAnnouncementHandler::class);
$router->get('/contactstaff.php', \PU239\Http\Handlers\PublicSite\ContactstaffHandler::class);
$router->post('/contactstaff.php', \PU239\Http\Handlers\PublicSite\ContactstaffHandler::class);
$router->get('/delete.php', \PU239\Http\Handlers\PublicSite\DeleteHandler::class);
$router->post('/delete.php', \PU239\Http\Handlers\PublicSite\DeleteHandler::class);
$router->get('/details.php', \PU239\Http\Handlers\PublicSite\DetailsHandler::class);
$router->post('/details.php', \PU239\Http\Handlers\PublicSite\DetailsHandler::class);
$router->get('/download.php', \PU239\Http\Handlers\PublicSite\DownloadHandler::class);
$router->get('/download_multi.php', \PU239\Http\Handlers\PublicSite\DownloadMultiHandler::class);
$router->get('/downloadsub.php', \PU239\Http\Handlers\PublicSite\DownloadsubHandler::class);
$router->post('/downloadsub.php', \PU239\Http\Handlers\PublicSite\DownloadsubHandler::class);
$router->get('/edit.php', \PU239\Http\Handlers\PublicSite\EditHandler::class);
$router->post('/edit.php', \PU239\Http\Handlers\PublicSite\EditHandler::class);
$router->get('/faq.php', \PU239\Http\Handlers\PublicSite\FaqHandler::class);
$router->get('/fastdelete.php', \PU239\Http\Handlers\PublicSite\FastdeleteHandler::class);
$router->get('/filelist.php', \PU239\Http\Handlers\PublicSite\FilelistHandler::class);
$router->get('/flash.php', \PU239\Http\Handlers\PublicSite\FlashHandler::class);
$router->get('/forums.php', \PU239\Http\Handlers\PublicSite\ForumsHandler::class);
$router->get('/games.php', \PU239\Http\Handlers\PublicSite\GamesHandler::class);
$router->get('/getrss.php', \PU239\Http\Handlers\PublicSite\GetrssHandler::class);
$router->get('/happylog.php', \PU239\Http\Handlers\PublicSite\HappylogHandler::class);
$router->get('/hnrs.php', \PU239\Http\Handlers\PublicSite\HnrsHandler::class);
$router->get('/img.php', \PU239\Http\Handlers\PublicSite\ImgHandler::class);
$router->get('/login.php', \PU239\Http\Handlers\PublicSite\LoginHandler::class);
$router->post('/login.php', \PU239\Http\Handlers\PublicSite\LoginHandler::class);
$router->get('/logout.php', \PU239\Http\Handlers\PublicSite\LogoutHandler::class);
$router->get('/lottery.php', \PU239\Http\Handlers\PublicSite\LotteryHandler::class);
$router->get('/movies.php', \PU239\Http\Handlers\PublicSite\MoviesHandler::class);
$router->get('/mybonus.php', \PU239\Http\Handlers\PublicSite\MybonusHandler::class);
$router->post('/mybonus.php', \PU239\Http\Handlers\PublicSite\MybonusHandler::class);
$router->get('/mytorrents.php', \PU239\Http\Handlers\PublicSite\MytorrentsHandler::class);
$router->get('/needseed.php', \PU239\Http\Handlers\PublicSite\NeedseedHandler::class);
$router->get('/new_announcement.php', \PU239\Http\Handlers\PublicSite\NewAnnouncementHandler::class);
$router->post('/new_announcement.php', \PU239\Http\Handlers\PublicSite\NewAnnouncementHandler::class);
$router->get('/offers.php', \PU239\Http\Handlers\PublicSite\OffersHandler::class);
$router->post('/offers.php', \PU239\Http\Handlers\PublicSite\OffersHandler::class);
$router->get('/peerlist.php', \PU239\Http\Handlers\PublicSite\PeerlistHandler::class);
$router->get('/polls_take_vote.php', \PU239\Http\Handlers\PublicSite\PollsTakeVoteHandler::class);
$router->post('/polls_take_vote.php', \PU239\Http\Handlers\PublicSite\PollsTakeVoteHandler::class);
$router->get('/port_check.php', \PU239\Http\Handlers\PublicSite\PortCheckHandler::class);
$router->get('/recover.php', \PU239\Http\Handlers\PublicSite\RecoverHandler::class);
$router->post('/recover.php', \PU239\Http\Handlers\PublicSite\RecoverHandler::class);
$router->get('/report.php', \PU239\Http\Handlers\PublicSite\ReportHandler::class);
$router->post('/report.php', \PU239\Http\Handlers\PublicSite\ReportHandler::class);
$router->get('/requests.php', \PU239\Http\Handlers\PublicSite\RequestsHandler::class);
$router->post('/requests.php', \PU239\Http\Handlers\PublicSite\RequestsHandler::class);
$router->get('/restoreclass.php', \PU239\Http\Handlers\PublicSite\RestoreclassHandler::class);
$router->get('/rss.php', \PU239\Http\Handlers\PublicSite\RssHandler::class);
$router->get('/rss_pdo_demo.php', \PU239\Http\Handlers\PublicSite\RssPdoDemoHandler::class);
$router->get('/rsstfreak.php', \PU239\Http\Handlers\PublicSite\RsstfreakHandler::class);
$router->get('/rules.php', \PU239\Http\Handlers\PublicSite\RulesHandler::class);

// Admin/staff (with AuthZGate metadata)
$router->get('/admin/namechanger.php', \PU239\Http\Handlers\Admin\NamechangerHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/reports.php', \PU239\Http\Handlers\Admin\ReportsHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/warn.php', \PU239\Http\Handlers\Admin\WarnHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/class_promo.php', \PU239\Http\Handlers\Admin\ClassPromoHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/sitelog.php', \PU239\Http\Handlers\Admin\SitelogHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/comments.php', \PU239\Http\Handlers\Admin\CommentsHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/reputation_ad.php', \PU239\Http\Handlers\Admin\ReputationAdHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/shit_list.php', \PU239\Http\Handlers\Admin\ShitListHandler::class, ['authz' => new AuthZGate('admin')]);
$router->get('/admin/system_view.php', \PU239\Http\Handlers\Admin\SystemViewHandler::class, ['authz' => new AuthZGate('admin')]);

$router->get('/staffpanel.php', \PU239\Http\Handlers\Staffpanel\IndexHandler::class, ['authz' => new AuthZGate(['any' => ['staff', 'admin']])]);
$router->get('/staffpanel/index.php', \PU239\Http\Handlers\Staffpanel\IndexHandler::class, ['authz' => new AuthZGate(['any' => ['staff', ' admin']])]);

$pipeline->handle($router);

// >>>>>> PU239:http-front-1
