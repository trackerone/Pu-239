<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-11 via handler-convert (batch=125-5)

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use Pu239\Peer;
use Pu239\Session;

final class ViewPeersHandler
{
    /**
     * @param array<string, mixed> $meta
     */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-11 via handler-convert (batch=125-5)
        try {
            global $container, $CURUSER;

            AuthZ::requireRole('admin');

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);
            /** @var Peer $peer */
            $peer = $container->get(Peer::class);
            /** @var Session $session */
            $session = $container->get(Session::class);

            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $count = (int) $peer->get_count();
            $peersperpage = 25;
            $HTMLOUT = "
    <h1 class='has-text-centered'>" . _('Site Peers') . "</h1>
    <div class='size_4 has-text-centered margin20'>" . _pfe('There is {0} peer currently on the tracker', 'There are {0} peers currently on the tracker', $count) . '</div>';

            $valid_sort = [
                'id',
                'userid',
                'name',
                'ip',
                'port',
                'agent',
                'uploaded',
                'downloaded',
                'connectable',
                'seeder',
                'started',
                'ts',
                'uploadoffset',
                'downloadoffset',
                'to_go',
                'size',
                'delete',
            ];
            $columnIndex = isset($_GET['sort']) && is_numeric($_GET['sort']) ? (int) $_GET['sort'] : null;
            $column = $columnIndex !== null && array_key_exists($columnIndex, $valid_sort) ? $valid_sort[$columnIndex] : 'started';

            if (isset($_GET['delete']) && is_valid_id((int) $_GET['delete'])) {
                // TODO(2025): add CSRF protection for peer deletion
                $db->run('DELETE FROM peers WHERE id = :id', [':id' => (int) $_GET['delete']]);
                $session->set('is-success', 'Peer ' . (int) $_GET['delete'] . ' has been deleted.');
                audit_log(
                    $CURUSER['id'] ?? null,
                    'torrent.moderate',
                    [
                        'id' => (int) $_GET['delete'],
                        'op' => 'peer.delete',
                    ],
                );
            }

            $pagerlink = '';
            $ascdesc = '';
            $type = isset($_GET['type']) ? $_GET['type'] : 'desc';
            $link = [];
            foreach ($valid_sort as $key => $value) {
                if ($value === $column) {
                    switch (htmlsafechars((string) $type)) {
                        case 'desc':
                            $ascdesc = 'DESC';
                            $linkascdesc = 'desc';
                            break;
                        case 'asc':
                        default:
                            $ascdesc = '';
                            $linkascdesc = 'asc';
                            break;
                    }
                    $pagerlink = "sort={$key}&amp;type={$linkascdesc}&amp;";
                }
                $link[$key] = 'desc';
            }

            if ($columnIndex !== null) {
                $link[$columnIndex] = isset($type) && $type === 'desc' ? 'asc' : 'desc';
            }

            $pager = pager($peersperpage, $count, $config->get('paths.baseurl') . '/staffpanel.php?tool=view_peers&amp;' . $pagerlink);
            if ($count > $peersperpage) {
                $HTMLOUT .= $pager['pagertop'];
            }

            $results = $peer->get_all_peers($pager['pdo']['limit'], $pager['pdo']['offset'], $column, $ascdesc);
            if (!empty($results)) {
                $self = $_SERVER['PHP_SELF'] ?? '';
                $heading = "
    <tr>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=1&amp;type={$link[1]}'>" . _('User') . "</a></th>
        <th class='has-text-centered min-150'><a href='{$self}?tool=view_peers&amp;sort=2&amp;type={$link[2]}'>" . _('Torrent') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=3&amp;type={$link[3]}'>" . _('IP') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=4&amp;type={$link[4]}'>" . _('Port') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=5&amp;type={$link[5]}'>" . _('Client') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=6&amp;type={$link[6]}'>" . _('Up') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=7&amp;type={$link[7]}'>" . _('Dn') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=8&amp;type={$link[8]}'>" . _('Con') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=9&amp;type={$link[9]}'>" . _('Seed') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=10&amp;type={$link[10]}'>" . _('Start') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=11&amp;type={$link[11]}'>" . _('Last') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=12&amp;type={$link[12]}'>" . _('Up/Off') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=13&amp;type={$link[13]}'>" . _('Dn/Off') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=14&amp;type={$link[14]}'>" . _('To Go') . "</a></th>
        <th class='has-text-centered'><a href='{$self}?tool=view_peers&amp;sort=15&amp;type={$link[15]}'>" . _('Size') . "</a></th>
        <th class='has-text-centered'>Delete</th>
    </tr>";
                $body = '';
                foreach ($results as $row) {
                    $smallname = format_comment($row['name']);
                    $body .= '
    <tr>
        <td class="has-text-centered">' . format_username((int) $row['userid']) . '</td>
        <td><a href="' . $config->get('paths.baseurl') . '/details.php?id=' . (int) $row['torrent'] . '">' . $smallname . '</a></td>
        <td class="has-text-centered">' . htmlsafechars((string) $row['ip']) . '</td>
        <td class="has-text-centered">' . (int) $row['port'] . '</td>
        <td class="has-text-centered">' . htmlsafechars(getagent($row['agent'], $row['peer_id'])) . '</td>
        <td class="has-text-centered">' . mksize((int) $row['uploaded']) . '</td>
        <td class="has-text-centered">' . mksize((int) $row['downloaded']) . '</td>
        <td class="has-text-centered">' . ($row['connectable'] == 'yes' ? "<i class='icon-ok icon has-text-success tooltipper' title='" . _('Yes') . "'></i>" : "<i class='icon-cancel icon has-text-danger tooltipper' title='" . _('No') . "'></i>") . '</td>
        <td class="has-text-centered">' . ($row['seeder'] == 'yes' ? "<i class='icon-ok icon has-text-success tooltipper' title='" . _('Yes') . "'></i>" : "<i class='icon-cancel icon has-text-danger tooltipper' title='" . _('No') . "'></i>") . '</td>
        <td class="has-text-centered">' . get_date((int) $row['started'], 'DATE', 0, 1) . '</td>
        <td class="has-text-centered">' . get_date((int) $row['ts'], 'DATE', 0, 1) . '</td>
        <td class="has-text-centered">' . mksize((int) $row['uploadoffset']) . '</td>
        <td class="has-text-centered">' . mksize((int) $row['downloadoffset']) . '</td>
        <td class="has-text-centered">' . mksize((int) $row['to_go']) . '</td>
        <td class="has-text-centered">' . mksize((int) $row['size']) . '</td>
        <td class="has-text-centered">
            <a href="' . $self . '?tool=view_peers&amp;delete=' . (int) $row['id'] . '" class="tooltipper" title="Delete Peer">
                <i class="icon-trash-empty icon has-text-danger" aria-hidden="true"></i>
            </a>
        </td>
    </tr>';
                }

                $HTMLOUT .= main_table($body, $heading);
            } else {
                stderr(_('Error'), _('No peers found'), 'bottom20');
            }

            if ($count > $peersperpage) {
                $HTMLOUT .= $pager['pagerbottom'];
            }

            $title = _('Peers Overview');
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
