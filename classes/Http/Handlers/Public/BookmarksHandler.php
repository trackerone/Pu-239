<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=70-5

namespace PU239\Http\Handlers\Public;

use Pu239\Config\ConfigRepository;
use Pu239\Database;

final class BookmarksHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-07 via handler-convert batch=70-5
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            if (!defined('PU239_ROUTED')) {
                require_once \dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $user = check_user_status();

            $stdfoot = [
                'js' => [
                    get_file_name('bookmarks_js'),
                ],
            ];

            $htmlOut = '';

            $userId = isset($_GET['id']) ? (int) $_GET['id'] : (int) $user['id'];
            if (!is_valid_id($userId)) {
                stderr(_('Error'), _('Invalid ID'));
            }

            if ($userId !== (int) $user['id']) {
                stderr(
                    _('Error'),
                    _('Access denied. Try ') . "<a href='{$config->get('paths.baseurl')}/sharemarks.php?id={$userId}'>" . _('Here') . '</a>'
                );
            }

            $htmlOut .= '
    <div class="has-text-centered bottom20">
        <h1>' . _('My Bookmarks') . '</h1>
        <div class="tabs is-centered">
            <ul>
                <li><a href="' . $config->get('paths.baseurl') . '/sharemarks.php?id=' . $userId . '" class="is-link">' . _('My Sharemarks') . '</a></li>
            </ul>
        </div>
    </div>';

            $count = (int) ($db->fetchValue(
                'SELECT COUNT(id) FROM bookmarks WHERE userid = :userid',
                ['userid' => $userId],
            ) ?? 0);

            $torrentsPerPage = (int) ($user['torrentsperpage'] ?? 25);
            if ($torrentsPerPage <= 0) {
                $torrentsPerPage = 25;
            }

            if ($count > 0) {
                $pager = pager($torrentsPerPage, $count, 'bookmarks.php?&amp;');

                $bookmarks = $db->fetchAll(
                    'SELECT b.id AS bookmarkid,
                            b.private,
                            t.owner,
                            t.id,
                            t.name,
                            t.comments,
                            t.leechers,
                            t.seeders,
                            t.save_as,
                            t.numfiles,
                            t.added,
                            t.filename,
                            t.size,
                            t.views,
                            t.visible,
                            t.hits,
                            t.times_completed,
                            t.category
                     FROM bookmarks AS b
                     INNER JOIN torrents AS t ON b.torrentid = t.id
                     WHERE b.userid = :userid
                     ORDER BY t.id DESC
                     LIMIT :limit OFFSET :offset',
                    [
                        'userid' => $userId,
                        'limit' => [$pager['pdo']['limit'], \PDO::PARAM_INT],
                        'offset' => [$pager['pdo']['offset'], \PDO::PARAM_INT],
                    ],
                );

                if ($count > $torrentsPerPage) {
                    $htmlOut .= $pager['pagertop'];
                }

                $htmlOut .= $this->renderBookmarks($bookmarks, $userId, 'index', $config);

                if ($count > $torrentsPerPage) {
                    $htmlOut .= $pager['pagerbottom'];
                }
            }

            $title = _('Bookmarks');
            $breadcrumbs = [
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot($stdfoot);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function renderBookmarks(array $rows, int $userId, string $variant, ConfigRepository $config): string
    {
        $html = "
    <div class='has-text-centered bottom20'>
        " . _('Icon Legend :') . "
        <i class='icon-bookmark-empty icon has-text-danger'></i> = " . _('Delete Bookmark') . " |
        <i class='icon-download icon'></i> = " . _('Download Torrent') . " |
        <i class='icon-key icon has-text-success'></i> = " . _('Bookmark is Private') . " |
        <i class='icon-users icon has-text-danger'></i> = " . _('Bookmark is Public') . '
    </div>';

        $heading = '
                        <tr>
                            <th>' . _('Type') . "</th>
                            <th class='has-text-left'>" . _('Name') . '</th>';
        $heading .= ($variant === 'index' ? '
                            <th>' . _('Delete') . '</th>
                            <th>' : '') . _('Download') . '</th>
                            <th>' . _('Share') . '</th>';
        if ($variant === 'mytorrents') {
            $heading .= '
                            <th>' . _('Edit') . '</th>
                            <th>' . _('Yes') . '</th>';
        }
        $heading .= '
                            <th>' . _('Files') . '</th>
                            <th>' . _('Comments') . '</th>
                            <th>' . _('Added') . '</th>
                            <th>' . _('Torrent Size') . '</th>
                            <th>' . _('Times Completed') . '</th>
                            <th>' . _('Seeders') . '</th>
                            <th>' . _('Leechers') . '</th>';
        if ($variant === 'index') {
            $heading .= '
                            <th>' . _('Upped by') . '</th>';
        }
        $heading .= '
                        </tr>';

        $body = '';
        $categories = genrelist(false);
        $categoryIndex = [];
        foreach ($categories as $category) {
            $categoryIndex[$category['id']] = [
                'name' => $category['name'],
                'image' => $category['image'],
            ];
        }

        foreach ($rows as $row) {
            $categoryId = (int) ($row['category'] ?? 0);
            $categoryName = $categoryIndex[$categoryId]['name'] ?? '';
            $categoryImage = $categoryIndex[$categoryId]['image'] ?? '';
            $torrentId = (int) ($row['id'] ?? 0);

            $body .= "
                        <tr>
                            <td class='has-text-centered'>";
            if ($categoryName !== '') {
                $body .= '<a href="' . $config->get('paths.baseurl') . '/browse.php?cat=' . $categoryId . '">';
                if ($categoryImage !== '') {
                    $body .= "<img src='{$config->get('paths.images_baseurl')}caticons/" . get_category_icons() . '/' . htmlsafechars((string) $categoryImage) . "' alt='" . htmlsafechars((string) $categoryName) . "' class='tooltipper' title='" . htmlsafechars((string) $categoryName) . "'>";
                } else {
                    $body .= htmlsafechars((string) $categoryName);
                }
                $body .= '</a>';
            } else {
                $body .= '-';
            }
            $body .= '
                            </td>';

            $displayName = htmlsafechars((string) ($row['name'] ?? ''));
            $body .= "
                            <td class='has-text-left'>
                                <a href='{$config->get('paths.baseurl')}/details.php?";
            if ($variant === 'mytorrents') {
                $body .= 'returnto=' . urlencode($_SERVER['REQUEST_URI'] ?? '') . '&amp;';
            }
            $body .= "id=$torrentId";
            if ($variant === 'index') {
                $body .= '&amp;hit=1';
            }
            $body .= "'><b>$displayName</b></a>&#160;
                            </td>";

            if ($variant === 'index') {
                $body .= "
                            <td class='has-text-centered'>
                                <span data-tid='{$torrentId}' data-remove='true' data-private='false' class='bookmarks tooltipper' title='" . _('Delete Bookmark!') . "'>
                                    <i class='icon-bookmark-empty icon has-text-danger'></i>
                                </span>
                            </td>";
                $body .= "
                            <td class='has-text-centered'>
                                <a href='{$config->get('paths.baseurl')}/download.php?torrent={$torrentId}' class='tooltipper' title='" . _('Download Bookmark!') . "'>
                                    <i class='icon-download icon'></i>
                                </a>
                            </td>";

                $isPrivate = ($row['private'] ?? 'no') === 'yes';
                if ($isPrivate) {
                    $body .= "
                            <td class='has-text-centered'>
                                <span data-tid='{$torrentId}' data-remove='false' data-private='true' class='bookmarks tooltipper' title='" . _('Mark Bookmark Public!') . "'>
                                    <i class='icon-key icon has-text-success'></i>
                                </span>
                            </td>";
                } else {
                    $body .= "
                            <td class='has-text-centered'>
                                <span data-tid='{$torrentId}' data-remove='false' data-private='true' class='bookmarks tooltipper' title='" . _('Mark Bookmark Private!') . "'>
                                    <i class='icon-users icon has-text-danger'></i>
                                </span>
                            </td>";
                }
            }

            if ($variant === 'mytorrents') {
                $body .= "
                            <td class='has-text-centered'>
                                <a href='{$config->get('paths.baseurl')}/edit.php?returnto=" . urlencode($_SERVER['REQUEST_URI'] ?? '') . "&amp;id={$torrentId}'>" . _('Edit') . '</a>';
                $body .= "
                            </td>
                            <td class='has-text-right'>";
                $body .= ($row['visible'] ?? 'yes') === 'no' ? _('No') : _('Yes');
                $body .= '
                            </td>';
            }

            $body .= "
                            <td class='has-text-right'><b><a href='{$config->get('paths.baseurl')}/filelist.php?id={$torrentId}'>" . (int) ($row['numfiles'] ?? 0) . '</a></b></td>';

            $commentCount = (int) ($row['comments'] ?? 0);
            if ($commentCount === 0) {
                $body .= "
                            <td class='has-text-right'>0</td>";
            } else {
                if ($variant === 'index') {
                    $body .= "
                            <td class='has-text-right'><b><a href='{$config->get('paths.baseurl')}/details.php?id={$torrentId}&amp;hit=1&amp;tocomm=1'>" . $commentCount . '</a></b></td>';
                } else {
                    $body .= "
                            <td class='has-text-right'><b><a href='{$config->get('paths.baseurl')}/details.php?id={$torrentId}&amp;page=0#startcomments'>" . $commentCount . '</a></b></td>';
                }
            }

            $added = (int) ($row['added'] ?? 0);
            $body .= "
                            <td class='has-text-centered'><span>" . str_replace(',', '<br>', get_date($added, '')) . "</span></td>
                            <td class='has-text-centered'>" . str_replace(' ', '<br>', mksize((int) ($row['size'] ?? 0))) . '</td>';

            $completed = (int) ($row['times_completed'] ?? 0);
            $body .= "
                            <td class='has-text-centered'><a href='{$config->get('paths.baseurl')}/snatches.php?id={$torrentId}'>" . _pfe('{0} time', '{0} times', $completed) . '</a></td>';

            $seeders = (int) ($row['seeders'] ?? 0);
            $leechers = (int) ($row['leechers'] ?? 0);
            if ($seeders > 0) {
                if ($variant === 'index') {
                    $ratio = $leechers > 0 ? $seeders / max($leechers, 1) : 1;
                    $body .= "
                            <td class='has-text-right'><b><a href='{$config->get('paths.baseurl')}/peerlist.php?id={$torrentId}#seeders'><span style='color: " . get_slr_color($ratio) . ";'>" . $seeders . '</span></a></b></td>';
                } else {
                    $body .= "
                            <td class='has-text-right'><b><a class='" . linkcolor($seeders) . "' href='{$config->get('paths.baseurl')}/peerlist.php?id={$torrentId}#seeders'>" . $seeders . '</a></b></td>';
                }
            } else {
                $body .= "
                            <td class='has-text-right'><span class='" . linkcolor($seeders) . "'>" . $seeders . '</span></td>';
            }

            if ($leechers > 0) {
                if ($variant === 'index') {
                    $body .= "
                            <td class='has-text-right'><b><a href='{$config->get('paths.baseurl')}/peerlist.php?id={$torrentId}#leechers'>" . number_format($leechers) . '</a></b></td>';
                } else {
                    $body .= "
                            <td class='has-text-right'><b><a class='" . linkcolor($leechers) . "' href='{$config->get('paths.baseurl')}/peerlist.php?id={$torrentId}#leechers'>" . $leechers . '</a></b></td>';
                }
            } else {
                $body .= "
                            <td class='has-text-right'>0</td>";
            }

            if ($variant === 'index') {
                $ownerId = (int) ($row['owner'] ?? 0);
                $body .= "
                            <td class='has-text-centered'>" . ($ownerId > 0 ? format_username($ownerId) : '<i>(' . _('Unknown') . ')</i>') . '</td>';
            }

            $body .= '
                        </tr>';
        }

        $html .= main_table($body, $heading);

        return $html;
    }
}
