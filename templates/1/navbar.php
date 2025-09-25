<?php
declare(strict_types=1);

require_once __DIR__ . '/../../include/runtime_safe.php';
require_once __DIR__ . '/../../include/bootstrap_pdo.php';

use Delight\Auth\Auth;
use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Roles;
use PU239\Config\ConfigRepository;

global $container;
/** @var Database $db */
$db = $container->get(Database::class);
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$baseUrl = (string) $config->get('paths.baseurl', '');

/**
 * @throws Exception
 *
 * @return string
 */
function navbar()
{
    global $container, $CURUSER, $BLOCKS, $config, $baseUrl;

    $auth = $container->get(Auth::class);
    $navbar = '';
    $staff_links = staff_panel();
    if ($CURUSER) {
        $siteName = (string) $config->get('site.name', 'Pu-239');
        // TODO(2025): map legacy key "site.name" to appropriate config path
        $bucketAllowed = $config->bool('storage.bucket.allowed', false);
        // TODO(2025): map legacy key "bucket.allowed" to appropriate config path
        $navbar = "
<div class='spacer'>
    <header id='navbar'>
        <div class='contained'>
            <div class='nav_container'>
                <div id='pm_count' class='has-text-centered vertical_center'></div>
                <div id='hamburger'><i class='icon-menu size_6 has-text-link' aria-hidden='true'></i></div>
                <div id='close' class='top20 right10'><i class='icon-cancel icon size_7 has-text-link' aria-hidden='true'></i></div>
                <div id='menuWrapper'>
                    <ul class='level'>
                        <li>
                            <a href='{$baseUrl}' class='is-flex'>
                                <i class='icon-home size_6'></i>
                                <span class='home'>{$siteName}</span>
                            </a>
                        </li>" . ($BLOCKS['bluray_com_api_on'] || $BLOCKS['imdb_api_on'] || $BLOCKS['tvmaze_api_on'] ? "
                        <li id='movies_links' class='clickable'>
                            <a href='#' class='has-text-weight-bold'>" . _('Movies & TV') . "</a>
                            <ul class='ddFade ddFadeFast'>" . ($BLOCKS['bluray_com_api_on'] ? "
                                <li class='hide-mobile'><span class='left10 has-text-weight-bold'>" . _('Blu-Ray.com') . "</span></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=bluray'>" . _('Bluray Releases') . '</a></li>' : '') . ($BLOCKS['imdb_api_on'] ? "
                                <li class='hide-mobile'><span class='left10 has-text-weight-bold'>" . _('IMDb') . "</span></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=imdb_top_movies'>" . _('Top Movies') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=imdb_top_oscar'>" . _('Top Oscar Winners') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=imdb_top_tv'>" . _('Top TV Shows') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=imdb_top_anime'>" . _('Top Anime') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=imdb_theaters'>" . _('In Theaters') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=upcoming'>" . _('Upcoming') . '</a></li>' : '') . ($BLOCKS['tmdb_api_on'] ? "
                                <li class='hide-mobile'><span class='left10 has-text-weight-bold'>" . _('TMDb') . "</span></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=tmdb_top_movies'>" . _('Top Movies') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=tmdb_theaters'>" . _('In Theaters') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=tv'>" . _('TV Airing') . '</a></li>' : '') . ($BLOCKS['tvmaze_api_on'] ? "
                                <li class='hide-mobile'><span class='left10 has-text-weight-bold'>" . _('TVMaze') . "</span></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/movies.php?list=tvmaze'>" . _('TV Airing') . '</a></li>' : '') . '
                            </ul>
                        </li>' : '') . "
                        <li id='torrents_links' class='clickable'>
                            <a href='#' class='has-text-weight-bold'>" . _('Torrent') . "</a>
                            <ul class='ddFade ddFadeFast'>
                                <li class='hide-mobile'><a href='{$baseUrl}/browse.php'>" . _('Browse Torrents') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/catalog.php'>" . _('Catalog') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/upcoming.php'>" . _('Cooker') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/tmovies.php'>" . _('Movies') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/needseed.php?needed=seeders'><span class='has-text-weight-bold has-text-danger'>" . _('Needs Seeds') . "</span></a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/browse.php?today=1' class='has-text-weight-bold has-text-green'>" . _('New Torrents Today') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/offers.php'>" . _('Offers') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/requests.php'>" . _('Requests') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/subtitles.php'>" . _('Subtitles') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/tvshows.php'>" . _('TV Shows') . '</a></li>' . (!$auth->hasRole(Roles::UPLOADER) ? "
                                <li class='hide-mobile'><a href='{$baseUrl}/uploadapp.php'>" . _('Uploader Application') . '</a></li>' : "
                                <li class='hide-mobile'><a href='{$baseUrl}/upload.php'>" . _('Upload') . '</a></li>') . "
                            </ul>
                        </li>
                        <li id='general_links' class='clickable'>
                            <a href='#' class='has-text-weight-bold'>" . _('General') . "</a>
                            <ul class='ddFade ddFadeFast'>" . ($bucketAllowed ? "
                                <li class='hide-mobile'><a href='{$baseUrl}/bitbucket.php'>" . _('BitBucket') . '</a></li>' : '') . "
                                <li class='hide-mobile'><a href='{$baseUrl}/bot_triggers.php'>" . _('Bot Triggers') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/faq.php'>" . _('FAQ') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/chat.php'>" . _('IRC') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/mybonus.php'>" . _('Karma Store') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/getrss.php'>" . _('Get RSS') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/rules.php'>" . _('Rules') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/announcement.php'>" . _('Site Announcements') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/topten.php'>" . _('Statistics') . '</a></li>' . ($BLOCKS['torrentfreak_on'] ? "
                                <li class='hide-mobile'><a href='{$baseUrl}/rsstfreak.php'>" . _('Torrent Freak') . '</a></li>' : '') . "
                                <li class='hide-mobile'><a href='{$baseUrl}/wiki.php'>" . _('Wiki') . "</a></li>
                            </ul>
                        </li>
                        <li id='games_links' class='clickable'>
                            <a href='#' class='has-text-weight-bold'>" . _('Games') . "</a>
                            <ul class='ddFade ddFadeFast'>
                                <li class='hide-mobile'><a href='{$baseUrl}/arcade.php'>" . _('Arcade') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/games.php'>" . _('Games') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/lottery.php'>" . _('Lottery') . "</a></li>
                            </ul>
                        </li>
                        <li id='user_links' class='clickable'>
                            <a href='#' class='has-text-weight-bold'>" . _('Users') . "</a>
                            <ul class='ddFade ddFadeFast'>
                                <li class='hide-mobile'><a href='{$baseUrl}/bookmarks.php'>" . _('Bookmarks') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/categoryids.php'>" . _("Category ID's") . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/friends.php'>" . _('Friends') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/hnrs.php'>" . _("Hit 'n Runs") . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/invite.php?do=view_page'>" . _('Invites') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/messages.php'>" . _('Messages') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/port_check.php'>" . _('Port Check') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/users.php'>" . _('Search Users') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/usercp.php?action=default' class='has-text-weight-bold'>" . _('User Control Panel') . "</a></li>
                            </ul>
                        </li>
                        <li id='forum_links' class='clickable'>
                            <a href='#' class='has-text-weight-bold'>" . _('Forums') . "</a>
                            <ul class='ddFade ddFadeFast'>
                                <li class='hide-mobile'>
                                    <a href='{$baseUrl}/forums.php'>" . _('Forums') . "</a>
                                </li>
                                <li class='hide-mobile'>
                                    <a href='{$baseUrl}/forums.php?action=view_unread_posts'>" . _('Unread Posts') . "</a>
                                </li>
                            </ul>
                        </li>
                        </li>" . (!has_access($CURUSER['class'], UC_STAFF, 'coder') ? "
                        <li id='staff_links' class='clickable'>
                            <a href='#' class='has-text-weight-bold'>" . _('Help') . "</a>
                            <ul class='ddFade ddFadeFast'>
                                <li class='hide-mobile'><a href='{$baseUrl}/bugs.php?action=add'>" . _('Bug Report') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/contactstaff.php'>" . _('Contact Staff') . "</a></li>
                                <li class='hide-mobile'><a href='{$baseUrl}/staff.php'>" . _('Staff List') . '</a></li>
                            </ul>
                        </li>' : '') . ($BLOCKS['global_staff_menu_on'] ? $staff_links : (has_access($CURUSER['class'], UC_STAFF, 'coder') ? "
                        <li>
                            <a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>
                        </li>' : '')) . "
                        <li>
                            <a href='{$baseUrl}/logout.php' class='is-flex'>
                            <i class='icon-logout size_6' aria-hidden='true'></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
</div>";
    }

    return $navbar;
}

/**
 * @param array $value
 *
 * @return string
 */
function make_link(array $value)
{
    global $baseUrl;

    return "
                            <li class='hide-mobile'><a href='{$baseUrl}/" . htmlsafechars($value['file_name']) . "'>" . _($value['page_name']) . '</a></li>';
}

/**
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \PDOException
 *
 * @return string
 */
function staff_panel()
{
    global $BLOCKS, $CURUSER, $container, $config, $db, $baseUrl;

    $cache = $container->get(Cache::class);
    $panel = '';
    $panels = [];
    if ($BLOCKS['global_staff_menu_on'] && has_access($CURUSER['class'], UC_STAFF, 'coder')) {
        $adminerAllowedIds = (array) $config->get('adminer.allowed_ids', []);
        // TODO(2025): map legacy key "adminer.allowed_ids" to appropriate config path
        $user_class = $CURUSER['class'] >= UC_STAFF ? $CURUSER['class'] : UC_MAX;
        $staff_panel = $cache->get('staff_panels_' . $user_class);
        if ($staff_panel === false || is_null($staff_panel)) {
            $sql = 'SELECT page_name, file_name, type, av_class FROM staffpanel WHERE navbar = 1 AND av_class <= :class ORDER BY page_name';
            $staff_panel = $db->fetchAll($sql, [':class' => $user_class]);

            $cache->set('staff_panels_' . $user_class, $staff_panel, 0);
        }
        $staff_panel[] = [
            'id' => 0,
            'page_name' => _('Staff Messages'),
            'file_name' => 'staffbox.php',
            'description' => _('View Staff Messages'),
            'type' => 'user',
            'av_class' => UC_STAFF,
            'added_by' => 1,
            'added' => 1546167296,
            'navbar' => 1,
        ];
        if (in_array($CURUSER['id'], $adminerAllowedIds)) {
            $staff_panel[] = [
                'page_name' => _('Adminer'),
                'file_name' => 'view_sql.php',
                'type' => 'other',
                'av_class' => UC_MAX,
                'navbar' => 1,
            ];
            $staff_panel = array_msort($staff_panel, ['page_name' => SORT_ASC]);
        }
        if ($staff_panel) {
            foreach ($staff_panel as $key => $value) {
                if ($value['av_class'] <= $user_class && $value['type'] === 'user') {
                    $panels['0' . _('Users')][] = make_link($value);
                } elseif ($value['av_class'] <= $user_class && $value['type'] === 'settings') {
                    $panels['1' . _('Settings')][] = make_link($value);
                } elseif ($value['av_class'] <= $user_class && $value['type'] === 'stats') {
                    $panels['2' . _('Stats')][] = make_link($value);
                } elseif ($value['av_class'] <= $user_class && $value['type'] === 'other') {
                    $panels['3' . _('Other')][] = make_link($value);
                }
            }
        }
        ksort($panels);
        $values = [
            'file_name' => 'staffpanel.php',
            'page_name' => _('Staff Panel'),
        ];
        $link = make_link($values);
        foreach ($panels as $key => $value) {
            $panel .= "
                <li class='staff-wide'>
                    <a id='staff_" . strtolower(substr($key, 1)) . "' href='#' class='has-text-weight-bold'>[" . substr($key, 1) . "]</a>
                    <ul class='ddFade ddFadeFast'>{$link}" . implode('', $value) . '
                    </ul>
                </li>';
            if (substr($key, 1) === 'Settings') {
                $panel .= "
                <li class='staff-narrow'>
                    <a id='staff_" . strtolower(substr($key, 1)) . "' href='staffpanel.php' class='has-text-weight-bold'>" . _('Staff Panel') . "</a>
                    <ul class='ddFade ddFadeFast'>{$link}" . implode('', $value) . '
                    </ul>
                </li>';
            }
        }
    }

    return $panel;
}
