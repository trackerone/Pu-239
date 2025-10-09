<?php
declare(strict_types=1);

// AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5

namespace PU239\Http\Handlers\Public\Ajax;

use PU239\Config\ConfigRepository;

final class ViewSqlHandler
{
    /** @param array<string,mixed> $meta */
    public function handle(array $meta = []): void
    {
        // AUTO_CONVERT_ATTEMPTED: 2025-10-09 via handler-convert batch=110-5
        try {
            require_once \dirname(__DIR__, 5) . '/bootstrap_web.php';
            require_once \dirname(__DIR__, 5) . '/include/bittorrent.php';

            global $container;
            /** @var ConfigRepository $config */
            $config = $container->get(ConfigRepository::class);

            $user = check_user_status();
            class_check(UC_MAX);

            if (!function_exists('adminer_object')) {
                /**
                 * @return AdminerCustomization
                 */
                function adminer_object()
                {
                    include_once PLUGINS_DIR . 'plugin.php';

                    foreach (glob(PLUGINS_DIR . '*.php') as $filename) {
                        include_once $filename;
                    }

                    $plugins = [
                        new \AdminerDatabaseHide([
                            'mysql',
                            'sys',
                            'performance_schema',
                            'information_schema',
                        ]),
                        new \AdminerFrames(),
                        new \AdminerDumpBz2(),
                        new \AdminerDumpZip(),
                        new \AdminerEnumTypes(),
                        new \AdminerVersionNoverify(),
                        new \AdminerTablesFilter(),
                        new \AdminerReadableDates(),
                        new \AdminerDumpDate(),
                    ];

                    /**
                     * Class AdminerCustomization.
                     */
                    class view_sql extends \AdminerPlugin
                    {
                        public $plugins;

                        /**
                         * AdminerCustomization constructor.
                         *
                         * @param mixed $plugins
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
                         * @return array<mixed>|mixed|null
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

                            return null;
                        }
                    }

                    return new \AdminerCustomization($plugins);
                }
            }

            include ADMIN_DIR . 'adminer.php';
        } catch (\Throwable $e) {
            error_log('Converted handler error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Internal error';
        }
    }
}
