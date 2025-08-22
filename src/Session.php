<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


declare(strict_types = 1);

namespace Pu239;

/**
 * Class Session.
 */
class Session
{
    private $site_config;
    private $cache;
    private $fluent;

    public function __construct()
    {
        global $site_config, $cache, $fluent;

        $this->site_config = $site_config;
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
            $prefix = $this->site_config['session']['prefix'];
        }
        if (in_array($key, $this->site_config['site']['notifications'])) {
            $current = $this->get($key);
            if ($current) {
                if (!in_array($value, $current)) {
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

        $prefix = $this->site_config['session']['prefix'];

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
            $prefix = $this->site_config['session']['prefix'];
        }

        unset($_SESSION[$prefix . $key]);
    }
}
