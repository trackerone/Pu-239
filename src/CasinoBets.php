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
        $bets = $this->fluent->from('casino_bets')
                             ->select(null)
                             ->select('COUNT(challenged) AS count')
                             ->where('proposed = ?', $username)
                             ->fetch('count');

        $bets = empty($bets) ? 1 : $bets;

        return $bets;
    }

    /**
     *
     * @param int $id
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_bet(int $id)
    {
        $bet = $this->fluent->from('casino_bets')
                            ->where('id = ?', $id)
                            ->fetch();

        return $bet;
    }

    /**
     *
     * @param int $userid
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_bets(int $userid)
    {
        $bets = $this->fluent->from('casino_bets')
                             ->where('userid = ?', $userid)
                             ->orderBy('time')
                             ->fetchAll();

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
        $sql = "UPDATE casino_bets SET /* columns */ WHERE id = :id";
$this->db->perform($sql, array_merge($set, ['id' => $id]));;
    }

    /**
     * @param int $id
     *
     * @throws Exception
     */
    public function delete_bet(int $id)
    {
        $sql = "DELETE FROM casino_bets WHERE id = :id";
$this->db->perform($sql, ['id' => $id]);;
    }

    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_empty_bets()
    {
        $bets = $this->fluent->from('casino_bets')
                             ->where('challenged = "empty"')
                             ->fetchAll();

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
        $sql = "INSERT INTO casino_bets (/* columns */) VALUES (/* values */)";
$id = $this->db->perform($sql, $values);;

        return $id;
    }
}
