<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05 via handler-convert (batch=8)

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;

final class ReferrersHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05 via handler-convert (batch=8)
        try {
            global $container, $CURUSER;

            AuthZ::requireRole('admin');
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $HTMLOUT = '';
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
            $perpage = 10;
            $count = (int) ($db->fetchValue('SELECT COUNT(*) FROM referrers') ?? 0);
            if ($count > 0) {
                $HTMLOUT .= "
    <h1 class='has-text-centered'>" . _('Last referers') . '</h1>';
                $heading = '
        <tr>
            <th>' . _('Nr.') . '</th>
            <th>' . _('Date / Time') . '</th>
            <th>' . _('Browser') . '</th>
            <th>' . _('IP') . '</th>
            <th>' . _('User') . '</th>
            <th>' . _('URL') . '</th>
            <th>' . _('Result') . '</th>
        </tr>';
                $pager = pager($perpage, $count, 'staffpanel.php?tool=referrers&amp;');
                $rows = $db->toArray(
                    'SELECT r.*, u.id AS uid, u.username FROM referrers AS r LEFT JOIN users AS u ON u.ip = r.ip ORDER BY date DESC ' . $pager['limit']
                );
                $body = '';
                $i = $page * $perpage;
                foreach ($rows as $data) {
                    ++$i;
                    $http_agent = htmlsafechars((string) $data['browser']);
                    if (str_contains($http_agent, 'Opera')) {
                        $browser = "<img src='{$config->get('paths.images_baseurl')}referrers/opera.png' alt='Opera' title='Opera' width='25' height='25'>&#160;&#160;Opera";
                    } elseif (str_contains($http_agent, 'Konqueror')) {
                        $browser = "<img src='{$config->get('paths.images_baseurl')}referrers/konqueror.png' alt='konqueror' title='konqueror' width='25' height='25'>&#160;&#160;konqueror";
                    } elseif (str_contains($http_agent, 'MSIE')) {
                        $browser = "<img src='{$config->get('paths.images_baseurl')}referrers/ie.png' alt='IE' title='IE' width='25' height='25'>&#160;&#160;IE";
                    } elseif (str_contains($http_agent, 'Chrome')) {
                        $browser = "<img src='{$config->get('paths.images_baseurl')}referrers/chrome.png' alt='Chrome' title='Chrome' width='25' height='25'>&#160;&#160;Chrome";
                    } elseif (
                        str_contains($http_agent, 'Nav') ||
                        str_contains($http_agent, 'Gold') ||
                        str_contains($http_agent, 'X11') ||
                        str_contains($http_agent, 'Mozilla') ||
                        str_contains($http_agent, 'Netscape')
                    ) {
                        $browser = "<img src='{$config->get('paths.images_baseurl')}referrers/firefox.png' alt='FireFox' title='FireFox' width='25' height='25'>&#160;&#160;Mozilla";
                    } else {
                        $browser = _('Unknow Browser');
                    }
                    $userCell = htmlsafechars((string) $data['ip']) . ' ';
                    if (!empty($data['uid'])) {
                        $userCell .= format_username((int) $data['uid']);
                    } else {
                        $userCell .= ' [' . _('Guest') . ']';
                    }
                $body .= "
        <tr>
            <td>{$i}</td>
            <td>" . get_date((int) $data['date'], '') . "</td>
            <td>{$browser}</td>
            <td>" . htmlsafechars((string) $data['ip']) . "</td>
            <td>{$userCell}</td>
            <td><a href='" . htmlsafechars((string) $data['referer']) . "'>" . htmlsafechars(CutName((string) $data['referer'], 50)) . "</a></td>
            <td><a href='" . htmlsafechars((string) $data['page']) . "'>" . _('page viewed') . "</a></td>
        </tr>";
                }
                $HTMLOUT .= main_table($body, $heading);
                $HTMLOUT .= $pager['pagerbottom'];
            } else {
                $HTMLOUT .= stdmsg(_('Nothing found!'), _('Try again with a refined search string.'));
            }
            $title = _('Referers');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
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
