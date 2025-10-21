<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use Pu239\Database;
use RuntimeException;

use function basename;
use function dirname;
use function preg_quote;
use function str_replace;
use function htmlsafechars;
use function preg_match;
use function strip_tags;

final class MysqlOverviewHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once dirname(__DIR__, 4) . '/include/helpers/audit.php';

            if (!defined('PU239_ROUTED')) {
                require_once dirname(__DIR__, 4) . '/public/index.php';

                return;
            }

            global $container;
            if (!isset($container)) {
                throw new RuntimeException('Global container not initialized');
            }

            AuthZ::requireRole('admin');

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            /** @var Database $db */
            $db = $container->get(Database::class);

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';
            global $CURUSER;

            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $class = get_access(basename((string) $requestUri));
            class_check($class);

            $baseUrl = (string) $config->get('paths.baseurl');
            $self = $_SERVER['PHP_SELF'] ?? '';

            if (isset($_GET['Do'], $_GET['table']) && $_GET['Do'] === 'optimize') {
                $rawTable = htmlsafechars(strip_tags((string) $_GET['table']));
                if ($rawTable === '' || preg_match('/[^A-Za-z_]/', $rawTable)) {
                    stderr(_('Error'), _('Invalid Data!'));
                }

                $tableName = '`' . $rawTable . '`';
                $sql = "OPTIMIZE TABLE {$tableName}";
                if (preg_match('@^(CHECK|ANALYZE|REPAIR|OPTIMIZE)[[:space:]]TABLE[[:space:]]' . preg_quote($tableName, '@') . '$@i', $sql)) {
                    $db->pdo()->prepare($sql)->execute();
                    audit_log($CURUSER['id'] ?? null, 'config.update', [
                        'keys' => ['mysql.optimize'],
                        'table' => $rawTable,
                    ]);
                    header("Location: {$self}?tool=mysql_overview&action=mysql_overview");
                    app_halt('Exit called');
                }
            }

            $title = _('MySQL Overview');
            $breadcrumbs = [
                "<a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>$title</a>",
            ];

            $statement = $db->pdo()->prepare('SHOW TABLE STATUS');
            $statement->execute();
            $tables = $statement->fetchAll();

            $innodb = true;
            foreach ($tables as $table) {
                if (($table['Engine'] ?? '') !== 'InnoDB') {
                    $innodb = false;
                    break;
                }
            }

            $heading = '        <tr>'
                . "            <th>" . _('Name') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Rows') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Avg Row Length') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Data Size') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Index Size') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Table Size') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Overhead (Waste)') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Auto Increment') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Row Format') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Collation') . '</th>'
                . "            <th class='has-text-centered is-wrapped'>" . _('Create Time') . '</th>'
                . (!$innodb
                    ? "            <th class='has-text-centered is-wrapped'>" . _('Update Time') . '</th>'
                        . "            <th class='has-text-centered is-wrapped'>" . _('Check Time') . '</th>'
                    : '')
                . '        </tr>';

            $body = '';
            $count = 0;
            foreach ($tables as $table) {
                $avgLength = mksize((int) ($table['Avg_row_length'] ?? 0), 0);
                $dataLength = mksize((int) ($table['Data_length'] ?? 0), 0);
                $indexLength = mksize((int) ($table['Index_length'] ?? 0), 0);
                $dataFreeBytes = (int) ($table['Data_free'] ?? 0);
                $dataFree = mksize($dataFreeBytes, 0);
                $tableSizeBytes = (int) ($table['Data_length'] ?? 0) + (int) ($table['Index_length'] ?? 0);
                $tableLength = mksize($tableSizeBytes, 0);
                $updateTime = $table['Update_time'] ?? 'null';
                $checkTime = $table['Check_time'] ?? 'null';
                $autoIncrement = isset($table['Auto_increment']) ? number_format((int) $table['Auto_increment']) : 'null';
                $overhead = $dataFree;
                if ($dataFreeBytes > 1024 * 1024 * 10) {
                    $link = "{$baseUrl}/staffpanel.php?tool=mysql_overview&amp;action=mysql_overview&amp;Do=optimize&amp;table=" . urlencode((string) ($table['Name'] ?? ''));
                    $overhead = "<a href='{$link}'><span class='has-text-danger has-text-weight-bold'>{$dataFree}</span></a>";
                }

                $rowFormat = ($table['Engine'] ?? '') . '::' . ($table['Row_format'] ?? '');
                $collation = str_replace('_', ' ', (string) ($table['Collation'] ?? ''));

                $body .= '        <tr>'
                    . "            <td>" . ($table['Name'] ?? '') . '</td>'
                    . "            <td class='has-text-centered is-wrapped'>" . ($table['Rows'] ?? 0) . '</td>'
                    . "            <td class='has-text-centered is-wrapped'>{$avgLength}</td>"
                    . "            <td class='has-text-centered is-wrapped'>{$dataLength}</td>"
                    . "            <td class='has-text-centered is-wrapped'>{$indexLength}</td>"
                    . "            <td class='has-text-centered is-wrapped'>{$tableLength}</td>"
                    . "            <td class='has-text-centered is-wrapped'>{$overhead}</td>"
                    . "            <td class='has-text-centered is-wrapped'>{$autoIncrement}</td>"
                    . "            <td class='has-text-centered is-wrapped'>{$rowFormat}</td>"
                    . "            <td class='has-text-centered is-wrapped'>{$collation}</td>"
                    . "            <td class='has-text-centered is-wrapped'>" . ($table['Create_time'] ?? 'null') . '</td>'
                    . (!$innodb
                        ? "            <td class='has-text-centered is-wrapped'>{$updateTime}</td>"
                            . "            <td class='has-text-centered is-wrapped'>{$checkTime}</td>"
                        : '')
                    . '        </tr>';
                ++$count;
            }

            $body .= '        <tr>'
                . "            <td><b>" . _('Tables') . " {$count}</b></td>"
                . "            <td colspan='12'>" . _('If it is Red, it probably needs optimizing!<p>Optimizing InnoDB tables is usually not needed.</p>') . '</td>'
                . '        </tr>';

            $html = "    <h1 class='has-text-centered is-wrapped'>" . _('MySQL Server Table Status') . '</h1>';
            $html .= main_table($body, $heading);

            echo stdhead($title, [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
