<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap_web.php';
require_once dirname(__DIR__) . '/include/helpers/audit.php';

use PU239\Config\ConfigRepository;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;

global $container, $CURUSER;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);
$db = $container->get(Database::class);

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);

/** @var array<int, array<string, mixed>> $sql_updates */
$sql_updates = require DATABASE_DIR . 'sql_updates.php';

// $fluent removed — use $this->db (ExtendedPdo)
$cache = $container->get(Cache::class);
$session = $container->get(Session::class);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id']) && !empty($_POST['submit'])) {
        $id = $_POST['id'];
        $submit = $_POST['submit'];
        $qid = array_search($id, array_column($sql_updates, 'id'));
        $sql = $sql_updates[$qid]['query'];
        $updateId = $sql_updates[$qid]['id'] ?? null;

        if (isset($qid) && $submit === 'Run Query') {
            $flush = $sql_updates[$qid]['flush'];

            try {
                $query = $fluent->getPdo()
                                ->prepare($sql);
                $query->execute();
                $values = [
                    'id' => (int) $id,
                    'query' => $sql,
                ];
                $sql = "INSERT INTO database_updates (/* columns */) VALUES (/* values */)";
                $db->perform($sql, $values);

                if ($flush) {
                    $cache->flushDB();
                    $session->set('is-success', 'You flushed the ' . ucfirst((string) $config->get('cache.driver')) . ' cache');
                } elseif (!$flush) {
                    // do nothing
                } else {
                    $items = explode(', ', $flush);
                    foreach ($items as $item) {
                        $cache->delete($item);
                        $session->set('is-success', "You flushed $item cache");
                    }
                }
                $session->set('is-success', "Query #$id ran without error");
                audit_log(
                    $CURUSER['id'] ?? null,
                    'config.update',
                    [
                        'keys' => $updateId !== null ? [$updateId] : [],
                        'action' => 'run',
                    ],
                );
            } catch (Exception $e) {
                $code = $e->getCode();
                $msg = $e->getMessage();
                if ($code === '42S21') {
                    $session->set('is-danger', "{$msg}[p]\n you should be safe if you ignore this query[/p][p]" . htmlsafechars($sql) . '[/p]');
                } else {
                    $session->set('is-danger', "{$msg}[p]\n try to run manually:[/p][p]" . htmlsafechars($sql) . '[/p]');
                }
            }
        } elseif (isset($qid) && $submit === 'Ignore Query') {
            $values = [
                'id' => (int) $id,
                'query' => $sql,
            ];
            $sql = "INSERT INTO database_updates (/* columns */) VALUES (/* values */)";
            $db->perform($sql, $values);
            $session->set('is-success', "Query #$id has been ignored");
            audit_log(
                $CURUSER['id'] ?? null,
                'config.update',
                [
                    'keys' => $updateId !== null ? [$updateId] : [],
                    'action' => 'ignore',
                ],
            );
        }
    }
}

$heading = "
        <tr>
            <th class='has-text-centered w-10'>
                ID
            </th>
            <th class='has-text-centered'>
                Info
            </th>
            <th class='has-text-centered'>
                Date
            </th>
            <th class='has-text-centered'>
                Query
            </th>
            <th class='has-text-centered w-10'>
                Status
            </th>
        </tr>";

if (file_exists(DATABASE_DIR)) {
    $results = $fluent->from('database_updates')
                      ->select(null)
                      ->select('id')
                      ->select('added')
                      ->fetchPairs('id', 'added');

    $results = !empty($results) ? $results : [0 => '2017-12-06 14:43:22'];

    $body = '';
    foreach ($sql_updates as $update) {
        if (array_key_exists($update['id'], $results)) {
            continue;
        }

        $button = "
                <form action='{$_SERVER['PHP_SELF']}?tool=upgrade_database' method='post' enctype='multipart/form-data' accept-charset='utf-8'>
                    <div class='level-center'>
                        <span class='margin10'>
                            <input type='hidden' name='id' value={$update['id']}>
                            <input class='button is-small' type='submit' name='submit' value='Run Query'>
                        </span>
                        <span class='margin10'>
                            <input type='hidden' name='id' value={$update['id']}>
                            <input class='button is-small' type='submit' name='submit' value='Ignore Query'>
                        </span
                    </div>
                </form>";
        $body .= "
        <tr>
            <td class='has-text-centered'>
                {$update['id']}
            </td>
            <td>
                {$update['info']}
            </td>
            <td class='has-text-centered'>
                " . (array_key_exists($update['id'], $results) ? $results[$update['id']] : $update['date']) . "
            </td>
            <td>
                {$update['query']}
            </td>
            <td class='has-text-centered'>
                " . (array_key_exists($update['id'], $results) ? 'Completed' : $button) . '
            </td>
        </tr>';
    }

    if (empty($body)) {
        $body = "
        <tr>
            <td colspan='5'>
                There are no updates available!
            </td>
        </tr>";
    }
} else {
    $body = "
        <tr>
            <td colspan='5'>
                'Path Missing: => " . DATABASE_DIR . '
            </td>
        </tr>';
}

$HTMLOUT = wrapper(main_table($body, $heading));
$title = _('Update Database');
$breadcrumbs = [
    "<a href='{$config->get('paths.baseurl')}/staffpanel.php'>" . _('Staff Panel') . '</a>',
    "<a href='{$_SERVER['PHP_SELF']}'>$title</a>",
];
echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($HTMLOUT) . stdfoot();
