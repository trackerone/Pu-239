<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Psr\Container\ContainerInterface;

/**
 * Class Achievementlist.
 */
class Achievementlist
{
    protected $cache;
    protected $fluent;
    protected $env;
    protected $limit;
    protected $container;

    /**
     * Achievement constructor.
     *
     * @param Cache              $cache
     * @param Database           $fluent
     * @param ContainerInterface $c
     */
    public function __construct(Cache $cache, Database $fluent, ContainerInterface $c)
    {
        $this->container = $c;
        $this->env = $this->container->get('env');
        $this->fluent = $fluent;
        $this->cache = $cache;
        $this->limit = $this->env['db']['query_limit'];
    }

    /**
     * @param array $values
     *
     * @return bool|int|string
     */
    public function add(array $values)
    {
        try {
            return $sql = "INSERT INTO achievementlist (/* columns */) VALUES (/* values */)";
$this->db->perform($sql, $values);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
