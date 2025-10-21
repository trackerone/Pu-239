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
use function exec;
use function fopen;
use function fgets;
use function floor;
use function htmlsafechars;
use function implode;
use function is_string;
use function explode;
use function substr;
use function strpos;
use function trim;

final class LoadHandler
{
    /** @param array<string, mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-20T20:10:38Z via handler-convert offset=330 batch=5
        try {
            require_once dirname(__DIR__, 4) . '/bootstrap_web.php';

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
            unset($db); // retained for parity with legacy bootstrap

            require_once dirname(__DIR__, 4) . '/include/bittorrent.php';

            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $class = get_access(basename(is_string($requestUri) ? $requestUri : ''));
            class_check($class);

            $baseUrl = (string) $config->get('paths.baseurl');
            $self = htmlsafechars($_SERVER['PHP_SELF'] ?? '');

            $percent = min(100, (int) round((float) exec('ps ax | grep -c apache') / 256 * 100));
            $image = $this->loadImageForPercent($percent);
            $uptime = $this->uptime();
            $loadInfo = $this->loadAverage();

            $body = "    <div id='load' class='padding20'>"
                . "        <div style='width: 100%; height: 15px; background: url({$config->get('paths.images_baseurl')}loadbarbg.gif) repeat-x;' class='bottom20 round5'>"
                . "            <img id='load_image' style='height: 15px; width: 1px;' src='{$config->get('paths.images_baseurl')}{$image}' alt='{$percent}&#37;' class='round5'>"
                . '        </div>'
                . "        <div class='padding20'>"
                . "            <span class='columns bg-02 round10'>"
                . "                <span class='column'>" . _('Currently') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$percent}&#37; " . _('CPU usage.') . '</span>'
                . '            </span>'
                . "            <span class='columns'>"
                . "                <span class='column'>" . _('Uptime') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$uptime}</span>"
                . '            </span>'
                . "            <span class='columns bg-02 round10'>"
                . "                <span class='column'>" . _('Load average for processes running for the past minute') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$loadInfo['last1']}</span>"
                . '            </span>'
                . "            <span class='columns'>"
                . "                <span class='column'>" . _('Load average for processes running for the past 5 minutes') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$loadInfo['last5']}</span>"
                . '            </span>'
                . "            <span class='columns bg-02 round10'>"
                . "                <span class='column'>" . _('Load average for processes running for the past 15 minutes') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$loadInfo['last15']}</span>"
                . '            </span>'
                . "            <span class='columns'>"
                . "                <span class='column'>" . _('Number of tasks currently running') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$loadInfo['tasks']}</span>"
                . '            </span>'
                . "            <span class='columns bg-02 round10'>"
                . "                <span class='column'>" . _('Number of processes currently running') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$loadInfo['processes']}</span>"
                . '            </span>'
                . "            <span class='columns'>"
                . "                <span class='column'>" . _('PID of last process executed') . ": </span><span class='has-text-success column has-text-right is-one-third'>{$loadInfo['lastpid']}</span>"
                . '            </span>'
                . '        </div>'
                . '    </div>';

            $html = "    <h1 class='has-text-centered'>" . _('Server Load') . '</h1>';
            $html .= main_div($body);
            $html .= "    <script>"
                . '        var percent = ' . $percent . ';'
                . "        var width = document.getElementById('load').offsetWidth;"
                . '        width = Math.ceil(width / 100 * percent);'
                . "        document.getElementById('load_image').style.width = width + 'px';"
                . '    </script>';

            $breadcrumbs = [
                "<a href='{$baseUrl}/staffpanel.php'>" . _('Staff Panel') . '</a>',
                "<a href='{$self}'>" . _('Server Load') . '</a>',
            ];

            echo stdhead(_('Server Load'), [], 'page-wrapper', $breadcrumbs) . wrapper($html) . stdfoot();
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }

    private function loadImageForPercent(int $percent): string
    {
        if ($percent <= 70) {
            return 'loadbargreen.gif';
        }

        if ($percent <= 90) {
            return 'loadbaryellow.gif';
        }

        return 'loadbarred.gif';
    }

    private function uptime(): string
    {
        $filename = '/proc/uptime';
        $handle = fopen($filename, 'r');
        if ($handle === false) {
            return _('Could not retrieve uptime');
        }

        $line = fgets($handle, 64);
        fclose($handle);
        if ($line === false) {
            return _('Could not retrieve uptime');
        }

        $uptime = (float) substr($line, 0, (int) strpos($line, ' '));
        $segments = [
            2419200 => _('month'),
            604800 => _('week'),
            86400 => _('day'),
            3600 => _('hour'),
            60 => _('minute'),
        ];

        $parts = [];
        foreach ($segments as $seconds => $label) {
            $value = (int) floor($uptime / $seconds);
            if ($value > 0) {
                $parts[] = $value . ' ' . $label . $this->pluralSuffix($value);
                $uptime -= $value * $seconds;
            }
        }

        if ($parts === []) {
            return _('less than one minute');
        }

        return implode(', ', $parts);
    }

    /**
     * @return array{last1:string,last5:string,last15:string,tasks:string,processes:string,lastpid:string}
     */
    private function loadAverage(): array
    {
        $filename = '/proc/loadavg';
        $handle = fopen($filename, 'r');
        if ($handle === false) {
            return [
                'last1' => _('N/A'),
                'last5' => _('N/A'),
                'last15' => _('N/A'),
                'tasks' => _('N/A'),
                'processes' => _('N/A'),
                'lastpid' => _('N/A'),
            ];
        }

        $line = fgets($handle, 64);
        fclose($handle);
        if ($line === false) {
            return [
                'last1' => _('N/A'),
                'last5' => _('N/A'),
                'last15' => _('N/A'),
                'tasks' => _('N/A'),
                'processes' => _('N/A'),
                'lastpid' => _('N/A'),
            ];
        }

        $parts = explode(' ', trim($line));
        $active = explode('/', $parts[3] ?? '0/0');

        return [
            'last1' => $parts[0] ?? '0',
            'last5' => $parts[1] ?? '0',
            'last15' => $parts[2] ?? '0',
            'tasks' => $active[0] ?? '0',
            'processes' => $active[1] ?? '0',
            'lastpid' => $parts[4] ?? '0',
        ];
    }

    private function pluralSuffix(int $value): string
    {
        return $value === 1 ? '' : _('s');
    }
}
