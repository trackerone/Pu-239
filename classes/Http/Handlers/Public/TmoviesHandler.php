<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T19:01:07Z via handler-convert offset=305 batch=5

namespace PU239\Http\Handlers\Public;

use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Image;

use function array_unique;
use function dirname;
use function error_log;
use function implode;
use function sprintf;
use function str_replace;
use function urlencode;

final class TmoviesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T19:01:07Z via handler-convert offset=305 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);
            /** @var Image $images */
            $images = $container->get(Image::class);

            $user = check_user_status();

            $movieCategoryId = (int) $config->get('categories.movie');
            $where = ['t.category = :category'];
            $params = [
                ':category' => [$movieCategoryId, \PDO::PARAM_INT],
            ];
            $joins = '';
            if ((int) ($user['hidden'] ?? 0) === 0) {
                $joins .= ' LEFT JOIN categories AS c ON t.category = c.id';
                $where[] = 'c.hidden = 0';
            }

            $addparam = [];
            $orderings = [];

            if (!empty($_GET['sn'])) {
                $searchName = searchfield($_GET['sn']);
                $where[] = 'MATCH (t.name) AGAINST (:search_name IN NATURAL LANGUAGE MODE)';
                $params[':search_name'] = [$searchName, \PDO::PARAM_STR];
                $addparam[] = 'sn=' . urlencode($searchName);
            }

            if (!empty($_GET['sys'])) {
                $yearStart = (int) $_GET['sys'];
                $where[] = 't.year >= :year_start';
                $params[':year_start'] = [$yearStart, \PDO::PARAM_INT];
                $orderings[] = 't.year DESC';
                $addparam[] = 'sys=' . urlencode((string) $yearStart);
            }

            if (!empty($_GET['sye'])) {
                $yearEnd = (int) $_GET['sye'];
                $where[] = 't.year <= :year_end';
                $params[':year_end'] = [$yearEnd, \PDO::PARAM_INT];
                $orderings[] = 't.year DESC';
                $addparam[] = 'sye=' . urlencode((string) $yearEnd);
            }

            if (!empty($_GET['srs'])) {
                $scoreStart = (float) $_GET['srs'];
                $where[] = 't.rating >= :rating_min';
                $params[':rating_min'] = [$scoreStart, \PDO::PARAM_STR];
                $orderings[] = 't.rating DESC';
                $addparam[] = 'srs=' . urlencode((string) $scoreStart);
            }

            if (!empty($_GET['sre'])) {
                $scoreEnd = (float) $_GET['sre'];
                $where[] = 't.rating <= :rating_max';
                $params[':rating_max'] = [$scoreEnd, \PDO::PARAM_STR];
                $orderings[] = 't.rating DESC';
                $addparam[] = 'sre=' . urlencode((string) $scoreEnd);
            }

            $countSql = 'SELECT COUNT(DISTINCT t.id) FROM torrents AS t' . $joins;
            if ($where !== []) {
                $countSql .= ' WHERE ' . implode(' AND ', $where);
            }

            $count = (int) ($db->fetchValue($countSql, $params) ?? 0);

            $perpage = 25;
            $baseUrl = (string) $config->get('paths.baseurl');
            $querySuffix = $addparam === [] ? '?' : '?' . implode('&amp;', $addparam) . '&amp;';
            $pager = pager($perpage, $count, sprintf('%s/tmovies.php%s', $baseUrl, $querySuffix));

            $selectSql = 'SELECT t.id, t.name, t.poster, t.imdb_id, t.seeders, t.leechers, t.year, t.rating'
                . ' FROM torrents AS t'
                . $joins;
            if ($where !== []) {
                $selectSql .= ' WHERE ' . implode(' AND ', $where);
            }
            $selectSql .= ' GROUP BY t.imdb_id, t.id';

            $orderings[] = 't.added DESC';
            $orderings = array_values(array_unique($orderings));
            if ($orderings !== []) {
                $selectSql .= ' ORDER BY ' . implode(', ', $orderings);
            }
            $selectSql .= ' LIMIT :limit OFFSET :offset';

            $queryParams = $params;
            $queryParams[':limit'] = [$pager['pdo']['limit'], \PDO::PARAM_INT];
            $queryParams[':offset'] = [$pager['pdo']['offset'], \PDO::PARAM_INT];

            $torrents = $db->fetchAll($selectSql, $queryParams);

            $htmlOut = "    <h1 class='has-text-centered top20'>" . _('Movies') . '</h1>';
            $body = "        <div class='masonry padding20'>";

            foreach ($torrents as $torrent) {
                $imdbId = (string) ($torrent['imdb_id'] ?? '');
                $castKey = 'cast_' . $imdbId;
                $cast = $cache->get($castKey);
                if ($cast === false || $cast === null) {
                    $cast = $db->fetchAll(
                        'SELECT p.name FROM person AS p INNER JOIN imdb_person AS i ON p.imdb_id = i.person_id'
                        . ' WHERE i.imdb_id = :imdb_id AND i.type = "cast" ORDER BY p.name LIMIT 7',
                        [
                            ':imdb_id' => [str_replace('tt', '', $imdbId), \PDO::PARAM_STR],
                        ],
                    );
                    $cache->set($castKey, $cast, 604800);
                }

                $people = [];
                foreach ($cast as $person) {
                    $personName = (string) ($person['name'] ?? '');
                    $people[] = "<div class='size_2'><a href='{$baseUrl}/browse.php?sp="
                        . urlencode(htmlsafechars($personName))
                        . "'>"
                        . format_comment($personName)
                        . '</a></div>';
                }

                $nameLink = "<a href='{$baseUrl}/browse.php?si={$imdbId}'>" . format_comment((string) $torrent['name']) . '</a>';

                $poster = (string) ($torrent['poster'] ?? '');
                if ($poster === '' && $imdbId !== '') {
                    $poster = (string) $images->find_images($imdbId, 'poster');
                }
                if ($poster === '') {
                    $poster = (string) $config->get('paths.images_baseurl') . 'noposter.png';
                } else {
                    $poster = url_proxy($poster, true);
                }

                $ratingPercent = (float) ($torrent['rating'] ?? 0) * 10;
                $ratingBlock = "
                <a href='{$baseUrl}/browse.php?srs=" . ($torrent['rating'] ?? 0) . "&amp;sre=" . ($torrent['rating'] ?? 0) . "'>
                    <div>
                        <div class='level-left size_3'>
                            <div class='right5'>{$ratingPercent}%</div>
                            <div class='star-ratings-css'>
                                <div class='star-ratings-css-top' style='width: {$ratingPercent}%'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                                <div class='star-ratings-css-bottom'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                            </div>
                        </div>
                    </div>
                </a>";

                $seedersLink = "<a href='{$baseUrl}/peerlist.php?id={$torrent['seeders']}#seeders'>{$torrent['seeders']}</a>";
                $leechersLink = "<a href='{$baseUrl}/peerlist.php?id={$torrent['leechers']}#leechers'>{$torrent['leechers']}</a>";
                $yearLink = "<a href='{$baseUrl}/browse.php?sys={$torrent['year']}&amp;sye={$torrent['year']}'>{$torrent['year']}</a>";

                $body .= "
                <div class='masonry-item padding10 bg-04 round10'>
                    <div class='columns'>
                        <div class='column'>
                            <img src='{$poster}' alt='" . htmlsafechars((string) $torrent['name']) . "'>
                        </div>
                        <div class='column'>
                            <div class='has-text-left size_4 torrent-name'>{$nameLink} <span class='size_2'>({$yearLink})</span></div>
                            {$ratingBlock}
                            <div class='size_2'>
                                <span class='has-text-primary'>" . _('Peers') . ":</span>
                                <span class='has-text-primary'> {$seedersLink} / {$leechersLink}</span>
                            </div>" . ($people === [] ? '' : '\n' . implode("\n", $people)) . '
                        </div>
                    </div>
                </div>';
            }

            $body .= '
        </div>';

            $form = "
            <form id='test1' method='get' action='{$baseUrl}/tmovies.php' enctype='multipart/form-data' accept-charset='utf-8'>
                <div class='padding20'>
                    <div class='padding10 w-100'>
                        <div class='columns'>
                            <div class='column'>
                                <div class='has-text-centered bottom10'>" . _('Name') . "</div>
                                <input id='search' name='sn' type='text' placeholder='" . _('Search by Name') . "' class='search w-100' value='" . (!empty($_GET['sn']) ? htmlsafechars((string) $_GET['sn']) : '') . "' onkeyup='autosearch()'>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sys' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('From Year Released') . "' class='search w-100' value='" . (!empty($_GET['sys']) ? htmlsafechars((string) $_GET['sys']) : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Year') . "</div>
                                        <input name='sye' type='number' min='1900' max='" . (date('Y') + 1) . "' placeholder='" . _('To Year Released') . "' class='search w-100' value='" . (!empty($_GET['sye']) ? htmlsafechars((string) $_GET['sye']) : '') . "'>
                                    </div>
                                </div>
                            </div>
                            <div class='column'>
                                <div class='columns'>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='srs' type='number' min='0' max='10' step='0.1' placeholder='" . _('From IMDb Rating') . "' class='search w-100' value='" . (!empty($_GET['srs']) ? htmlsafechars((string) $_GET['srs']) : '') . "'>
                                    </div>
                                    <div class='column'>
                                        <div class='has-text-centered bottom10'>" . _('Rating') . "</div>
                                        <input name='sre' type='number' min='0' max='10' step='0.1' placeholder='" . _('To IMDb Rating') . "' class='search w-100' value='" . (!empty($_GET['sre']) ? htmlsafechars((string) $_GET['sre']) : '') . "'>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='margin10 has-text-centered'>
                        <input type='submit' value='" . _('Search!') . "' class='button is-small'>
                    </div>
                </div>
            </form>";

            $pagerHtml = $count > $perpage ? $pager['pagertop'] : '';
            $htmlOut .= $form;
            $htmlOut .= "<div class='top20'>" . $pagerHtml . main_div($body, 'top20') . $pagerHtml . '</div>';

            $title = _('Search Movies');
            $breadcrumbs = [
                "<a href='{$baseUrl}/browse.php'>" . _('Browse Torrents') . '</a>',
                sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title),
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
