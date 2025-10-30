<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Psr\Container\ContainerInterface;

final class IpSearchController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23T00:00:00Z; tool=codex-admin-convert; rules=2025.10.23
        try {
            // LEGACY BODY START (from admin/ipsearch.php)
            global $container;
            $container = $this->container;
            /** @var ConfigRepository $config */
            $config = $this->config;
            $db = $container->get(Database::class);

            AuthZ::requireRole('admin');

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $HTMLOUT = '';
            $HTMLOUT .= begin_main_frame();
            $ip = isset($_GET['ip']) ? htmlsafechars($_GET['ip']) : '';
            if ($ip) {
                $regex = "/^(((1?\d{1,2})|(2[0-4]\d)|(25[0-5]))(\.\b|$)){4}$/";
                if (!preg_match($regex, $ip)) {
                    $HTMLOUT .= stdmsg(_('Error'), _('Invalid IP.'));
                    $HTMLOUT .= end_main_frame();
                    $this->render($HTMLOUT);

                    return;
                }
                $mask = isset($_GET['mask']) ? htmlsafechars($_GET['mask']) : '';
                $params = ['search_ip' => $ip];
                if ($mask == '' || $mask === '255.255.255.255') {
                    $where1 = 'u.ip = :search_ip';
                    $where2 = 'ips.ip = :search_ip';
                    $dom = @gethostbyaddr($ip);
                    if ($dom == $ip || @gethostbyname($dom) != $ip) {
                        $addr = '';
                    } else {
                        $addr = $dom;
                    }
                } else {
                    if (substr($mask, 0, 1) == '/') {
                        $n = substr($mask, 1, strlen($mask) - 1);
                        if (!is_numeric($n) || $n < 0 || $n > 32) {
                            $HTMLOUT .= stdmsg(_('Error'), _('Invalid subnet mask.'));
                            $HTMLOUT .= end_main_frame();
                            $this->render($HTMLOUT);

                            return;
                        }
                        $mask = long2ip(pow(2, 32) - pow(2, 32 - (int) $n));
                    } elseif (!preg_match($regex, $mask)) {
                        $HTMLOUT .= stdmsg(_('Error'), _('Invalid subnet mask.'));
                        $HTMLOUT .= end_main_frame();
                        $this->render($HTMLOUT);

                        return;
                    }
                    $params['mask'] = $mask;
                    $where1 = 'INET_ATON(u.ip) & INET_ATON(:mask) = INET_ATON(:search_ip) & INET_ATON(:mask)';
                    $where2 = 'INET_ATON(ips.ip) & INET_ATON(:mask) = INET_ATON(:search_ip) & INET_ATON(:mask)';
                    $addr = _('Mask') . ": $mask";
                }
                $queryc = "SELECT COUNT(id) FROM
           (
             SELECT u.id FROM users AS u WHERE $where1
             UNION SELECT u.id FROM users AS u RIGHT JOIN ips ON u.id=ips.userid WHERE $where2
             GROUP BY u.id
           ) AS ipsearch";
                $countValue = $db->fetchValue($queryc, $params);
                $count = $countValue !== null ? (int) $countValue : 0;
                if ($count == 0) {
                    $HTMLOUT .= "<br><b>No users found</b>\n";
                    $HTMLOUT .= end_main_frame();
                    $this->render($HTMLOUT);

                    return;
                }
                $order = isset($_GET['order']) && $_GET['order'] ? (string) $_GET['order'] : '';
                $perpage = 20;
                $pager = pager($perpage, $count, "staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=$ip&amp;mask=$mask&amp;order=$order&amp;");
                $orderby = match ($order) {
                    'registered' => 'registered DESC',
                    'username' => 'UPPER(username) ASC',
                    'email' => 'email ASC',
                    'last_ip' => 'last_ip ASC',
                    'last_access' => 'last_ip ASC',
                    default => 'access DESC',
                };
                $query1 = "SELECT * FROM (
          SELECT u.id, u.username, INET6_NTOA(u.ip) AS ip, INET6_NTOA(u.ip) AS last_ip, u.last_access, u.last_access AS access,u.email, u.invitedby, u.registered, u.class, u.uploaded, u.downloaded, u.donor, u.status, u.warned, u.leechwarn, u.chatpost, u.pirate, u.king
          FROM users AS u
          WHERE $where1
          UNION SELECT u.id, u.username, INET6_NTOA(ips.ip) AS ip, INET6_NTOA(u.ip) as last_ip, u.last_access, max(ips.lastlogin) AS access, u.email, u.invitedby, u.registered, u.class, u.uploaded, u.downloaded, u.donor, u.status, u.warned, u.leechwarn, u.chatpost, u.pirate, u.king
          FROM users AS u
          RIGHT JOIN ips ON u.id=ips.userid
          WHERE $where2
          GROUP BY u.id ) as ipsearch
          GROUP BY id
          ORDER BY $orderby
          " . $pager['limit'] . '';
                $users = $db->fetchAll($query1, $params);
                $HTMLOUT .= begin_frame("$count " . _('users have used the IP') . ': ' . format_comment($ip) . ' (' . format_comment($addr) . ')', true);
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagertop'];
                }
                $HTMLOUT .= "<table>\n";
                $HTMLOUT .= "<tr>
      <td class='colhead'><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=$ip&amp;mask=$mask&amp;order=username'>" . _('Username') . '</a></td>' . "<td class='colhead'>" . _('Ratio') . '</td>' . "<td class='colhead'><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=$ip&amp;mask=$mask&amp;order=email'>" . _('Email') . '</a></td>' . "<td class='colhead'><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=$ip&amp;mask=$mask&amp;order=last_ip'>" . _('Last IP') . '</a></td>' . "<td class='colhead'><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=$ip&amp;mask=$mask&amp;order=last_access'>" . _('Last access') . '</a></td>' . "<td class='colhead'>" . _("Num of IP's") . '</td>' . "<td class='colhead'><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=$ip&amp;mask=$mask'>" . _('Last access') . ' on <br>' . format_comment($ip) . '</a></td>' . "<td class='colhead'><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=ipsearch&amp;action=ipsearch&amp;ip=$ip&amp;mask=$mask&amp;order=added'>" . _('Added') . '</a></td>' . "<td class='colhead'>" . _('Invited by') . '</td></tr>';
                foreach ($users as $user) {
                    if ($user['registered'] == '0') {
                        $user['registered'] = '---';
                    }
                    if ($user['last_access'] == '0') {
                        $user['last_access'] = '---';
                    }
                    if (!empty($user['last_ip'])) {
                        $banCount = $db->fetchValue(
                            'SELECT COUNT(id) FROM bans WHERE INET6_NTOA(first) <= :last_ip AND INET6_NTOA(last) >= :last_ip',
                            ['last_ip' => $user['last_ip']],
                        );
                        $banCount = $banCount !== null ? (int) $banCount : 0;
                        if ($banCount === 0) {
                            $ipstr = $user['last_ip'];
                        } else {
                            $ipstr = "<a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=testip&amp;action=testip&amp;ip=" . htmlsafechars($user['last_ip']) . "'><span style='color: #FF0000;'><b>" . format_comment($user['last_ip']) . '</b></span></a>';
                        }
                    } else {
                        $ipstr = '---';
                    }
                    $ipHistoryRows = $db->fetchAll('SELECT INET6_NTOA(ip) AS ip FROM ips WHERE userid = :userid GROUP BY ips.ip', ['userid' => (int) $user['id']]);
                    $iphistory = count($ipHistoryRows);
                    if ((int) $user['invitedby'] > 0) {
                        $inviter = $db->row('SELECT id, username FROM users WHERE id = :id', ['id' => (int) $user['invitedby']]);
                        if ($inviter === null) {
                            $invitedby = '<i>[' . _('Deleted') . ']</i>';
                        } else {
                            $invitedby = format_username((int) $inviter['id']);
                        }
                    } else {
                        $invitedby = '--';
                    }
                    $HTMLOUT .= '<tr>
           <td>' . format_username((int) $user['id']) . '</td>' . '<td>' . member_ratio((float) $user['uploaded'], (float) $user['downloaded']) . '</td>
          <td>' . $user['email'] . '</td><td>' . $ipstr . '</td>
          <td><div>' . get_date((int) $user['last_access'], 'DATE', 1, 0) . "</div></td>
          <td><div><b><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=iphistory&amp;action=iphistory&amp;id=" . (int) $user['id'] . "'>$iphistory</a></b></div></td>
          <td><div>" . get_date((int) $user['access'], 'DATE', 1, 0) . '</div></td>
          <td><div>' . get_date((int) $user['registered'], 'DATE', 1, 0) . '</div></td>
          <td><div>' . $invitedby . "</div></td>
          </tr>\n";
                }
                $HTMLOUT .= '</table>';
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagerbottom'];
                }
                $HTMLOUT .= end_frame();
            }
            $HTMLOUT .= end_main_frame();
            $this->render($HTMLOUT);
            // LEGACY BODY END
        } catch (\Throwable $e) {
            error_log('Admin controller error (ipsearch): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }

    private function render(string $HTMLOUT): void
    {
        $config = $this->config;
        $title = _('IP Search');
        $breadcrumbs = [
            "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
            "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
        ];
        echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
    }
}
