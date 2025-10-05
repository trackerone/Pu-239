<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion

namespace PU239\Http\Handlers\Admin;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Pu239\Database;

final class DonationsHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-05T18:36:06Z via codex handler conversion
        try {
            global $container;

            if (strpos(ADMIN_DIR, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $escaper = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $escaper($_SERVER['PHP_SELF'] ?? '');
            $baseurl = (string) $config->get('paths.baseurl');

            $HTMLOUT = '';
            $count = 0;
            $perpage = 15;
            $rows = [];
            $pager = ['pagertop' => '', 'pagerbottom' => '', 'limit' => ''];

            if (isset($_GET['total_donors'])) {
                $total_donors = (int) ($_GET['total_donors'] ?? 0);
                if ($total_donors != 1) {
                    stderr(_('Error'), _('I smell a rat!'));
                }

                $countRow = $db->fetch('SELECT COUNT(id) AS count FROM users WHERE total_donated > 0 AND status = 0');
                $count = (int) ($countRow['count'] ?? 0);
                $pager = pager($perpage, $count, $baseurl . '/staffpanel.php?tool=donations&amp;action=donations&amp;');

                $rows = $db->fetchAll(
                    'SELECT id, username, email, registered, donated, donoruntil, total_donated
                     FROM users
                     WHERE total_donated >= 0 AND status = 0
                     ORDER BY id ' . $pager['limit']
                );
            } else {
                $countRow = $db->fetch("SELECT COUNT(id) AS count FROM users WHERE donor = 'yes' AND status = 0");
                $count = (int) ($countRow['count'] ?? 0);
                $pager = pager($perpage, $count, $baseurl . '/staffpanel.php?tool=donations&amp;action=donations&amp;');

                $rows = $db->fetchAll(
                    "SELECT id, username, email, registered, donated, donoruntil, total_donated
                     FROM users
                     WHERE donor = 'yes' AND status = 0
                     ORDER BY id " . $pager['limit']
                );
            }

            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagertop'];
            }

            $HTMLOUT .= "
    <ul class='level-center bg-06'>
        <li class='is-link margin10'>
            <a href='{$baseurl}/staffpanel.php?tool=donations&amp;action=donations'>" . _('Current Donors') . "</a>
        </li>
        <li class='is-link margin10'>
            <a href='{$baseurl}/staffpanel.php?tool=donations&amp;action=donations&amp;total_donors=1'>" . _('All Donations') . "</a>
        </li>
    </ul>
    <h1 class='has-text-centered'>Site Donations</h1>";

            $heading = '
    <tr>
        <th>' . _('ID') . '</th>
        <th>' . _('Username') . '</th>
        <th>' . _('E-mail') . '</th>
        <th>' . _('Joined') . '</th>
        <th>' . _('Donor Until?') . '</th>
        <th>' . _('Current') . '</th>
        <th>' . _('Total') . '</th>
        <th>' . _('PM') . '</th>
    </tr>';
            $body = '';
            foreach ($rows as $arr) {
                $body .= "
    <tr>
        <td>{$arr['id']}</td>
        <td>" . format_username((int) $arr['id']) . "</td>
        <td><a class='is-link' href='mailto:" . htmlsafechars($arr['email']) . "'>" . htmlsafechars($arr['email']) . "</a></td>
        <td><span class='size_3'>" . get_date((int) $arr['registered'], 'DATE') . '</span></td>
        <td>'; 
                $donoruntil = (int) $arr['donoruntil'];
                if ($donoruntil === 0) {
                    $body .= 'n/a';
                } else {
                    $body .= '<span class="size_3">' . get_date((int) $arr['donoruntil'], 'DATE') . ' [ ' . mkprettytime($donoruntil - TIME_NOW) . ' ] ' . _('To go') . '...</span>';
                }
                setlocale(LC_MONETARY, 'en_US.UTF-8');
                $body .= '
        </td>
        <td><b>' . money_format('%.2n', (float) $arr['donated']) . '</td>
        <td><b>' . money_format('%.2n', (float) $arr['total_donated']) . "</td>
        <td>
            <a class='is-link' href='{$baseurl}/messages.php?action=send_message&amp;receiver=" . (int) $arr['id'] . "'>" . _('PM') . '</a>
        </td>
    </tr>';
            }

            if ($count === 0) {
                $body = '<td colspan="8">No Donors</td>';
            }

            $HTMLOUT .= main_table($body, $heading);
            if ($count > $perpage) {
                $HTMLOUT .= $pager['pagerbottom'];
            }

            $title = _('Donations');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
