<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:32:58Z via handler-convert offset=250 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;

use function array_map;
use function dirname;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;

final class GetrssHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T21:32:58Z via handler-convert offset=250 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once CLASS_DIR . 'class_user_options_2.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            $stdfoot = [
                'js' => [
                    get_file_name('categories_js'),
                ],
            ];

            $user = check_user_status();
            $baseUrl = (string) $config->get('paths.baseurl');

            $self = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                $cats = [];
                if (!empty($_POST['cats']) && is_array($_POST['cats'])) {
                    $cats = array_map('intval', $_POST['cats']);
                }
                $feed = !empty($_POST['feed']) && $_POST['feed'] === 'dl' ? 'dl' : 'web';
                $bm = isset($_POST['bm']) ? (int) $_POST['bm'] : 0;

                $counts = [15, 30, 50, 100];
                $count = isset($_POST['count']) && in_array((int) $_POST['count'], $counts, true)
                    ? (int) $_POST['count']
                    : 15;

                $rsslink = sprintf(
                    '%s/rss.php?cats=%s&amp;type=%s&amp;torrent_pass=%s&amp;count=%d&amp;bm=%d',
                    $baseUrl,
                    implode(',', $cats),
                    $feed,
                    $user['torrent_pass'],
                    $count,
                    $bm,
                );

                $HTMLOUT = "
        <div class='portlet has-text-centered w-100'>
            <h1>" . _('This is your link set up according to your selected categories') . "</h1>
            <input type='text' class='w-75 margin20' readonly='readonly' value='{$rsslink}' onclick='select()'>
        </div>";

                $title = _('Generated RSS Feed');
                $breadcrumbs = [
                    "<a href='{$self}'>$title</a>",
                ];
                echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
            }

            $HTMLOUT = "
        <form action='{$self}' method='post' enctype='multipart/form-data' accept-charset='utf-8'>";
            require_once PARTIALS_DIR . 'categories.php';
            $HTMLOUT .= main_div("
        <div class='padding20'>
            <ul class='level-center'>
                <li class='has-text-centered w-25 tooltipper' title='" . _('Returns only Bookmarked Torrents') . "'>
                    <label for='bm'>" . _('Bookmarked Torrents') . "<br>
                        <select id='bm' name='bm' class='top10 w-100'>
                            <option value='0'>" . _('No') . "</option>
                            <option value='1'>" . _('Yes') . "</option>
                        </select>
                    </label>
                </li>
                <li class='has-text-centered w-25 tooltipper' title='" . _('Generate Links to download torrents or to view torrent details.') . "'>
                    <label for='feed'>" . _('RSS Link Type') . "<br>
                        <select id='feed' name='feed' class='top10 w-100'>
                            <option value='dl'>" . _('Download link') . "</option>
                            <option value='web'>" . _('Web link') . "</option>
                        </select>
                    </label>
                </li>
                <li class='has-text-centered w-25 tooltipper' title='" . _('How many results should be returned in the RSS feed?') . "'>
                    <label for='count'>" . _('Results in Feed') . "<br>
                        <select id='count' name='count' class='top10 w-100'>
                            <option value='15'>15</option>
                            <option value='30'>30</option>
                            <option value='50'>50</option>
                            <option value='100'>100</option>
                        </select>
                    </label>
                </li>
            </ul>
            <div class='level-center top20'>
                <input type='submit' class='button is-small' value='" . _('Create') . "'>
            </div>
        </div>");
            $HTMLOUT .= "
        </form>";

            $title = _('Create RSS Feed');
            $breadcrumbs = [
                "<a href='{$self}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
