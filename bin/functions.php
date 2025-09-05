<?php
require_once __DIR__ . '/../include/runtime_safe.php';


declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Cache;
use Pu239\Database;
use Pu239\Session;

/**
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 *
 * @return array
 */
function get_styles()
{
    global $container;

    $fluent = $container->get(Database::class);
    $query = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        if (empty($classes)) {
            if (!$create) {
                app_halt("You do have not classes for template {$style}\n\nto create them rerun this script\nphp bin/uglify.php classes\n");
            } else {
                foreach ($all_classes[0] as $values) {
                    $values['template'] = $style;
                    // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
                }
                app_halt("Classes added for template {$style}\n");
            }
        }
        $all_classes[] = $classes;
    }

    return $all_classes;
}

/**
 * @return string
 */
function get_webserver_user()
{
    global $site_config;

    if (php_sapi_name() === 'cli') {
        $group = shell_exec("ps -ef | egrep '(httpd|apache2|apache|nginx)' | grep -v \`whoami\` | grep -v root | head -n1 | awk '{print $1}'");
    } else {
        $group = posix_getpwuid(posix_geteuid());
        $group = $group['name'];
    }
    if (empty($group)) {
        return $site_config['webserver']['username'];
    } else {
        return trim($group);
    }
}

/**
 * @return mixed|string|null
 */
function get_username()
{
    if (php_sapi_name() === 'cli') {
        $user = null;
        $commands = [
            `logname`,
            `who | awk '{print $1}'`,
            exec('echo $SUDO_USER'),
        ];
        $i = 0;
        while (empty($user)) {
            $user = $commands[$i];
            if (!empty($user)) {
                $user = trim($user);
            }
            ++$i;
        }
        if (!empty($user)) {
            return $user;
        }
    }

    return get_webserver_user();
}

/**
 * @param string $group
 */
function cleanup(string $group)
{
    global $site_config;

    if (file_exists($site_config['files']['path'])) {
        if (php_sapi_name() === 'cli') {
            passthru("sudo chown -R $group:$group " . $site_config['files']['path']);
        } else {
            try {
                chown($site_config['files']['path'], $group);
                chgrp($site_config['files']['path'], $group);
            } catch (Exception $exception) {
                // TODO logger
            }
        }
    }
    if (php_sapi_name() === 'cli') {
        if (file_exists(DI_CACHE_DIR)) {
            passthru("sudo chown -R $group:$group " . DI_CACHE_DIR);
        }
    }
}

/**
 *
 * @param bool $before
 *
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 *
 * @return int
 */
function toggle_site_status(bool $before)
{
    global $container;

    $fluent = $container->get(Database::class);
    $cache = $container->get(Cache::class);
    $online = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    if (!$before) {
        clear_di_cache();
    }
    if (!$online) {
        $session = $container->get(Session::class);
        $session->unset('is-danger');
    }
    $cache->set('site_settings_', false);

    return $disabled;
}
