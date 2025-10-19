<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T16:48:06Z via handler-convert offset=285 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PDO;
use Pu239\Cache;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\User;
use Rakit\Validation\Validator;

use function array_filter;
use function array_map;
use function date;
use function dirname;
use function explode;
use function hash;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function sprintf;
use function urlencode;

final class RssHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T16:48:06Z via handler-convert offset=285 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            require_once dirname(__DIR__, 4) . '/include/function_bbcode.php';

            global $container;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Validator $validator */
            $validator = $container->get(Validator::class);
            /** @var User $users */
            $users = $container->get(User::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Cache $cache */
            $cache = $container->get(Cache::class);

            $input = $_GET;
            $validation = $validator->validate($input, [
                'torrent_pass' => 'required|alpha_num:between:64,64',
                'count' => 'required|in:15,30,50,100',
                'bm' => 'required|in:0,1',
                'type' => 'required|in:dl,web',
                'cats' => 'regex:/^(\\d+,?)*$/',
            ]);

            $feedType = is_string($input['type'] ?? null) ? (string) $input['type'] : 'web';
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';

            if ($validation->fails()) {
                if (!isset($input['torrent_pass'])) {
                    $this->formatRss($config, _("Your link doesn't have a torrent pass"), null, $feedType, $requestUri);
                } elseif (!isset($input['torrent_pass']) || strlen((string) $input['torrent_pass']) !== 64) {
                    $this->formatRss($config, _fe('Your torrent pass is not long enough! Go to {0} and reset your passkey', $config->get('site.name')), null, $feedType, $requestUri);
                } else {
                    $this->formatRss($config, _("Your link isn't a valid rss link."), null, $feedType, $requestUri);
                }

                return;
            }

            $torrentPass = (string) $input['torrent_pass'];
            $user = $users->get_user_from_torrent_pass($torrentPass);
            if (!$user) {
                $this->formatRss($config, _fe('Your torrent pass is invlaid! Go to {0} and reset your passkey', $config->get('site.name')), null, $feedType, $requestUri);

                return;
            }

            if (($user['status'] ?? 0) === 2) {
                $this->formatRss($config, _("Permission denied, you're account is disabled"), null, $feedType, $requestUri);

                return;
            }
            if (($user['status'] ?? 0) === 1) {
                $this->formatRss($config, _("Permission denied, you're account is parked"), null, $feedType, $requestUri);

                return;
            }
            if (($user['downloadpos'] ?? 0) !== 1) {
                $this->formatRss($config, _('Your download privileges have been removed.'), null, $feedType, $requestUri);

                return;
            }
            if (($user['status'] ?? 0) === 5) {
                $this->formatRss($config, _("Permission denied, you're account is suspended"), null, $feedType, $requestUri);

                return;
            }
            if (($user['status'] ?? 0) !== 0) {
                $this->formatRss($config, _("Permission denied, you're account is disabled for other reasons"), null, $feedType, $requestUri);

                return;
            }

            $catsParam = (string) ($input['cats'] ?? '');
            if ($catsParam !== '') {
                $categories = array_filter(
                    array_map(static fn(string $cat): int => (int) $cat, explode(',', $catsParam)),
                    static fn(int $cat): bool => $cat > 0
                );
            } else {
                $categories = [];
            }

            $counts = [15, 30, 50, 100];
            $limit = 15;
            if (isset($input['count']) && in_array((int) $input['count'], $counts, true)) {
                $limit = (int) $input['count'];
            }

            $bookmarksOnly = (int) ($input['bm'] ?? 0) === 1;

            $hashKey = hash('sha256', json_encode($_POST));
            $cacheKey = 'rss_query_' . $hashKey;
            $cache->delete($cacheKey);
            $data = $cache->get($cacheKey);
            if ($data === false || $data === null) {
                $sql = 'SELECT t.id, t.name, t.descr, t.size, t.category, t.seeders, t.leechers, t.added, c.name AS catname ' .
                    'FROM torrents AS t ';
                $params = [];
                if ($bookmarksOnly) {
                    $sql .= 'INNER JOIN bookmarks AS b ON t.id = b.torrentid ';
                }
                $sql .= 'LEFT JOIN categories AS c ON t.category = c.id ';
                $conditions = [];
                if ($categories !== []) {
                    $placeholders = [];
                    foreach ($categories as $index => $categoryId) {
                        $placeholder = ':cat' . $index;
                        $placeholders[] = $placeholder;
                        $params[$placeholder] = [$categoryId, PDO::PARAM_INT];
                    }
                    $conditions[] = 't.category IN (' . implode(', ', $placeholders) . ')';
                }
                if ((int) ($user['class'] ?? 0) !== UC_VIP) {
                    $conditions[] = 't.vip = "0"';
                }
                if ($bookmarksOnly) {
                    $conditions[] = 'b.userid = :bookmark_user';
                    $params[':bookmark_user'] = [(int) $user['id'], PDO::PARAM_INT];
                }

                if ($conditions !== []) {
                    $sql .= 'WHERE ' . implode(' AND ', $conditions) . ' ';
                }

                $sql .= 'ORDER BY t.added DESC LIMIT :limit';
                $params[':limit'] = [$limit, PDO::PARAM_INT];

                $data = $db->fetchAll($sql, $params);
                if (is_array($data) && $data !== []) {
                    $cache->set($cacheKey, $data, 300);
                } else {
                    $data = _('No results in your request');
                }
            }

            $this->formatRss($config, $data, $torrentPass, $feedType, $requestUri);
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    /**
     * @param array<int, array<string, mixed>>|string $data
     */
    private function formatRss(ConfigRepository $config, $data, ?string $torrentPass, string $feedType, string $requestUri): void
    {
        $rssDescription = $config->get('site.name') . ' RSS Feed - Please Donate';
        $feed = $feedType === 'dl' ? 'dl' : 'web';
        $url = urlencode($config->get('paths.baseurl') . $requestUri);
        $date = date(DATE_RSS, TIME_NOW);
        $rss = '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/css" href="' . $config->get('paths.baseurl') . '/css/rss.css"?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
    <channel>
        <title>' . $config->get('site.name') . '</title>
        <atom:link href="' . $url . '" rel="self" type="application/rss+xml" />
        <link>' . $config->get('paths.baseurl') . '</link>
        <description>' . $rssDescription . '</description>
        <language>en-us</language>
        <copyright>Copyright © ' . date('Y') . ' ' . $config->get('site.name') . '</copyright>
        <webMaster>' . $config->get('site.email') . '(' . $config->get('site.name') . ')</webMaster>
        <lastBuildDate>' . $date . '</lastBuildDate>
        <ttl>5</ttl>
        <image>
            <title>' . $config->get('site.name') . '</title>
            <url>' . $config->get('paths.baseurl') . '/favicon-16x16.png</url>
            <link>' . $config->get('paths.baseurl') . '</link>
            <width>16</width>
            <height>16</height>
            <description>' . $rssDescription . '</description>
        </image>';

        if (is_array($data)) {
            foreach ($data as $row) {
                $id = (int) ($row['id'] ?? 0);
                $size = mksize((int) ($row['size'] ?? 0));
                $seeders = (int) ($row['seeders'] ?? 0);
                $leechers = (int) ($row['leechers'] ?? 0);
                $name = htmlsafechars((string) ($row['name'] ?? ''));
                $categoryName = htmlsafechars((string) ($row['catname'] ?? ''));
                $added = get_date((int) ($row['added'] ?? 0), 'DATE');
                $description = htmlsafechars(substr(format_comment_no_bbcode((string) ($row['descr'] ?? ''), true), 0, 450));
                $published = date(DATE_RSS, (int) ($row['added'] ?? 0));
                $link = $config->get('paths.baseurl') . ($feed === 'dl'
                    ? '/download.php?torrent=' . $id . '&amp;torrent_pass=' . $torrentPass
                    : '/details.php?id=' . $id . '&amp;hit=1');
                $guidLink = $config->get('paths.baseurl') . '/details.php?id=' . $id;
                $rss .= '
        <item>
            <title>' . $name . '</title>
            <link>' . $link . '</link>
            <description>
                <p>' . _('Category') . ': ' . $categoryName . '</p>
                <p>' . _('Size') . ': ' . $size . '</p>
                <p>' . _('Leechers') . ': ' . $leechers . '</p>
                <p>' . _('Seeders') . ': ' . $seeders . '</p>
                <p>' . _('Added') . ': ' . $added . '</p>
                <p>' . _('Description') . ': ' . $description . '</p>
            </description>
            <guid>' . $guidLink . '</guid>
            <pubDate>' . $published . '</pubDate>
        </item>';
            }
        } else {
            $rss .= '
        <item>
            <title>' . _('Empty Results') . '</title>
            <link>' . $config->get('paths.baseurl') . '/getrss.php</link>
            <description>' . $data . '</description>
            <guid>' . $config->get('paths.baseurl') . '/getrss.php</guid>
            <pubDate>' . $date . '</pubDate>
        </item>';
        }

        $rss .= '
    </channel>
</rss>';

        header('Content-Type: application/xml');
        // TODO(2025): review escaping strategy for $rss output
        echo $rss; // noescape
        echo $rss;
        app_halt('Exit called');
    }
}
