<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:27:35Z via handler_convert (batch=225-229)

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class NeedseedHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:27:35Z via handler_convert (batch=225-229)
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();
            $HTMLOUT = '';
            $baseurl = (string) $config->get('paths.baseurl');
            $imagesBaseurl = (string) $config->get('paths.images_baseurl');

            $possibleActions = [
                'leechers',
                'seeders',
            ];

            $needed = isset($_GET['needed']) && !is_array($_GET['needed']) ? htmlsafechars($_GET['needed']) : 'seeders';
            if (!in_array($needed, $possibleActions, true)) {
                stderr(_('Error'), _('Invalid action'));
            }

            $categories = genrelist(false);
            $change = [];
            foreach ($categories as $value) {
                $change[$value['id']] = [
                    'id' => $value['id'],
                    'name' => $value['name'],
                    'image' => $value['image'],
                ];
            }

            if ($needed === 'leechers') {
                $HTMLOUT .= "        <div class='padding20'>
            <ul class='tabs'>
                <li>
                    <a href='#' class='active is-link'>" . _('Seeders in need') . "</a>
                </li>
                <li>
                    <a href='{$baseurl}/needseed.php?needed=seeders' class='is-link'>" . _('Torrents Needing Seeds') . '</a>
                </li>
            </ul>
        </div>';

                $duration = TIME_NOW - (86400 * 7);
                $sql = 'SELECT p.id, p.userid, p.torrent, u.username, u.uploaded, u.downloaded, t.name, t.seeders, t.leechers, t.category
                        FROM peers AS p
                        LEFT JOIN users AS u ON p.userid = u.id
                        LEFT JOIN torrents AS t ON p.torrent = t.id
                        LEFT JOIN categories AS c ON t.category = c.id
                        WHERE p.seeder = :seeder AND u.downloaded > :downloaded AND u.registered < :registered';
                $params = [
                    ':seeder' => 'yes',
                    ':downloaded' => [1024, \PDO::PARAM_INT],
                    ':registered' => [$duration, \PDO::PARAM_INT],
                ];
                if ((int) ($user['hidden'] ?? 0) === 0) {
                    $sql .= ' AND c.hidden = 0';
                }
                $sql .= ' ORDER BY u.uploaded / NULLIF(u.downloaded, 0)';

                $rows = $db->fetchAll($sql, $params);
                if ($rows !== []) {
                    $header = '                <tr>
                    <th>' . _('User') . '</th>
                    <th>' . _('Torrent') . '</th>
                    <th>' . _('Category') . '</th>
                    <th>' . _('Peers') . '</th>
                </tr>';
                    $body = '';
                    foreach ($rows as $row) {
                        $torrentId = (int) ($row['torrent'] ?? 0);
                        $userId = (int) ($row['userid'] ?? 0);
                        $categoryId = (int) ($row['category'] ?? 0);
                        $categoryInfo = $change[$categoryId] ?? ['name' => '', 'image' => ''];
                        $categoryName = htmlsafechars((string) ($categoryInfo['name'] ?? ''));
                        $categoryImage = htmlsafechars((string) ($categoryInfo['image'] ?? ''));
                        if ($categoryImage !== '') {
                            $cat = "<img src='{$imagesBaseurl}caticons/" . get_category_icons() . "/{$categoryImage}' alt='{$categoryName}' title='{$categoryName}' class='tooltipper'>";
                        } else {
                            $cat = $categoryName;
                        }
                        $torrentName = format_comment(CutName((string) ($row['name'] ?? ''), 80));
                        $peerSummary = (int) ($row['seeders'] ?? 0) . ' seeder' . ((int) ($row['seeders'] ?? 0) > 1 ? 's' : '') . ', ' . (int) ($row['leechers'] ?? 0) . ' leecher' . ((int) ($row['leechers'] ?? 0) > 1 ? 's' : '');
                        $body .= "                <tr>\n"
                            . "                    <td>" . format_username($userId) . ' (' . member_ratio((float) ($row['uploaded'] ?? 0), (float) ($row['downloaded'] ?? 0)) . ")</td>\n"
                            . "                    <td><a href='{$baseurl}/details.php?id={$torrentId}' title='{$torrentName}' class='tooltipper'>{$torrentName}</a></td>\n"
                            . "                    <td>{$cat}</td>\n"
                            . "                    <td>{$peerSummary}</td>\n"
                            . "                </tr>";
                    }
                    $HTMLOUT .= main_table($body, $header);
                } else {
                    $HTMLOUT .= main_div("<div class='padding20'>" . _('There are no torrents needing leechers right now.') . '</div>');
                }

                $title = _('Leechers in Need');
                $breadcrumbs = [
                    "<a href='{$baseurl}/browse.php'>" . _('Browse Torrents') . '</a>',
                    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                ];
                echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
            } else {
                $HTMLOUT .= "        <div class='padding20'>
            <ul class='tabs'>
                <li>
                    <a href='{$baseurl}/needseed.php?needed=leechers'  class='is-link'>" . _('Seeders in need') . "</a>
                </li>
                <li>
                    <a href='#' class='active is-link'>" . _('Torrents Needing Seeds') . '</a>
                </li>
            </ul>
        </div>';
                $sql = 'SELECT t.id, t.name, t.seeders, t.leechers, t.added, t.category
                        FROM torrents AS t';
                if ((int) ($user['hidden'] ?? 0) === 0) {
                    $sql .= ' LEFT JOIN categories AS c ON t.category = c.id';
                }
                $sql .= ' WHERE t.leechers >= 0 AND t.seeders = 0';
                if ((int) ($user['hidden'] ?? 0) === 0) {
                    $sql .= ' AND c.hidden = 0';
                }
                $sql .= ' ORDER BY t.leechers DESC LIMIT 20';

                $rows = $db->fetchAll($sql);
                if ($rows !== []) {
                    $header = "                <tr>\n"
                        . "                    <th class='has-text-centered'>" . _('Category') . "</th>\n"
                        . "                    <th>" . _('Name') . "</th>\n"
                        . "                    <th class='has-text-centered'>" . _('Seeders') . "</th>\n"
                        . "                    <th class='has-text-centered'>" . _('Leechers') . "</th>\n"
                        . "                </tr>";
                    $body = '';
                    foreach ($rows as $row) {
                        $categoryId = (int) ($row['category'] ?? 0);
                        $categoryInfo = $change[$categoryId] ?? ['name' => '', 'image' => ''];
                        $categoryName = htmlsafechars((string) ($categoryInfo['name'] ?? ''));
                        $categoryImage = htmlsafechars((string) ($categoryInfo['image'] ?? ''));
                        if ($categoryImage !== '') {
                            $cat = "<img src='{$imagesBaseurl}caticons/" . get_category_icons() . "/{$categoryImage}' alt='{$categoryName}' title='{$categoryName}' class='tooltipper'>";
                        } else {
                            $cat = $categoryName;
                        }
                        $torrentId = (int) ($row['id'] ?? 0);
                        $torrentName = format_comment(CutName((string) ($row['name'] ?? ''), 80));
                        $seeders = (int) ($row['seeders'] ?? 0);
                        $leechers = (int) ($row['leechers'] ?? 0);
                        $body .= "                <tr>\n"
                            . "                    <td class='has-text-centered'>{$cat}</td>\n"
                            . "                    <td><a href='{$baseurl}/details.php?id={$torrentId}&amp;hit=1' title='{$torrentName}' class='tooltipper'>{$torrentName}</a></td>\n"
                            . "                    <td class='has-text-centered'><span>{$seeders}</span></td>\n"
                            . "                    <td class='has-text-centered'>{$leechers}</td>\n"
                            . "                </tr>";
                    }
                    $HTMLOUT .= main_table($body, $header);
                } else {
                    $HTMLOUT .= main_div("<div class='padding20'>" . _('There are no torrents needing seeds right now.') . '</div>');
                }

                $title = _('Seeders in Need');
                $breadcrumbs = [
                    "<a href='{$baseurl}/browse.php'>" . _('Browse Torrents') . '</a>',
                    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
                ];
                echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
            }
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
