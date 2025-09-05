<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Envms\FluentPDO\Queries\Delete;
use Envms\FluentPDO\Queries\Select;
use PDOStatement;

/**
 * Class Offer.
 */
class Offer
{
    protected $fluent;
    protected $cache;
    protected $site_config;
    protected $settings;

    /**
     * Upcoming constructor.
     *
     * @param Cache    $cache
     * @param Database $fluent
     * @param Settings $settings
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, Settings $settings)
    {
        $this->settings = $settings;
        $this->site_config = $this->settings->get_settings();
        $this->fluent = $fluent;
        $this->cache = $cache;
    }

    /**
     *
     * @param bool $all
     * @param bool $show_hidden
     *
     * @throws Exception
     *
     * @return Select|mixed
     */
    public function get_count(bool $all, bool $show_hidden)
    {
        $count = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchOne($sql, [/* params */]);;
        if (!empty($result['parent_name'])) {
            $result['fullcat'] = $result['parent_name'] . '::' . $result['cat'];
        }
        if ($is_staff) {
            $vote_yes = $this->fluent->from('offer_votes')
                                     ->select(null)
                                     ->select('COUNT(id) AS count')
                                     ->where('vote = "yes"')
                                     ->where('offer_id = ?', $result['id'])
                                     ->fetch('count');
            $vote_no = $this->fluent->from('offer_votes')
                                    ->select(null)
                                    ->select('COUNT(id) AS count')
                                    ->where('vote = "no"')
                                    ->where('offer_id = ?', $result['id'])
                                    ->fetch('count');
            $result['vote_yes'] = (int) $vote_yes;
            $result['vote_no'] = (int) $vote_no;
        }

        return $result;
    }

    /**
     *
     * @param array $set
     * @param int   $offerid
     *
     * @throws Exception
     *
     * @return bool|int|PDOStatement
     */
    public function update(array $set, int $offerid)
    {
        $result = // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;

        return $result;
    }

    /**
     *
     * @param int  $id
     * @param bool $staff
     * @param int  $userid
     *
     * @throws Exception
     *
     * @return bool|Delete
     */
    public function delete(int $id, bool $staff, int $userid)
    {
        $result = // TODO: review delete
$sql = "DELETE FROM table WHERE ...";
$this->db->perform($sql, [/* params */]);;

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
    public function insert(array $values)
    {
        $id = // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;

        return $id;
    }
}
