<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use Pu239\Database;
use Pu239\Image;

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_pager.php';
require_once INCL_DIR . 'function_html.php';
$user = check_user_status();
$valid_search = [
    'sn',
    'sys',
    'sye',
    'srs',
    'sre',
];
global $container, $site_config;

$fluent = $container->get(Database::class);
$count = $fluent$sql = "SELECT * FROM 'torrents AS t'"; $this->db->fetchAll($sql);;
        $cache->set('cast_' . $torrent['imdb_id'], $cast, 604800);
    }

    $casts[] = $cast;
    $people = [];
    foreach ($cast as $person) {
        $people[] = "<div class='size_2'><a href='{$site_config['paths']['baseurl']}/browse.php?sp=" . urlencode(htmlsafechars($person['name'])) . "'>" . format_comment($person['name']) . '</a></div>';
    }

    $name = "<a href='{$site_config['paths']['baseurl']}/browse.php?si={$torrent['imdb_id']}'>" . format_comment($torrent['name']) . '</a>';
    if (empty($torrent['poster'])) {
        if (!empty($torrent['imdb_id'])) {
            $image = $images_class->find_images($torrent['imdb_id'], 'poster');
        }
        if (!empty($image)) {
            $image = url_proxy($image, true);
        } else {
            $image = $site_config['paths']['images_baseurl'] . 'noposter.png';
        }
    } else {
        $image = url_proxy($torrent['poster'], true);
    }
    $percent = $torrent['rating'] * 10;
    $rating = "
                <a href='{$site_config['paths']['baseurl']}/browse.php?srs={$torrent['rating']}&amp;sre={$torrent['rating']}'>
                    <div>
                        <div class='level-left size_3'>
                            <div class='right5'>{$percent}%</div>
                            <div class='star-ratings-css'>
                                <div class='star-ratings-css-top' style='width: {$percent}%'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                                <div class='star-ratings-css-bottom'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                            </div>
                        </div>
                    </div>
                </a>";

    $seeders = "<a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$torrent['seeders']}#seeders'>{$torrent['seeders']}</a>";
    $leechers = "<a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$torrent['leechers']}#leechers'>{$torrent['leechers']}</a>";
    $year = "<a href='{$site_config['paths']['baseurl']}/browse.php?sys={$torrent['year']}&amp;sye={$torrent['year']}'>{$torrent['year']}</a>";
    $body .= "
                <div class='masonry-item padding10 bg-04 round10'>
                    <div class='columns'>
                        <div class='column'>
                            <img src='{$image}' alt='" . htmlsafechars($torrent['name']) . "'>
                        </div>
                        <div class='column'>
                            <div class='has-text-left size_4 torrent-name'>$name <span class='size_2'>({$year})</span></div>
                            $rating
                            <div class='size_2'>
                                <span class='has-text-primary'>" . _('Peers') . ":</span>
                                <span class='has-text-primary'> {$seeders} / {$leechers}</span>
                            </div>" . implode("\n", $people) . '
                        </div>
                    </div>
                </div>';
}
$body .= '
        </div>';

$HTMLOUT .= main_div("
            <form id='test1' method='get' action='{$site_config['paths']['baseurl']}/tmovies.php' enctype='multipart/form-data' accept-charset='utf-8'>
                <div class='padding20'>
                    <div class='padding10 w-100'>
                        <div class='columns'>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Name') . "</div>
                                <input id='search' name='sn' type='text' placeholder='" . _('Search by Name') . "' class='search w-100' value='" . (!empty($_GET['sn']) ? $_GET['sn'] : '') . "' onkeyup='autosearch()'>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sys' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('From Year Released') . "' class='search w-100' value='" . (!empty($_GET['sys']) ? $_GET['sys'] : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sye' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('To Year Released') . "' class='search w-100' value='" . (!empty($_GET['sye']) ? $_GET['sye'] : '') . "'>
                                    </div>
                                </div>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='srs' type='number' min='0' max='10' step='0.1' placeholder='" . _('From IMDb Rating') . "' class='search w-100' value='" . (!empty($_GET['srs']) ? $_GET['srs'] : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='sre' type='number' min='0' max='10' step='0.1' placeholder='" . _('To IMDb Rating') . "' class='search w-100' value='" . (!empty($_GET['sre']) ? $_GET['sre'] : '') . "'>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='margin10 has-text-centered'>
                        <input type='submit' value='" . _('Search!') . "' class='button is-small'>
                    </div>
                </div>
            </form>");

$HTMLOUT .= "<div class='top20'>" . ($count > $perpage ? $pager['pagertop'] : '') . main_div($body, 'top20') . ($count > $perpage ? $pager['pagertop'] : '') . '</div>';

$title = _('Search TV Shows');
$breadcrumbs = [
    "<a href='{$site_config['paths']['baseurl']}/browse.php'>" . _('Browse Torrents') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
