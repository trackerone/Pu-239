<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:45:00Z via handler_convert (offset=230 batch=5)

namespace PU239\Http\Handlers\PublicSite;

use Pu239\Config\ConfigRepository;
use Pu239\HappyLog;

final class HappylogHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-18T19:45:00Z via handler_convert (offset=230 batch=5)
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            $user = check_user_status();
            $HTMLOUT = '';
            $baseUrl = (string) $config->get('paths.baseurl');

            if (empty($user)) {
                stderr(_('Error'), 'User not found');
            }
            $id = $user['id'];
            /** @var HappyLog $happylog */
            $happylog = $container->get(HappyLog::class);
            $count = $happylog->get_count($id);
            $perpage = 30;
            $pager = pager($perpage, $count, sprintf('%s/happylog.php?id=%d&amp;', $baseUrl, $id));
            $res = $happylog->get_by_userid($id, $pager['pdo']);
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
                        <td><a href='{$baseUrl}/details.php?id={$arr['torrentid']}'>" . htmlsafechars($arr['name']) . "</a></td>
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
                sprintf("<a href='%s'>%s</a>", $_SERVER['PHP_SELF'] ?? '', $title),
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
