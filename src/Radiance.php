<?php
declare(strict_types=1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use PU239\Config\ConfigRepository;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

/**
 * Class Radiance
 *
 * @package Pu239
 */
class Radiance
{
    protected ConfigRepository $config;

    /**
     * Radiance constructor.
     *
     * @param ConfigRepository $config
     *
     * @throws Exception
     */
    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function check_status()
    {
        exec("ps --no-headers -C radiance -o args,state", $result);

        return $result;
    }

    /**
     * @return mixed
     */
    public function start_radiance()
    {
        $configPath = $this->config->get('tracker.config_path');
        if (!is_string($configPath) || $configPath === '') {
            // TODO(2025): map legacy key "tracker.config_path" to appropriate config path
            $configPath = '';
        }

        exec("radiance -d -c {$configPath}", $result);

        return $result;
    }

    /**
     * @param string $signal
     *
     * @return mixed
     */
    public function reload_radiance(string $signal)
    {
        exec("killall -s {$signal} radiance", $result);

        return $result;
    }
}
