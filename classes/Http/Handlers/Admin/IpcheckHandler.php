<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:57:12Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\IP;
use Pu239\User;

final class IpcheckHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:57:12Z via codex handler conversion
        try {
            $container = $GLOBALS['container'] ?? null;
            if ($container === null) {
                throw new \RuntimeException('Global container not initialized');
            }

            if (defined('ADMIN_DIR') && strpos((string) ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var User $usersClass */
            $usersClass = $container->get(User::class);
            /** @var IP $ipsClass */
            $ipsClass = $container->get(IP::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $data = $ipsClass->get_duplicates();
            $heading = '
    <tr>
        <th>' . _('User') . '</th>
        <th>' . _('Email') . '</th>
        <th>' . _('Registered') . '</th>
        <th>' . _('Last access') . '</th>' . ((bool) $config->get('site.ratio_free') ? '' : '
        <th>' . _('Downloaded') . '</th>') . '
        <th>' . _('Uploaded') . '</th>
        <th>' . _('Ratio') . '</th>
        <th>' . _('IP') . '</th>
    </tr>';
            $ip = '';
            $body = '';
            foreach ($data as $ras) {
                if ($ras['count'] <= 1) {
                    break;
                }
                if ($ras['ip'] !== $ip) {
                    $ros = $ipsClass->getUsersFromIP($ras['ip']);
                    if (count($ros) > 1) {
                        foreach ($ros as $arr) {
                            if ($arr['last_access'] == '0') {
                                $arr['last_access'] = '-';
                            }
                            $uploaded = mksize($arr['uploaded']);
                            $downloaded = mksize($arr['downloaded']);
                            $added = get_date((int) $arr['registered'], 'DATE', 1, 0);
                            $lastAccess = get_date((int) $arr['last_access'], '', 1, 0);
                            $body .= '
                <tr>
                    <td>' . format_username((int) $arr['id']) . '</td>
                    <td>' . format_comment($arr['email']) . "</td>
                    <td>$added</td>
                    <td>$lastAccess</td" . ((bool) $config->get('site.ratio_free') ? '' : "
                    <td>$downloaded</td>") . "
                    <td>$uploaded</td>
                    <td>" . member_ratio((float) $arr['uploaded'], (float) $arr['downloaded']) . '</td>
                    <td><span class="has-text-weight-bold">' . format_comment($arr['ip']) . '</span></td>
                </tr>';
                            $ip = htmlsafechars($arr['ip']);
                        }
                    }
                }
            }

            $HTMLOUT = '<h1 class="has-text-centered">Duplicate IP Check</h1>';
            if (!empty($body)) {
                $HTMLOUT .= main_table($body, $heading);
            } else {
                $HTMLOUT .= stdmsg(_('Error'), _("There are no duplicate IP's in use."));
            }
            $title = _('IP Check');
            $breadcrumbs = [
                "<a href='" . (string) $config->get('paths.baseurl') . "/staffpanel.php'>" . _('Staff Panel') . '</a>',
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
