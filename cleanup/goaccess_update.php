<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;

/**
 * @param $data
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 */
function goaccess_cleanup($data)
{
    $time_start = microtime(true);
    if (file_exists('/usr/bin/goaccess') && file_exists('/var/log/nginx/access.log.gz')) {
        $path = '/dev/shm/goaccess/';
        if (!make_dir($path, 0775) || !is_writable($path)) {
            $path = CACHE_DIR . 'goaccess';
            if (!make_dir($path, 0775) || !is_writable($path)) {
                return;
            }
        }
        passthru("zcat /var/log/nginx/access.log.gz* > {$path}access.log");
        passthru("/usr/bin/goaccess '{$path}access.log' -p '" . CONFIG_DIR . "goaccess.conf' --real-os --geoip-database='" . ROOT_DIR . "GeoIP/GeoLiteCity.dat' -o '" . CACHE_DIR . "goaccess.html' \n");
    }

    $time_end = microtime(true);
    $run_time = $time_end - $time_start;
    $text = " Run time: $run_time seconds";
    echo $text . "\n";
    if ($data['clean_log']) {
        write_log('GO Access Cleanup: Completed' . $text);
    }
}
