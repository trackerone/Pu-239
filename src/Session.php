<?php
declare(strict_types=1);

namespace Pu239;

use PU239\Config\ConfigRepository;

require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/bootstrap_pdo.php';

/**
 * Class Session.
 */
class Session
{
    private ConfigRepository $config;
    private $cache;
    private $fluent;

    public function __construct(ConfigRepository $config, Cache $cache, Database $fluent)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->fluent = $fluent;
    }

    /**
     * @param        $value
     * @param string $key
     * @param bool   $use_prefix
     */
    public function set(string $key, $value, bool $use_prefix = true)
    {
        $prefix = '';
        if ($use_prefix) {
            $prefix = (string) $this->config->get('session.prefix', '');
            if ($prefix === '') {
                // TODO(2025): map legacy key "session.prefix" to appropriate config path
            }
        }
        $notifications = $this->config->get('site.notifications', []);
        if (!is_array($notifications)) {
            // TODO(2025): map legacy key "site.notifications" to appropriate config path
            $notifications = [];
        }
        if (in_array($key, $notifications, true)) {
            $current = $this->get($key);
            if ($current) {
                if (!in_array($value, $current, true)) {
                    $_SESSION[$prefix . $key] = array_merge($current, [$value]);
                }
            } else {
                $_SESSION[$prefix . $key] = [$value];
            }
        } else {
            $this->unset($key);
            $_SESSION[$prefix . $key] = $value;
        }
    }

    /**
     * @param string $key
     *
     * @return mixed|null |null
     */
    public function get(string $key)
    {
        if (empty($key)) {
            return null;
        }

        $prefix = (string) $this->config->get('session.prefix', '');
        if ($prefix === '') {
            // TODO(2025): map legacy key "session.prefix" to appropriate config path
        }

        if (isset($_SESSION[$prefix . $key])) {
            return $_SESSION[$prefix . $key];
        } else {
            return null;
        }
    }

    /**
     * @param string      $key
     * @param string|null $prefix
     */
    public function unset(string $key, string $prefix = null)
    {
        if ($prefix === null) {
            $prefix = (string) $this->config->get('session.prefix', '');
            if ($prefix === '') {
                // TODO(2025): map legacy key "session.prefix" to appropriate config path
            }
        }

        unset($_SESSION[$prefix . $key]);
    }
}
