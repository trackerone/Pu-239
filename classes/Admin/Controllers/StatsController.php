<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use PU239\Config\ConfigRepository;
use PU239\Security\AuthZ;
use Psr\Container\ContainerInterface;
use Pu239\Database;
use Pu239\Roles;

final class StatsController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
        private readonly \PDO $pdo,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23T00:00:00Z; tool=codex-admin-convert; rules=2025.10.23
        try {
            // LEGACY BODY START (from admin/stats.php)
            // Keep query building and rendering logic as-is.
            // TODO(2025): move stat query to AdminStatsService
            // TODO(2025): group stats into single response object
            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            $container = $this->container;
            /** @var ConfigRepository $config */
            $config = $this->config;
            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $HTMLOUT = '';
            //$HTMLOUT .= begin_main_frame();
            $rows = $db->fetchAll('SELECT COUNT(id) FROM torrents');
            $n = mysqli_fetch_row($res);
            $n_tor = $n[0];
            $rows = $db->fetchAll('SELECT COUNT(id) FROM peers');
            $n = mysqli_fetch_row($res);
            $n_peers = $n[0];
            $uporder = isset($_GET['uporder']) ? $_GET['uporder'] : '';
            $catorder = isset($_GET['catorder']) ? $_GET['catorder'] : '';
            if ($uporder === 'lastul') {
                $orderby = 'last DESC, name';
            } elseif ($uporder === 'torrents') {
                $orderby = 'n_t DESC, name';
            } elseif ($uporder === 'peers') {
                $orderby = 'n_p DESC, name';
            } else {
                $orderby = 'name';
            }
            // TODO(2025): manual DB refactor (complex search query)
            $query = 'SELECT u.id, u.username AS name, MAX(t.added) AS last, COUNT(DISTINCT t.id) AS n_t, COUNT(p.id) as n_p FROM users as u
        LEFT JOIN torrents as t ON u.id = t.owner
        LEFT JOIN peers as p ON t.id = p.torrent
        WHERE u.roles_mask & ' . Roles::UPLOADER . '
        GROUP BY u.id
        UNION SELECT u.id, u.username AS name, MAX(t.added) AS last, COUNT(DISTINCT t.id) AS n_t, COUNT(p.id) as n_p FROM users as u
        LEFT JOIN torrents as t ON u.id = t.owner
        LEFT JOIN peers as p ON t.id = p.torrent
        WHERE u.roles_mask & ' . Roles::UPLOADER . "
        GROUP BY u.id
        ORDER BY $orderby";
            $res = sql_query($query) or sqlerr(__FILE__, __LINE__);
            $perpage = 25;
            $count = mysqli_num_rows($res);
            $pager = pager($perpage, $count, "{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;");
            if ($count > $perpage) {
                // TODO(2025): manual DB refactor (complex search query)
                $query = 'SELECT u.id, u.username AS name, MAX(t.added) AS last, COUNT(DISTINCT t.id) AS n_t, COUNT(p.id) as n_p FROM users as u
        LEFT JOIN torrents as t ON u.id = t.owner
        LEFT JOIN peers as p ON t.id = p.torrent
        WHERE u.roles_mask & ' . Roles::UPLOADER . '
        GROUP BY u.id
        UNION SELECT u.id, u.username AS name, MAX(t.added) AS last, COUNT(DISTINCT t.id) AS n_t, COUNT(p.id) as n_p FROM users as u
        LEFT JOIN torrents as t ON u.id = t.owner
        LEFT JOIN peers as p ON t.id = p.torrent
        WHERE u.roles_mask & ' . Roles::UPLOADER . "
        GROUP BY u.id
        ORDER BY $orderby
        {$pager['limit']}";
                $res = sql_query($query) or sqlerr(__FILE__, __LINE__);
            }
            if ($count === 0) {
                stdmsg(_('Error'), _('No uploaders.'));
            } else {
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagertop'];
                }
                $heading = "
    <tr>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=uploader&amp;catorder=$catorder' class='colheadlink'>" . _('Uploader') . "</a></th>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=lastul&amp;catorder=$catorder' class='colheadlink'>" . _('Last upload') . "</a></th>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=torrents&amp;catorder=$catorder' class='colheadlink'>" . _('Torrents') . "</a></th>
        <th>Perc.</th>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=peers&amp;catorder=$catorder' class='colheadlink'>" . _('Peers') . '</a></th>
        <th>Perc.</th>
    </tr>';
                $body = '';
                while ($uper = mysqli_fetch_assoc($res)) {
                    $body .= '
    <tr>
        <td>' . format_username((int) $uper['id']) . '</td>
        <td ' . ($uper['last'] ? ('>' . get_date((int) $uper['last'], '') . ' (' . get_date((int) $uper['last'], '', 0, 1) . ')') : "align='center'>---") . "</td>
        <td>{$uper['n_t']}</td>
        <td>" . ($n_tor > 0 ? number_format(100 * $uper['n_t'] / $n_tor, 1) . '%' : '---') . '</td>
        <td>' . $uper['n_p'] . '</td>
        <td>' . ($n_peers > 0 ? number_format(100 * $uper['n_p'] / $n_peers, 1) . '%' : '---') . '</td>
    </tr>';
                }
                $HTMLOUT .= main_table($body, $heading);
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagertop'];
                }
            }
            if ($n_tor == 0) {
                stdmsg(_('Error'), _('No categories defined!'));
            } else {
                if ($catorder === 'lastul') {
                    $orderby = 'last DESC, c.name';
                } elseif ($catorder === 'torrents') {
                    $orderby = 'n_t DESC, c.name';
                } elseif ($catorder === 'peers') {
                    $orderby = 'n_p DESC, name';
                } else {
                    $orderby = 'c.name';
                }
                // TODO(2025): manual DB refactor (complex search query)
                $rows = $db->fetchAll("SELECT c.name, MAX(t.added) AS last, COUNT(DISTINCT t.id) AS n_t, COUNT(p.id) AS n_p
      FROM categories as c LEFT JOIN torrents as t ON t.category = c.id LEFT JOIN peers as p
      ON t.id=p.torrent GROUP BY c.id ORDER BY $orderby");
                $heading = "
    <tr>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=$uporder&amp;catorder=category' class='colheadlink'>" . _('Category') . "</a></th>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=$uporder&amp;catorder=lastul' class='colheadlink'>" . _('Last upload') . "</a></th>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=$uporder&amp;catorder=torrents' class='colheadlink'>" . _('Torrents') . "</a></th>
        <th>Perc.</th>
        <th><a href='{$config->get('paths.baseurl')}/staffpanel.php?tool=stats&amp;action=stats&amp;uporder=$uporder&amp;catorder=peers' class='colheadlink'>" . _('Peers') . '</a></th>
        <th>Perc.</th>
    </tr>';
                $body = '';
                while ($cat = mysqli_fetch_assoc($res)) {
                    $body .= '
    <tr>
        <td>' . htmlsafechars($cat['name']) . '</td>
        <td ' . ($cat['last'] ? ('>' . get_date((int) $cat['last'], '') . ' (' . get_date((int) $cat['last'], '', 0, 1) . ')') : "align='center'>---") . "</td>
        <td>{$cat['n_t']}</td>
        <td>" . number_format(100 * $cat['n_t'] / $n_tor, 1) . "%</td>
        <td>{$cat['n_p']}</td>
        <td>" . ($n_peers > 0 ? number_format(100 * $cat['n_p'] / $n_peers, 1) . '%' : '---') . '</td>
    </tr>';
                }
                $HTMLOUT .= main_table($body, $heading, null, 'top20');
            }
            $title = _('Stats');
            $breadcrumbs = [
                "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
            // LEGACY BODY END
        } catch (\Throwable $e) {
            error_log('Admin controller error (stats): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
