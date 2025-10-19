<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:05:00Z via handler-convert offset=255 batch=5

namespace PU239\Http\Handlers\PublicSite;

use PU239\Config\ConfigRepository;
use Pu239\HappyLog;

use function dirname;

final class HappylogHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-19T15:05:00Z via handler-convert offset=255 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            $user = check_user_status();
            $HTMLOUT = '';
            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $baseurl = (string) $config->get('paths.baseurl');

            if (empty($user)) {
                stderr(_('Error'), 'User not found');
            }
            $id = $user['id'];
            /** @var HappyLog $happylog_class */
            $happylog_class = $container->get(HappyLog::class);
            $count = $happylog_class->get_count($id);
            $perpage = 30;
            $pager = pager($perpage, $count, "{$baseurl}/happylog.php?id=$id&amp;");
            $res = $happylog_class->get_by_userid($id, $pager['pdo']);
            $HTMLOUT .= "
    <h1 class='has-text-centered'>" . _fe('Happy hour log for {0}', format_username((int) $id)) . '</h1>';
            if ($count > 0) {
                $HTMLOUT .= $count > $perpage ? $pager['pagertop'] : '';
                $heading = "
        <tr>
            <td class='colhead w-50'>" . _('Name') . "</td>
            <td class='colhead'>" . _('Multiplier') . "</td>
            <td class='colhead' nowrap='nowrap'>" . _('Date Started') . '</td>
        </tr>';
                $body = '';
                foreach ($res as $arr) {
                    $body .= "
        <tr>
            <td><a href='{$baseurl}/details.php?id={$arr['torrentid']}'>" . htmlsafechars($arr['name']) . "</a></td>
            <td>{$arr['multi']}</td>
            <td nowrap='nowrap'>" . get_date((int) $arr['date'], 'LONG', 1, 0) . '</td>
        </tr>';
                }
                $HTMLOUT .= main_table($body, $heading);
                $HTMLOUT .= $count > $perpage ? $pager['pagerbottom'] : '';
            } else {
                $HTMLOUT .= main_div(_('No torrents downloaded during happy hour!'), '', 'has-text-centered padding20');
            }
            $title = _('Happy Log');
            $breadcrumbs = [
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
