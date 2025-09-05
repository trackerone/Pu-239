<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Psr\Container\ContainerInterface;

/**
 * Class Searchcloud.
 */
class Searchcloud
{
    protected $cache;
    protected $fluent;
    protected $container;

    /**
     * Searchcloud constructor.
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
     * @param array $limit
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get(array $limit)
    {
        $search = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $search;
    }

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function get_count()
    {
        $search = $this->fluent->from('searchcloud')
                               ->select(null)
                               ->select('COUNT(id) AS count')
                               ->fetch('count');

        return $search;
    }

    /**
     * @param array $terms
     *
     * @throws Exception
     */
    public function delete(array $terms)
    {
        foreach ($terms as $term) {
            // TODO: review delete
$sql = "DELETE FROM table WHERE ...";
$this->db->perform($sql, [/* params */]);;
        }
        $this->cache->delete('searchcloud_');
    }

    /**
     * @param array $values
     * @param array $update
     *
     * @throws Exception
     */
    public function insert(array $values, array $update)
    {
        // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
    }
}
