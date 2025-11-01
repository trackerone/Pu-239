<?php
declare(strict_types=1);

namespace PU239\Admin\Controllers;

use Psr\Container\ContainerInterface;
use PU239\Config\ConfigRepository;
use PDO;

use PU239\Security\AuthZ;
use Pu239\Database;
use Pu239\Session;

final class BansController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigRepository $config,
        private readonly PDO $pdo,
    ) {
    }

    /** @param array<string,mixed> $meta */
    public function __invoke(array $meta = []): void
    {
        // AUTO_ADMIN_CONVERT: 2025-10-23; tool=codex-admin-medium-require; rules=2025.10.23-admin-require
        try {
            global $container;
            $container = $this->container;
            $config = $this->config;
            $pdo = $this->pdo;

            // TODO(2025): inline legacy logic from admin/bans.php (was using legacy require)

            if (strpos(__FILE__, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container, $CURUSER;
            /** @var ContainerInterface $container */
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            // AUTO_ADMIN_MEDIUM: 2025-10-23; tool=codex-admin-medium-sweep; rules=2025.10.23-admin-medium

            $db = $container->get(Database::class);

            $class = get_access(basename($_SERVER['REQUEST_URI']));
            class_check($class);

            $session = $container->get(Session::class);
            $db = $container->get(Database::class);
            $fluent = $db;
            $s = $s ?? static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $self = $s($_SERVER['PHP_SELF'] ?? '');

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                rate_limit_or_fail();
            }
            $baseurl = $s($config->get('paths.baseurl'));
            $remove = isset($_GET['remove']) ? (int) $_GET['remove'] : 0;
            if ($remove > 0) {
                $res = $fluent->from('bans')
                              ->select(null)
                              ->select('INET6_NTOA(first) AS first')
                              ->select('INET6_NTOA(last) AS last')
                              ->where('id = ?', $remove)
                              ->fetch();

                if (!$res) {
                    stderr(_('Error'), _('A Ban with that ID could not be found'));
                }
                for ($i = $res['first']; $i <= $res['last']; ++$i) {
                    $cache->delete('bans_' . $i);
                }
                if (is_valid_id($remove)) {
                    $sql = "DELETE FROM bans WHERE id = :id";
                    $db->perform($sql, ['id' => $remove]);
                    write_log(_fe('Ban {0} was removed by {1}', $remove, $CURUSER['username']));
                    audit_log($CURUSER['id'] ?? null, 'user.unban', [
                        'target' => $remove,
                        'range' => [
                            'first' => $res['first'],
                            'last' => $res['last'],
                        ],
                    ]);
                    $session->set('is-success', _fe('IPS: {0} to {1} were removed', $res['first'], $res['last']));
                    unset($_GET);
                }
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $CURUSER['class'] >= UC_MAX) {
                // TODO(2025): csrf
                $first = htmlsafechars($_POST['first']);
                $last = htmlsafechars($_POST['last']);
                $comment = htmlsafechars($_POST['comment']);
                if (!$first || !$last || !$comment) {
                    stderr(_('Error'), _('Missing form data.'));
                }
                if (!validip($first) || !validip($last)) {
                    stderr(_('Error'), _('Invalid IP address.'));
                }
                $added = TIME_NOW;
                for ($i = $first; $i <= $last; ++$i) {
                    $cache->delete('bans_' . $i);
                }

                $values = [
                    'added' => $added,
                    'addedby' => $CURUSER['id'],
                    'first' => inet_pton($first),
                    'last' => inet_pton($last),
                    'comment' => $comment,
                ];

                $sql = "INSERT INTO bans (/* columns */) VALUES (/* values */)";
                $db->perform($sql, $values);
                audit_log($CURUSER['id'] ?? null, 'user.ban', [
                    'target' => [
                        'first' => $first,
                        'last' => $last,
                    ],
                    'comment' => $comment,
                ]);

                $key = 'bans_' . $ip;
                $session->set('is-success', "IPs: $first to $last added to Bans");
                unset($_POST);
            }

            $res = $fluent->from('bans')
                          ->select('INET6_NTOA(first) AS first')
                          ->select('INET6_NTOA(last) AS last')
                          ->orderBy('added DESC');

            foreach ($res as $arr) {
                $bans[] = $arr;
            }
            $count = !empty($bans) ? count($bans) : 0;
            $perpage = 15;
            $pager = pager($perpage, $count, 'staffpanel.php?tool=bans&amp;');

            $HTMLOUT = "
                    <h1 class='has-text-centered'>Bans</h1>
                    <div class='top20 bg-00 round10'>
                        <div class='padding20'>
                            <h2>" . _('Current bans') . '</h2>
                        </div>';
            if ($count == 0) {
                $HTMLOUT .= main_div("<div class='padding20'>" . _('Nothing found.') . '</div>');
            } else {
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagertop'];
                }
                $header = '
                            <tr>
                                <th>' . _('Added') . '</th>
                                <th>' . _('First IP') . '</th>
                                <th>' . _('Last IP') . '</th>
                                <th>' . _('By') . '</th>
                                <th>' . _('Comment') . '</th>
                                <th>' . _('Remove') . '</th>
                            </tr>';
                $body = '';
                foreach ($bans as $banned) {
                    $addedOn = $s(get_date((int) $banned['added'], ''));
                    $firstIp = $s($banned['first']);
                    $lastIp = $s($banned['last']);
                    $comment = $s($banned['comment']);
                    $banId = $s((string) $banned['id']);
                    $body .= "
                            <tr>
                                <td>{$addedOn}</td>
                                <td>{$firstIp}</td>
                                <td>{$lastIp}</td>
                                <td>" . format_username((int) $banned['addedby']) . "</td>
                                <td>{$comment}</td>
                                <td><a href='{$baseurl}/staffpanel.php?tool=bans&amp;remove={$banId}'><i class='icon-trash-empty icon tooltipper has-text-danger' title='" . _('Remove') . "'></i></a></td>
                           </tr>";
                }
                $HTMLOUT .= main_table($body, $header);
                if ($count > $perpage) {
                    $HTMLOUT .= $pager['pagerbottom'];
                }
            }
            $HTMLOUT .= '
                    </div>';
            if ($CURUSER['class'] >= UC_MAX) {
                $HTMLOUT .= "
                    <div class='top20 bg-00 round10'>
                        <div class='padding20'>
                            <h2>" . _('Add ban') . "</h2>
                        </div>
                        <form method='post' action='staffpanel.php?tool=bans' enctype='multipart/form-data' accept-charset='utf-8'>";
                $HTMLOUT .= main_table("
                            <tr>
                                <td class='rowhead'>" . _('First IP') . "</td>
                                <td><input type='text' name='first' class='w-100'></td>
                            </tr>
                            <tr>
                                <td class='rowhead'>" . _('Last IP') . "</td>
                                <td><input type='text' name='last' class='w-100'></td>
                            </tr>
                            <tr>
                                <td class='rowhead'>" . _('Comment') . "</td><td><input type='text' name='comment' class='w-100'></td>
                            </tr>");
                $HTMLOUT .= "
                            <div class='has-text-centered padding20'>
                                <input type='submit' name='okay' value='" . _('Add') . "' class='button is-small'>
                            </div>
                        </form>
                    </div>";
            }
            $title = _('Bans');
            $breadcrumbs = [
                "<a href='{$baseurl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . $s($title) . '</a>',
            ];
            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Admin controller error (bans): ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal admin error';
        }
    }
}
