<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Psr\Container\ContainerInterface;

/**
 * Class HappyLog.
 */
class HappyLog
{
    protected $cache;
    protected $fluent;
    protected $container;

    /**
     * HappyLog constructor.
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
     * @param int $userid
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_count(int $userid)
    {
        $count = $this->fluent$sql = "SELECT * FROM 'happylog'"; $this->db->fetchAll($sql);;

        return $happy;
    }
}
