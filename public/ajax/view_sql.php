<?php
declare(strict_types=1);

use PU239\Config\ConfigRepository;

require_once dirname(__DIR__) . '/bootstrap_web.php';

global $container;
/** @var ConfigRepository $config */
$config = $container->get(ConfigRepository::class);

require_once __DIR__ . '/../../include/bittorrent.php';
$user = check_user_status();
class_check(UC_MAX);

/**
 * @return AdminerCustomization
 */
function adminer_object()
{
    include_once PLUGINS_DIR . 'plugin.php';

    foreach (glob(PLUGINS_DIR . '*.php') as $filename) {
        include_once "$filename";
    }

    $plugins = [
        new AdminerDatabaseHide([
            'mysql',
            'sys',
            'performance_schema',
            'information_schema',
        ]),
        new AdminerFrames(),
        new AdminerDumpBz2(),
        new AdminerDumpZip(),
        new AdminerEnumTypes(),
        new AdminerVersionNoverify(),
        new AdminerTablesFilter(),
        new AdminerReadableDates(),
        new AdminerDumpDate(),
    ];

    /**
     * Class AdminerCustomization.
     */
    class view_sql extends AdminerPlugin
    {
        public $plugins;

        /**
         * AdminerCustomization constructor.
         *
         * @param $plugins
         */
        public function __construct($plugins)
        {
            $this->plugins = $plugins;
            parent::__construct($this->plugins);
        }

        /**
         * @return mixed
         */
        public function name()
        {
            global $config;

            return (string) $config->get('site.name');
        }

        /**
         * @return mixed|string
         */
        public function database()
        {
            global $config;

            return (string) $config->get('db.database');
        }

        /**
         * @return array|mixed
         */
        public function credentials()
        {
            global $config, $user;

            if (in_array($user['id'], (array) $config->get('adminer.allowed_ids'), true)) {
                return [
                    'localhost',
                    (string) $config->get('db.username'),
                    (string) $config->get('db.password'),
                ];
            }
        }
    }

    return new AdminerCustomization($plugins);
}

include ADMIN_DIR . 'adminer.php';
