<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Psr\Container\ContainerInterface;

/**
 * Class Files.
 */
class Files
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
     * @param int $id
     *
     * @throws Exception
     */
    public function delete(int $id)
    {
        $sql = "DELETE FROM files WHERE torrent = :torrent";
$this->db->perform($sql, ['torrent' => $id]);;
    }

    /**
     *
     * @param array $values
     *
     * @throws Exception
     *
     * @return bool|int
     */
    public function insert(array $values)
    {
        $sql = "INSERT INTO files (/* columns */) VALUES (/* values */)";
$id = $this->db->perform($sql, $values);;

        return $id;
    }
}
