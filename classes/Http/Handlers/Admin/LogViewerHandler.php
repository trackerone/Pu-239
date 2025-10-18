<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-21T00:00:00Z via handler-convert offset=247 batch=3

namespace PU239\Http\Handlers\Admin;

use PU239\Security\AuthZ;
use Pu239\Config\ConfigRepository;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class LogViewerHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-21T00:00:00Z via handler-convert offset=247 batch=3
        try {
            require_once \dirname(__DIR__, 4) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 4) . '/include/helpers/audit.php';

            $handlerPath = __FILE__;
            if (stripos($handlerPath, '/admin/') !== false) {
                AuthZ::requireRole('admin');
            } else {
                AuthZ::requireAnyRole(['staff', 'admin']);
            }

            global $container, $CURUSER;

            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);
            $class = get_access(basename($_SERVER['REQUEST_URI'] ?? ''));
            class_check($class);

            $HTMLOUT = '';
            $content = '';
            $count = 0;
            $perpage = 50;
            $state = 'div';

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete'] ?? '') === 'Delete') {
                // TODO(2025): add CSRF verification to log deletion
                $logs = array_map('strval', (array) ($_POST['logs'] ?? []));
                $deletedLogs = [];
                foreach ($logs as $log) {
                    $logPath = urldecode($log);
                    if (is_file($logPath) && is_readable($logPath)) {
                        unlink($logPath);
                        $deletedLogs[] = $logPath;
                    }
                }

                if ($deletedLogs !== []) {
                    audit_log(
                        $CURUSER['id'] ?? null,
                        'config.update',
                        [
                            'keys' => array_map(
                                static fn(string $path): string => basename($path),
                                $deletedLogs
                            ),
                            'op' => 'log.delete',
                        ],
                    );
                }
            }

            $action = (string) ($_GET['action'] ?? '');
            if ($action === 'view') {
                $file = (string) ($_GET['file'] ?? '');
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $name = basename($file);
                $uncompress = $ext === 'gz' ? 'compress.zlib://' : '';

                if ($file !== '' && file_exists($file) && is_readable($file)) {
                    $contentRaw = file_get_contents($uncompress . $file);
                } else {
                    $contentRaw = '<b>' . $file . '</b> does not exist or is not readable';
                }

                $contentRaw = trim((string) $contentRaw);

                $dateFormats = "(\\d{4}/\\d{2}/\\d{2}\\s+\\d{2}:\\d{2}:\\d{2}.*?|\\[\\w+ \\w+ \\d+ \\d{2}:\\d{2}:\\d{2}\\.\\d+ \\d{4}\\])";
                $contents = [];
                if (!preg_match('/(sqlerr|slow\\-fpm\\.log|access\\.log|cron.*\\.log|images.*\\.log|announce\\.log)/i', $file)) {
                    preg_match_all('!' . $dateFormats . '!iU', $contentRaw, $matches);
                    if (!empty($matches[1])) {
                        $contents = $matches[1];
                    } else {
                        $contents = explode("\n", $contentRaw);
                    }
                } elseif (preg_match('/slow\\-fpm\\.log/', $name)) {
                    $tempContents = preg_split('!(\\[\\d+\\-\\w+\\-\\d{4}\\s+\\d{2}:\\d{2}:\\d{2}\\])!iU', $contentRaw, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                    if (!empty($tempContents)) {
                        $contents = [];
                        $i = 1;
                        $temp = '';
                        foreach ($tempContents as $row) {
                            $temp .= $row;
                            if ($i++ % 2 === 0) {
                                $contents[] = $temp;
                                $temp = '';
                            }
                        }
                    }
                    $state = 'pre';
                } elseif (preg_match('/access\\.log/', $name)) {
                    preg_match_all('!(\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}.*?)!iU', $contentRaw, $matches);
                    if (!empty($matches[1])) {
                        $contents = $matches[1];
                    } else {
                        $contents = explode("\n", $contentRaw);
                    }
                } else {
                    $contents = explode('===================================================', $contentRaw);
                    $state = 'pre';
                }

                if (!empty($contents)) {
                    $contents = array_reverse($contents);
                    $count = count($contents);
                    $pager = pager($perpage, $count, $config->get('paths.baseurl') . '/staffpanel.php?tool=log_viewer&action=view&file=' . htmlsafechars($file) . '&');
                } else {
                    $pager = pager($perpage, 0, '');
                }

                $i = 0;
                $formatted = [];
                foreach ($contents as $line) {
                    if ($line === '' || $line === false) {
                        continue;
                    }
                    ++$i;
                    $className = $i % 2 === 0 ? 'bg-08 simple_border round10 padding20 has-text-black bottom5' : 'bg-light simple_border round10 padding20 has-text-black bottom5';
                    $line = trim((string) $line);
                    $formatted[] = "<$state class='{$className}'>{$line}</$state>";
                    if ($i >= $pager['pdo']['limit'] + $pager['pdo']['offset']) {
                        break;
                    }
                }

                $content = ($count > $perpage ? $pager['pagertop'] : '') . implode("\n", $formatted) . ($count > $perpage ? $pager['pagerbottom'] : '');

                $HTMLOUT = main_div("\n        <div class='bg-00 round10'>\n            <div class='size_7 has-text-centered padding20'>Viewing Log: " . htmlsafechars($file) . "</div>$content\n        </div>", 'bottom20');
            }

            $pathsConfig = (array) $config->get('paths.log_viewer');
            $paths = array_merge($pathsConfig, [LOGS_DIR]);
            $files = [];
            foreach ($paths as $path) {
                if ($path === '' || !file_exists($path) || !is_readable($path)) {
                    continue;
                }
                $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                $exts = ['log', 'gz', '1'];
                foreach ($objects as $name => $object) {
                    $ext = pathinfo((string) $name, PATHINFO_EXTENSION);
                    if (!in_array($ext, $exts, true)) {
                        continue;
                    }
                    $size = filesize($name);
                    if ($size > 0 && is_readable($name)) {
                        $files[] = (string) $name;
                    }
                }
            }

            natsort($files);
            $files = array_reverse($files, false);

            if (!empty($files)) {
                $heading = "
        <tr>
            <th>Filename</th>
            <th class='has-text-centered'>Date</th>
            <th class='has-text-centered'>Size</th>
            <th class='has-text-centered'><input type='checkbox' id='checkThemAll' class='tooltipper' title='Select All'></th>
        </tr>";
                $body = '';
                foreach ($files as $logFile) {
                    $checked = (!empty($_GET['file']) && $_GET['file'] === $logFile) ? 'checked' : '';
                    $body .= "
        <tr>
            <td>
                <a href='{$_SERVER['PHP_SELF']}?tool=log_viewer&amp;action=view&amp;file=" . htmlsafechars($logFile) . "'>$logFile</a>
            </td>
            <td class='has-text-centered'>
                " . get_date((int) filemtime($logFile), 'LONG') . "
            </td>
            <td class='has-text-right w-10'>
                " . mksize(filesize($logFile)) . "
            </td>
            <td class='has-text-centered w-10'>
                <input type='checkbox' name='logs[]' value='" . urlencode($logFile) . "' {$checked}>
            </td>
        </tr>";
                }
                $HTMLOUT .= "
        <form action='{$_SERVER['PHP_SELF']}?tool=log_viewer' method='post' name='checkme' enctype='multipart/form-data' accept-charset='utf-8'>" . main_table($body, $heading) . "
            <div class='has-text-centered margin20'>
                <input type='submit' class='button is-small' name='delete' value='Delete'>
            </div>
        </form>";
            } else {
                $HTMLOUT .= main_div('There are no log files to view', '', 'padding20');
            }

            $title = _('Log Files');
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
