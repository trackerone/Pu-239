<?php
declare(strict_types=1);

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;

final class SysoplogHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05 via tools/handler_convert_report.csv
        try {
            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container, $CURUSER;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $search = isset($_POST['search']) ? strip_tags((string) $_POST['search']) : '';
            if (isset($_GET['search'])) {
                $search = strip_tags((string) $_GET['search']);
            }

            $params = [];
            $where = '';
            if ($search !== '') {
                $where = 'WHERE txt LIKE :search';
                $params[':search'] = "%{$search}%";
            }

            $secondsToKeep = 30 * 86400;
            $cutoff = TIME_NOW - $secondsToKeep;
            $pruneStatement = $db->run('DELETE FROM infolog WHERE added < :cutoff', [':cutoff' => $cutoff]);
            $pruned = method_exists($pruneStatement, 'rowCount') ? $pruneStatement->rowCount() : null;
            if (!empty($pruned)) {
                audit_log($CURUSER['id'] ?? null, 'config.update', [
                    'keys' => ['sysoplog.prune'],
                    'count' => $pruned,
                ]);
            }

            $count = (int) $db->fetchValue("SELECT COUNT(id) FROM infolog $where", $params);
            $perPage = 30;
            $baseUrl = $config->get('paths.baseurl');
            $searchQuery = $search !== '' ? "search={$search}&amp;" : '';
            $pager = pager($perPage, $count, "staffpanel.php?tool=sysoplog&amp;action=sysoplog&amp;{$searchQuery}");

            $rows = $db->fetchAll("SELECT added, txt FROM infolog $where ORDER BY added DESC {$pager['limit']}", $params);

            $htmlOut = "
    <h1 class='has-text-centered'>" . _('Staff actions log') . "</h1>
    <div class='has-text-centered bottom20'>
        <form method='post' action='" . ($_SERVER['PHP_SELF'] ?? '') . "?tool=sysoplog&amp;action=sysoplog' enctype='multipart/form-data' accept-charset='utf-8'>
            <input type='text' name='search' size='40' value='' placeholder='" . _('Search log') . "'>
            <input type='submit' value='" . _('Search log') . "' class='button is-small'>
        </form>
    </div>";

            if ($count > $perPage) {
                $htmlOut .= $pager['pagertop'];
            }

            if (empty($rows)) {
                $htmlOut .= main_div("<div class='padding20'>" . _('No records found') . '</div>');
            } else {
                $heading = '
      <tr>
          <th>' . _('Date') . '</th>
          <th>' . _('Time') . '</th>
          <th>' . _('Event') . '</th>
      </tr>';
                $body = '';
                $logEvents = [];
                $colors = [];
                foreach ($rows as $arr) {
                    $txt = substr($arr['txt'], 0, 50);
                    if (!in_array($txt, $logEvents, true)) {
                        $color = random_color();
                        while (in_array($color, $colors, true)) {
                            $color = random_color();
                        }
                        $logEvents[] = $txt;
                        $colors[] = $color;
                    }
                    $key = array_search($txt, $logEvents, true);
                    $color = $colors[$key];
                    $date = get_date((int) $arr['added'], 'DATE');
                    $time = get_date((int) $arr['added'], 'LONG', 0, 1);
                    $body .= "
        <tr>
            <td style='background-color: $color;'>
                <span class='has-text-black'>{$date}</span>
            </td>
            <td style='background-color: $color;'>
                <span class='has-text-black'>{$time}</span>
            </td>
            <td style='background-color: $color;'>
                <span class='has-text-black'>{$arr['txt']}</span>
            </td>
        </tr>";
                }
                $htmlOut .= main_table($body, $heading);
            }

            if ($count > $perPage) {
                $htmlOut .= $pager['pagerbottom'];
            }

            $title = _('Sysop Log');
            $breadcrumbs = [
                "<a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='" . ($_SERVER['PHP_SELF'] ?? '') . "'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($htmlOut) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
