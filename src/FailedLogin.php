<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Psr\Container\ContainerInterface;

/**
 * Class FailedLogin.
 */
class FailedLogin
{
    protected $cache;
    protected $fluent;
    protected $container;

    /**
     * FailedLogin constructor.
     *
     * @param Cache              $cache
     * @param Database           $fluent
     * @param ContainerInterface $c
     */
    public function __construct(Cache $cache, Database $fluent, ContainerInterface $c)
    {
        $this->container = $c;
        $this->fluent = $fluent;
        $this->cache = $cache;
    }

    /**
     *
     * @param string $ip
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get(string $ip)
    {
        $fails = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    }

    /**
     * @param array $values
     * @param array $update
     *
     * @throws Exception
     */
    public function insert(array $values, array $update)
    {
        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    }

    /**
     * @param string $ip
     *
     * @throws Exception
     */
    public function delete(string $ip)
    {
        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    }
}
