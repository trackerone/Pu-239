<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;

/**
 * Class BotTriggers.
 */
class BotTriggers
{
    protected $fluent;
    protected $cache;

    /**
     * BotTriggers constructor.
     *
     * @param Database $fluent
     * @param Cache    $cache
     */
    public function __construct(Database $fluent, Cache $cache)
    {
        $this->fluent = $fluent;
        $this->cache = $cache;
    }

    /**
     *
     * @param array $values
     *
     * @throws Exception
     *
     * @return bool
     */
    public function insert(array $values)
    {
        $result = // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;

        if (!$result) {
            return false;
        }
        $this->cache->delete('bot_replies_');

        return true;
    }

    /**
     *
     * @param array $set
     * @param int   $id
     *
     * @throws Exception
     *
     * @return bool
     */
    public function update(array $set, int $id)
    {
        $result = // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;

        if (!$result) {
            return false;
        }
        $this->cache->delete('bot_replies_');

        return true;
    }

    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_unapproved()
    {
        $result = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $result;
    }

    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function getall()
    {
        $result = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $result;
    }

    /**
     *
     * @param int $id
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete(int $id)
    {
        $results = // TODO: review delete
$sql = "DELETE FROM table WHERE ...";
$this->db->perform($sql, [/* params */]);;
        $this->cache->delete('bot_replies_');

        return $results;
    }

    /**
     *
     * @param int $id
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_by_id(int $id)
    {
        $trigger = $this->fluent->from('bot_triggers')
                                ->select(null)
                                ->select('phrase')
                                ->where('id = ?', $id)
                                ->fetch('phrase');

        return $trigger;
    }
}
