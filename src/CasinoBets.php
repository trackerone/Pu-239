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
        $bets = $this->fluent$sql = "SELECT * FROM 'casino_bets'"; $this->db->fetchAll($sql);;

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
        $this->fluent->update('casino_bets')
                     ->set($set)
                     ->where('id = ?', $id)
                     ->execute();
    }

    /**
     * @param int $id
     *
     * @throws Exception
     */
    public function delete_bet(int $id)
    {
        $this->fluent$sql = "DELETE FROM 'casino_bets' WHERE ..."; $this->db->perform($sql);;
    }

    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_empty_bets()
    {
        $bets = $this->fluent$sql = "SELECT * FROM 'casino_bets'"; $this->db->fetchAll($sql);;

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
        $id = $this->fluent->insertInto('casino_bets')
                           ->values($values)
                           ->execute();

        return $id;
    }
}
