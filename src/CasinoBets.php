<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;

/**
 * Class CasinoBets.
 */
class CasinoBets
{
    protected $fluent;

    /**
     * Casino constructor.
     *
     * @param Database $fluent
     */
    public function __construct(Database $fluent)
    {
        $this->fluent = $fluent;
    }

    /**
     *
     * @param string $username
     *
     * @throws Exception
     *
     * @return int|mixed
     */
    public function get_open_bets(string $username)
    {
        $bets = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $bets;
    }

    /**
     * @param array $set
     * @param int   $id
     *
     * @throws Exception
     */
    public function update(array $set, int $id)
    {
        // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
    }

    /**
     * @param int $id
     *
     * @throws Exception
     */
    public function delete_bet(int $id)
    {
        // TODO: review delete
$sql = "DELETE FROM table WHERE ...";
$this->db->perform($sql, [/* params */]);;
    }

    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_empty_bets()
    {
        $bets = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $bets;
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
        $id = // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;

        return $id;
    }
}
