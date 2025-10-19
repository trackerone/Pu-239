<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:30:40Z via handler-convert (offset=270 batch=5)

namespace PU239\Http\Handlers\PublicSite;

use DateTime;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use RuntimeException;

final class MoviesHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:30:40Z via handler-convert (offset=270 batch=5)
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new RuntimeException('Global container not initialized');
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $user = check_user_status();
            $baseUrl = (string) $config->get('paths.baseurl');
            $imagesBaseUrl = (string) $config->get('paths.images_baseurl');
            $defaultUse12Hour = (bool) $config->get('site.use_12_hour');

            $lists = [
                'upcoming',
                'tmdb_top_movies',
                'tmdb_theaters',
                'tv',
                'tvmaze',
                'bluray',
                'imdb_top_movies',
                'imdb_top_oscar',
                'imdb_top_tv',
                'imdb_top_anime',
                'imdb_theaters',
            ];
            $list = 'upcoming';
            $title = _('Poster Views');
            $countRaw = $cache->get('item_count_');
            $count = is_int($countRaw) ? $countRaw : (int) $countRaw;
            if ($count >= 100) {
                $count = 100;
            }
            if (!empty($_GET['list']) && in_array((string) $_GET['list'], $lists, true)) {
                $list = (string) $_GET['list'];
            }

            $generateHtml = static function (array $data) use ($baseUrl): string {
                $html = "
     <div class='masonry-item-clean padding10 bg-04 round10'>
        <div class='dt-tooltipper-large has-text-centered vertical_spread h-100' data-tooltip-content='#movie_{$data['id']}_tooltip'>
            <a href='{$baseUrl}browse.php?sna=" . urlencode($data['title'] ?? '') . "'>
                <img src='{$data['placeholder']}' data-src='{$data['poster']}' alt='Poster' class='lazy tooltip-poster'>
            </a>
            <div class='has-text-centered top10'>
                <div>{$data['title']}</div>";

                if (!empty($data['airtime'])) {
                    $html .= "
                <div class='has-text-centered top10'>{$data['airtime']}</div>";
                }
                if (!empty($data['release_date'])) {
                    $html .= "
                <div class='has-text-centered'>{$data['release_date']}</div>";
                }
                $html .= "
        </div>
            <div class='tooltip_templates'>
                <div id='movie_{$data['id']}_tooltip' class='round10 tooltip-background' " . (!empty($data['backdrop']) ? "style='background-image: url({$data['backdrop']});'" : '') . ">
                    <div class='columns is-marginless is-paddingless'>
                        <div class='column padding10 is-4'>
                            <span>
                                <img src='{$data['placeholder']}' data-src='{$data['poster']}' alt='Poster' class='lazy tooltip-poster'>
                            </span>
                        </div>
                        <div class='column padding10 is-8'>
                            <div class='padding20 is-8 bg-09 round10 h-100'>
                                <div class='columns is-multiline'>";

                $rows = [
                    ['Title', $data['title'] ?? null, static fn($value): string => format_comment((string) $value)],
                    ['Episode Title', $data['ep_title'] ?? null, static fn($value): string => format_comment((string) $value)],
                    ['Season', $data['season'] ?? null, static fn($value): string => (string) (int) $value],
                    ['Episode', $data['episode'] ?? null, static fn($value): string => (string) (int) $value],
                    ['Runtime', $data['runtime'] ?? null, static fn($value): string => format_comment((string) $value)],
                    ['Type', $data['type'] ?? null, static fn($value): string => format_comment((string) $value)],
                    ['Release Date', $data['release_date'] ?? null, static fn($value): string => format_comment((string) $value)],
                    ['Popularity', $data['popularity'] ?? null, static fn($value): string => (string) (int) $value],
                    ['Votes', $data['vote_average'] ?? null, static fn($value): string => (string) (int) $value],
                    ['Overview', $data['overview'] ?? null, static fn($value): string => format_comment((string) $value)],
                ];

                foreach ($rows as [$label, $value, $formatter]) {
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>" . _($label) . ": </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . $formatter($value) . '</span>
                                    </div>';
                }

                $html .= '
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

                return $html;
            };

            $HTMLOUT = '';
            $tvmazeData = null;

            switch ($list) {
                case 'bluray':
                    $title = _('Bluray Releases');
                    $pubs = $cache->get('bluray_pubs_');
                    if (is_array($pubs)) {
                        $div = "
        <div class='masonry padding20'>";
                        foreach ($pubs as $data) {
                            if (is_array($data)) {
                                $div .= $generateHtml($data);
                            }
                        }
                        $div .= '
        </div>';
                        $div = main_div($div);
                    } else {
                        $div = main_div("<p class='has-text-centered'>" . _('Blu-ray.com may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }
                    $HTMLOUT = "
        <h1 class='has-text-centered'>$title</h1>" . $div;

                    break;

                case 'tvmaze':
                    $title = _('TV Schedule');
                    $data = $cache->get('tvmaze_schedule_');
                    if ($data !== false) {
                        $json = is_string($data) ? bzdecompress($data) : '';
                        $tvmazeData = is_string($json) ? json_decode($json, true) : null;
                    }
                    if (is_array($tvmazeData)) {
                        $today = date('Y-m-d');
                        $shows = [];
                        $titles = [];
                        foreach ($tvmazeData as $listing) {
                            if (
                                !empty($listing['airstamp']) &&
                                !empty($listing['airdate']) &&
                                $listing['airdate'] === $today &&
                                ($listing['_embedded']['show']['language'] ?? null) === 'English'
                            ) {
                                $name = $listing['_embedded']['show']['name'] ?? '';
                                if ($name === '') {
                                    continue;
                                }
                                if (in_array(strtolower($name), $titles, true)) {
                                    continue;
                                }

                                $poster = $listing['image']['original'] ?? ($listing['_embedded']['show']['image']['original'] ?? $imagesBaseUrl . 'noposter.png');
                                $airtime = strtotime((string) $listing['airstamp']);
                                $use12Hour = !empty($user['use_12_hour']) ? (bool) $user['use_12_hour'] : $defaultUse12Hour;
                                $shows[] = [
                                    'poster' => url_proxy($poster, true, 250),
                                    'placeholder' => url_proxy($poster, true, 250, null, 20),
                                    'title' => $name,
                                    'ep_title' => $listing['name'] ?? '',
                                    'season' => $listing['season'] ?? null,
                                    'episode' => $listing['number'] ?? null,
                                    'runtime' => !empty($listing['runtime']) ? "{$listing['runtime']} minutes" : '',
                                    'type' => $listing['_embedded']['show']['type'] ?? '',
                                    'airtime' => !empty($listing['airtime']) ? get_date((int) $airtime, $use12Hour ? 'WITHOUT_SEC' : 'WITHOUT_SEC', 1, 0) : '',
                                    'id' => $listing['_embedded']['show']['id'] ?? 0,
                                    'overview' => str_replace([
                                        '<p>',
                                        '</p>',
                                        '<b>',
                                        '</b>',
                                        '<i>',
                                        '</i>',
                                    ], '', $listing['_embedded']['show']['summary'] ?? ''),
                                ];
                                $titles[] = strtolower($name);
                            }
                        }

                        if ($shows !== []) {
                            usort($shows, 'timeSort');
                            $div = "
        <h1 class='has-text-centered'>" . _('TVMaze TV Today') . "</h1>
        <div class='masonry padding20'>";
                            foreach ($shows as $data) {
                                $div .= $generateHtml($data);
                            }
                            $div .= '
        </div>';

                            $HTMLOUT = main_div($div);
                        }
                    }

                    if ($HTMLOUT === '') {
                        $HTMLOUT = "
        <h1 class='has-text-centered'>" . _('TVMaze TV Today') . '</h1>' . main_div("<p class='has-text-centered'>" . _('TVMaze may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }

                    break;

                case 'tv':
                    $title = _('TV Schedule');
                    $base = date('Y-m-d');
                    $today = !empty($_GET['date']) ? (string) $_GET['date'] : $base;
                    $date = new DateTime($today);
                    $yesterday = $date->modify('-1 day')->format('Y-m-d');
                    $date = new DateTime($today);
                    $tomorrow = $date->modify('+1 day')->format('Y-m-d');
                    $date = new DateTime($today);
                    $display = $date->format('l Y-m-d');

                    $HTMLOUT = "
    <h1 class='has-text-centered'>" . _('TV Airing By Date') . "</h1>
    <div class='level-center top20'>
        <a href='{$_SERVER['PHP_SELF']}?list=tv&amp;date={$yesterday}' class='tooltipper' title='{$yesterday}'>{$yesterday}</a>
        <a href='{$_SERVER['PHP_SELF']}?list=tv&amp;date={$base}' class='tooltipper' title='GoTo {$base}'><h2>{$display}</h2></a>
        <a href='{$_SERVER['PHP_SELF']}?list=tv&amp;date={$tomorrow}' class='tooltipper' title='{$tomorrow}'>{$tomorrow}</a>
    </div>";
                    $tvs = $cache->get('tmdb_tv_' . $today);
                    if (is_array($tvs)) {
                        $titles = [];
                        $body = [];
                        foreach ($tvs as $tv) {
                            $name = $tv['name'] ?? '';
                            if ($name === '' || in_array(strtolower($name), $titles, true)) {
                                continue;
                            }
                            $imdbId = get_imdbid($tv['id'] ?? 0);
                            $posterPath = $tv['poster_path'] ?? null;
                            $poster = $posterPath ? "https://image.tmdb.org/t/p/original{$posterPath}" : $imagesBaseUrl . 'noposter.png';
                            $backdropPath = $tv['backdrop_path'] ?? null;
                            $backdrop = $backdropPath ? "https://image.tmdb.org/t/p/original{$backdropPath}" : '';

                            $body[] = [
                                'poster' => url_proxy($poster, true, 250),
                                'placeholder' => url_proxy($poster, true, 250, null, 20),
                                'backdrop' => url_proxy($backdrop, true),
                                'title' => $name,
                                'vote_count' => $tv['vote_count'] ?? null,
                                'id' => $tv['id'] ?? 0,
                                'vote_average' => $tv['vote_average'] ?? null,
                                'popularity' => $tv['popularity'] ?? null,
                                'overview' => $tv['overview'] ?? '',
                            ];
                            $titles[] = strtolower($name);
                        }

                        $div = "
        <div class='masonry padding20'>";
                        foreach ($body as $data) {
                            $div .= $generateHtml($data);
                        }
                        $div .= '
        </div>';

                        $HTMLOUT .= main_div($div);
                    } else {
                        $HTMLOUT = "
        <h1 class='has-text-centered'>" . _('TMBb TV Airing By Date') . '</h1>' . main_div("<p class='has-text-centered'>" . _('TMDb may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }

                    break;

                case 'tmdb_theaters':
                    $title = _('TMDb In Theaters');
                    $HTMLOUT = "
    <h1 class='has-text-centered'>$title</h1>";
                    $movies = $cache->get('tmdb_movies_in_theaters_');
                    if (is_array($movies)) {
                        $body = "
        <div class='masonry padding20'>";
                        foreach ($movies as $movie) {
                            if (!is_array($movie)) {
                                continue;
                            }
                            $imdbId = get_imdbid($movie['id'] ?? 0);
                            $movieHtml = $imdbId ? get_imdb_info_short($imdbId) : '';
                            if (!empty($movieHtml)) {
                                $body .= $movieHtml;
                            }
                        }
                        $body .= '
        </div>';

                        $HTMLOUT .= main_div($body);
                    } else {
                        $HTMLOUT = "
        <h1 class='has-text-centered'>$title</h1>" . main_div("<p class='has-text-centered'>" . _('TMDb may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }

                    break;

                case 'imdb_theaters':
                    $title = _('IMDb In Theaters');
                    $HTMLOUT = "
    <h1 class='has-text-centered'>{$title}</h1>";
                    $movies = $cache->get('imdb_in_theaters_display_');
                    if (is_array($movies)) {
                        $body = "
        <div class='masonry padding20'>";
                        foreach ($movies as $imdbId) {
                            $movieHtml = get_imdb_info_short($imdbId);
                            if (!empty($movieHtml)) {
                                $body .= $movieHtml;
                            }
                        }
                        $body .= '
        </div>';

                        $HTMLOUT .= main_div($body);
                    } else {
                        $HTMLOUT = "
        <h1 class='has-text-centered'>$title</h1>" . main_div("<p class='has-text-centered'>" . _('IMDb may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }

                    break;

                case 'imdb_top_movies':
                case 'imdb_top_oscar':
                case 'imdb_top_tv':
                case 'imdb_top_anime':
                    $cacheKey = [
                        'imdb_top_movies' => 'imdb_top_movies_',
                        'imdb_top_oscar' => 'imdb_oscar_winners_',
                        'imdb_top_tv' => 'imdb_top_tvshows_',
                        'imdb_top_anime' => 'imdb_top_anime_',
                    ][$list];
                    $movies = $cache->get($cacheKey . $count);
                    $actualCount = is_array($movies) ? count($movies) : $count;
                    $title = _('IMDb Top ' . $actualCount . ' ' . trim(str_replace('_', ' ', substr($list, strlen('imdb_top_')))));
                    $HTMLOUT = "
    <h1 class='has-text-centered'>{$title}</h1>";
                    if (is_array($movies)) {
                        $body = "
        <div class='masonry padding20'>";
                        foreach ($movies as $imdbId) {
                            $movieHtml = get_imdb_info_short($imdbId);
                            if (!empty($movieHtml)) {
                                $body .= $movieHtml;
                            }
                        }
                        $body .= '
        </div>';

                        $HTMLOUT .= main_div($body);
                    } else {
                        $HTMLOUT = "
        <h1 class='has-text-centered'>$title</h1>" . main_div("<p class='has-text-centered'>" . _('IMDb may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }

                    break;

                case 'tmdb_top_movies':
                    $movies = $cache->get('tmdb_movies_vote_average_' . $count);
                    $actualCount = is_array($movies) ? count($movies) : $count;
                    $title = _('TMDb Top ' . $actualCount . ' Movies');
                    $HTMLOUT = "
    <h1 class='has-text-centered'>{$title}</h1>";
                    if (is_array($movies)) {
                        $body = "
        <div class='masonry padding20'>";
                        foreach ($movies as $movie) {
                            if (!is_array($movie)) {
                                continue;
                            }
                            $imdbId = get_imdbid($movie['id'] ?? 0);
                            if (!$imdbId) {
                                continue;
                            }
                            $movieHtml = get_imdb_info_short($imdbId);
                            if (!empty($movieHtml)) {
                                $body .= $movieHtml;
                            }
                        }
                        $body .= '
        </div>';

                        $HTMLOUT .= main_div($body);
                    } else {
                        $HTMLOUT = "
        <h1 class='has-text-centered'>$title</h1>" . main_div("<p class='has-text-centered'>" . _('TMDb may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }

                    break;

                case 'upcoming':
                default:
                    $title = _('IMDb Upcoming Movies');
                    $HTMLOUT = '';
                    $imdbs = $cache->get('imdb_upcoming_movies_');
                    if (is_array($imdbs)) {
                        foreach ($imdbs as $key => $imdbList) {
                            if (!is_array($imdbList)) {
                                continue;
                            }
                            $body = "
        <h1 class='has-text-centered'>$title $key</h1>";
                            $body .= "
        <div class='masonry padding20'>";
                            foreach ($imdbList as $item) {
                                $movieHtml = get_imdb_info_short($item);
                                if (!empty($movieHtml)) {
                                    $body .= $movieHtml;
                                }
                            }
                            $body .= '
        </div>';
                            $HTMLOUT .= main_div($body);
                        }
                    } else {
                        $HTMLOUT = "
        <h1 class='has-text-centered'>$title</h1>" . main_div("<p class='has-text-centered'>" . _('IMDb may be down or caching queue is incomplete, check back later') . '</p>', '', 'padding20');
                    }

                    break;
            }

            $breadcrumbs = [
                "<a href='{$baseUrl}/browse.php'>" . _('Browse Torrents') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
