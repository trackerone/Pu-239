<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T19:57:12Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Ban;
use Pu239\Database;
use Pu239\IP;
use Pu239\User;

final class IphistoryHandler
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
            $currentUser = $GLOBALS['CURUSER'] ?? null;

            if (defined('ADMIN_DIR') && strpos((string) ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            require_once INCL_DIR . 'geoip.inc';
            require_once INCL_DIR . 'geoipcity.inc';
            require_once INCL_DIR . 'geoipregionvars.php';

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Ban $bansClass */
            $bansClass = $container->get(Ban::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $color = '';
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!is_valid_id($id)) {
                stderr(_('Error'), _('Invalid ID'));
            }
            /** @var IP $ipsClass */
            $ipsClass = $container->get(IP::class);
            if (isset($_GET['remove'])) {
                $ip = htmlsafechars((string) $_GET['remove']);
                $type = htmlsafechars((string) ($_GET['type'] ?? ''));
                $ipsClass->delete($id, $ip, $type);
                unset($ip, $type);
            }
            if (isset($_GET['banthisuser'])) {
                $ip = htmlsafechars((string) ($_GET['banthisip'] ?? ''));
                if ($ip !== '') {
                    $bansClass->add_ban($ip, (int) ($currentUser['id'] ?? 0), 'Banned');
                    audit_log(
                        $currentUser['id'] ?? null,
                        'user.ban',
                        [
                            'target' => $id,
                            'ip' => $ip,
                            'reason' => 'iphistory.ban',
                        ]
                    );
                }
            }

            /** @var User $usersClass */
            $usersClass = $container->get(User::class);
            $user = $usersClass->getUserFromId($id);
            $username = htmlsafechars($user['username']);
            $resip = $ipsClass->get_data_set($id);
            $ipcount = $ipsClass->get_ip_count($id, 0, 'all');
            $HTMLOUT = "
        <h1 class='has-text-centered'>" . _('IP addresses used by ') . format_username($id) . "</h1>
        <p class='has-text-centered'>" . _('Total Unique IP Addresses') . " <b>$username</b> " . _('Has Logged In With') . " <b><u>$ipcount</u></b>.</p>
        <p class='has-text-centered'>
            <span class='is-blue'>" . _('Single') . "</span> - <span class='has-text-danger'>" . _('Banned') . "</span> - <span class='has-text-success'>" . _('Dupe Used') . '</span>
        </p>';

            $heading = '
        <tr>
            <th>' . _('Last') . '</th>
            <th>' . _('Address') . '</th>
            <th>' . _('ISP/Host Name') . '</th>
            <th>' . _('Location') . '</th>
            <th>' . _('Type') . '</th>
            <th>' . _('Delete') . '</th>
            <th>' . _('Ban') . '</th>
        </tr>';

            $body = '';
            foreach ($resip as $iphistory) {
                if (!validip($iphistory['ip'])) {
                    continue;
                }
                $host = gethostbyaddr($iphistory['ip']);
                $userip = htmlsafechars($iphistory['ip']);
                if ($host === $userip) {
                    $host = "<span class='has-text-danger'><b>" . _('Not Found') . '</b></span>';
                }
                $lastannounce = $iphistory['type'] === 'announce' ? $iphistory['last_access'] : 0;
                $lastbrowse = $iphistory['type'] === 'browse' ? $iphistory['last_access'] : 0;
                $lastlogin = $iphistory['type'] === 'login' ? $iphistory['last_access'] : 0;
                $iptype = htmlsafechars($iphistory['type']);
                $ipcountUser = $ipsClass->get_user_count($iphistory['ip']);
                $count = $bansClass->get_count($iphistory['ip']);
                if ($count === 0) {
                    if ($ipcountUser > 1) {
                        $ipshow = "<b><a class='is-link' href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=" . htmlsafechars($iphistory['ip']) . "'><span class='has-text-success'>" . htmlsafechars($iphistory['ip']) . ' </span></a></b>';
                    } else {
                        $ipshow = "<a class='is-link' href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=" . htmlsafechars($iphistory['ip']) . "'><b><span class='is-blue'>" . htmlsafechars($iphistory['ip']) . ' </span></b></a>';
                    }
                } else {
                    $ipshow = "<a class='is-link' href='{$config->get('paths.baseurl')}/staffpanel.php?tool=testip&amp;action=testip&amp;ip=" . htmlsafechars($iphistory['ip']) . "'><span class='has-text-danger'><b>" . htmlsafechars($iphistory['ip']) . ' </b></span></a>';
                }

                $gi = geoip_open(ROOT_DIR . 'GeoIP' . DIRECTORY_SEPARATOR . 'GeoIP.dat', GEOIP_STANDARD);
                $countrybyip = geoip_country_name_by_addr($gi, $userip);
                $listcountry = $countrybyip;
                geoip_close($gi);

                $gi = geoip_open(ROOT_DIR . 'GeoIP' . DIRECTORY_SEPARATOR . 'GeoLiteCity.dat', GEOIP_STANDARD);
                $citybyip = geoip_record_by_addr($gi, $userip);
                $listcity = @$citybyip->city;
                $listregion = @$citybyip->region;
                geoip_close($gi);

                $body .= '
        <tr>
            <td>' . _('Browse') . ': ' . get_date((int) $lastbrowse, '') . '<br>' . _('Login') . ': ' . get_date((int) $lastlogin, '') . '<br>' . _('Announce') . ': ' . get_date((int) $lastannounce, '') . "</td>
            <td>$ipshow</td>
            <td>$host</td>
            <td>$listcity, $listregion<br>$listcountry</td>
            <td>$iptype</td>
            <td><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=iphistory&amp;id=$id&amp;remove=" . urlencode($iphistory['ip']) . "&amp;type={$iptype}'><b>" . _('Delete') . "</b></a></td>
            <td><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=iphistory&amp;id=$id&amp;banthisuser=$username&amp;banthisip=$userip'><b>" . _('Ban') . '</b></a></td>
        </tr>';
            }

            if (!empty($body)) {
                $HTMLOUT .= main_table($body, $heading, 'top20');
            } else {
                $HTMLOUT .= stdmsg(_('Error'), _("There is no IP data available."));
            }
            $title = _('IP History');
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
