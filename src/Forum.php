<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use PDOStatement;
use Psr\Container\ContainerInterface;

/**
 * Class Forum.
 */
class Forum
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
     * @param int $forum_id
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete(int $forum_id)
    {
        $sql = "DELETE FROM forums WHERE id = :id";
$result = $this->db->perform($sql, ['id' => $forum_id]);

        return $result;
    }

    /**
     *
     * @param array $set
     * @param int   $forum_id
     *
     * @throws Exception
     *
     * @return bool|int|PDOStatement
     */
    public function update(array $set, int $forum_id)
    {
        $sql = "UPDATE forums SET /* columns */ WHERE id = :id";
$result = $this->db->perform($sql, array_merge($set, ['id' => $forum_id]));

        return $result;
    }

    /**
     *
     * @param array $values
     *
     * @throws Exception
     *
     * @return bool|int
     */
    public function add(array $values)
    {
        $sql = "INSERT INTO forums (/* columns */) VALUES (/* values */)";
$id = $this->db->perform($sql, $values);

        return $id;
    }

    /**
     *
     * @param int $forum_id
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_forum(int $forum_id)
    {
        $forum = $this->fluent->from('forums')
                              ->where('id = ?', $forum_id)
                              ->fetch(); // TODO(batch41): replace with $this->db->fetchRow("SELECT ...", [...])

        return $forum;
    }

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function get_count()
    {
        $count = $this->fluent->from('forums')
                              ->select(null)
                              ->select('COUNT(id) AS count')
                              ->fetch("count"); // TODO(batch41): use $this->db->fetchValue("SELECT COUNT(...) ...", [...])

        return $count;
    }
}
