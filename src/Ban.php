<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use PDOStatement;
use Psr\Container\ContainerInterface;

/**
 * Class Ban.
 */
class Ban
{
    protected $cache;
    protected $fluent;
    protected $container;

    /**
     * Ban constructor.
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
     * @return array|PDOStatement
     *
     */
    public function get_range(string $ip)
    {
        $bans = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        return $bans;
    }

    /**
     *
     * @param string $ip
     *
     * @throws Exception
     *
     * @return mixed
     *
     */
    public function get_count(string $ip)
    {
        $count = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
    }
}
