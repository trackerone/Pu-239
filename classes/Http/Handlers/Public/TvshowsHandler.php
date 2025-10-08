<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=90-5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Image;

final class TvshowsHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-08 via handler-convert batch=90-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $cache, $container, $fluent;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Image $images */
            $images = $container->get(Image::class);

            $user = check_user_status();
            $validSearch = [
                'sn',
                'sys',
                'sye',
                'srs',
                'sre',
            ];

            $countQuery = $fluent->from('torrents AS t')
                ->select(null)
                ->select('COUNT(t.id) AS count')
                ->where('t.category', $config->get('categories.tv'));

            $selectQuery = $fluent->from('torrents AS t')
                ->select(null)
                ->select('t.id')
                ->select('t.name')
                ->select('t.poster')
                ->select('t.imdb_id')
                ->select('t.seeders')
                ->select('t.leechers')
                ->select('t.year')
                ->select('t.rating')
                ->where('t.category', $config->get('categories.tv'))
                ->groupBy('t.imdb_id, t.id');

            if (($user['hidden'] ?? 0) === 0) {
                $countQuery->leftJoin('categories AS c ON t.category = c.id')
                    ->where('c.hidden = 0');
                $selectQuery->leftJoin('categories AS c ON t.category = c.id')
                    ->where('c.hidden = 0');
            }

            $request = $_GET;
            $selfUrl = $_SERVER['PHP_SELF'] ?? '';
            $addParam = [];
            foreach ($validSearch as $search) {
                if (!empty($request[$search])) {
                    $cleaned = searchfield($request[$search]);
                    if ($search !== 'srs' && $search !== 'sre') {
                        $addParam[] = sprintf('%s=%s', $search, urlencode($cleaned));
                    }
                }
            }

            if (!empty($request['sn'])) {
                $countQuery->where('MATCH (t.name) AGAINST (? IN NATURAL LANGUAGE MODE)', searchfield($request['sn']));
                $selectQuery->where('MATCH (t.name) AGAINST (? IN NATURAL LANGUAGE MODE)', searchfield($request['sn']));
            }
            if (!empty($request['sys'])) {
                $countQuery->where('t.year >= ?', (int) $request['sys']);
                $selectQuery->where('t.year >= ?', (int) $request['sys'])
                    ->orderBy('t.year DESC');
            }
            if (!empty($request['sye'])) {
                $countQuery->where('t.year <= ?', (int) $request['sye']);
                $selectQuery->where('t.year <= ?', (int) $request['sye'])
                    ->orderBy('t.year DESC');
            }
            if (!empty($request['srs'])) {
                // TODO(2025): Confirm legacy query param key for rating lower bound from public/tvshows.php
                $addParam[] = sprintf('%s=%s', 'srs', urlencode($request['srs']));
                $countQuery->where('t.rating >= ?', (float) $request['srs']);
                $selectQuery->where('t.rating >= ?', (float) $request['srs'])
                    ->orderBy('t.rating DESC');
            }
            if (!empty($request['sre'])) {
                // TODO(2025): Confirm legacy query param key for rating upper bound from public/tvshows.php
                $addParam[] = sprintf('%s=%s', 'sre', urlencode($request['sre']));
                $countQuery->where('t.rating <= ?', (float) $request['sre']);
                $selectQuery->where('t.rating <= ?', (float) $request['sre'])
                    ->orderBy('t.rating DESC');
            }

            $count = (int) $countQuery->fetch('count');
            $perPage = 25;
            $baseUrl = (string) $config->get('paths.baseurl');
            $querySuffix = !empty($addParam) ? '?' . implode('&amp;', $addParam) . '&amp;' : '?';
            $pager = pager($perPage, $count, sprintf('%s/tmovies.php%s', $baseUrl, $querySuffix));

            $selectQuery->limit($pager['pdo']['limit'])
                ->offset($pager['pdo']['offset'])
                ->orderBy('t.added DESC');

            $HTMLOUT = "
    <h1 class='has-text-centered top20'>" . _('TV Shows') . '</h1>';

            $body = "
        <div class='masonry padding20'>";
            foreach ($selectQuery as $torrent) {
                $cast = $cache->get('cast_' . $torrent['imdb_id']);
                if ($cast === false || $cast === null) {
                    $cast = $fluent->from('person AS p')
                        ->select(null)
                        ->select('p.name')
                        ->innerJoin('imdb_person AS i ON p.imdb_id = i.person_id')
                        ->where('i.imdb_id = ?', str_replace('tt', '', $torrent['imdb_id']))
                        ->where('i.type = "cast"')
                        ->orderBy('p.name')
                        ->limit(7)
                        ->fetchAll();
                    $cache->set('cast_' . $torrent['imdb_id'], $cast, 604800);
                }

                $casts[] = $cast;
                $people = [];
                foreach ($cast as $person) {
                    $people[] = "<div class='size_2'><a href='{$baseUrl}/browse.php?sp=" . urlencode(htmlsafechars($person['name'])) . "'>" . format_comment($person['name']) . '</a></div>';
                }

                $name = "<a href='{$baseUrl}/browse.php?si={$torrent['imdb_id']}'>" . format_comment($torrent['name']) . '</a>';
                $image = null;
                if (empty($torrent['poster'])) {
                    if (!empty($torrent['imdb_id'])) {
                        $image = $images->find_images($torrent['imdb_id'], 'poster');
                    }
                    if (!empty($image)) {
                        $image = url_proxy($image, true);
                    } else {
                        $image = $config->get('paths.images_baseurl') . 'noposter.png';
                    }
                } else {
                    $image = url_proxy($torrent['poster'], true);
                }

                $percent = (float) $torrent['rating'] * 10;
                $rating = "
                <a href='{$baseUrl}/browse.php?srs={$torrent['rating']}&amp;sre={$torrent['rating']}'>
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

                $seeders = "<a href='{$baseUrl}/peerlist.php?id={$torrent['seeders']}#seeders'>{$torrent['seeders']}</a>";
                $leechers = "<a href='{$baseUrl}/peerlist.php?id={$torrent['leechers']}#leechers'>{$torrent['leechers']}</a>";
                $year = "<a href='{$baseUrl}/browse.php?sys={$torrent['year']}&amp;sye={$torrent['year']}'>{$torrent['year']}</a>";

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
            <form id='test1' method='get' action='{$baseUrl}/tmovies.php' enctype='multipart/form-data' accept-charset='utf-8'>
                <div class='padding20'>
                    <div class='padding10 w-100'>
                        <div class='columns'>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Name') . "</div>
                                <input id='search' name='sn' type='text' placeholder='" . _('Search by Name') . "' class='search w-100' value='" . (!empty($request['sn']) ? $request['sn'] : '') . "' onkeyup='autosearch()'>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sys' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('From Year Released') . "' class='search w-100' value='" . (!empty($request['sys']) ? $request['sys'] : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sye' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('To Year Released') . "' class='search w-100' value='" . (!empty($request['sye']) ? $request['sye'] : '') . "'>
                                    </div>
                                </div>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='srs' type='number' min='0' max='10' step='0.1' placeholder='" . _('From IMDb Rating') . "' class='search w-100' value='" . (!empty($request['srs']) ? $request['srs'] : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='sre' type='number' min='0' max='10' step='0.1' placeholder='" . _('To IMDb Rating') . "' class='search w-100' value='" . (!empty($request['sre']) ? $request['sre'] : '') . "'>
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

            $HTMLOUT .= "<div class='top20'>" . ($count > $perPage ? $pager['pagertop'] : '') . main_div($body, 'top20') . ($count > $perPage ? $pager['pagertop'] : '') . '</div>';

            $title = _('Search TV Shows');
            $breadcrumbs = [
                "<a href='{$baseUrl}/browse.php'>" . _('Browse Torrents') . '</a>',
                sprintf("<a href='%s'>%s</a>", $selfUrl, $title),
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
