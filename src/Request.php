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
 * Class Request.
 */
class Request
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
        $count = $this->fluent$sql = "SELECT * FROM 'requests AS r'"; $this->db->fetchOne($sql);;
        if (!empty($result['parent_name'])) {
            $result['fullcat'] = $result['parent_name'] . '::' . $result['cat'];
        }
        $result['bounties'] = !empty($result['bounties']) ? (int) $result['bounties'] : 0;
        $result['bounty'] = !empty($result['bounty']) ? (int) $result['bounty'] : 0;
        $result['owner'] = !empty($result['owner']) ? (int) $result['owner'] : 0;
        if ($is_staff) {
            $vote_yes = $this->fluent->from('request_votes')
                                     ->select(null)
                                     ->select('COUNT(id) AS count')
                                     ->where('vote = "yes"')
                                     ->where('request_id = ?', $result['id'])
                                     ->fetch('count');
            $vote_no = $this->fluent->from('request_votes')
                                    ->select(null)
                                    ->select('COUNT(id) AS count')
                                    ->where('vote = "no"')
                                    ->where('request_id = ?', $result['id'])
                                    ->fetch('count');
            $result['vote_yes'] = (int) $vote_yes;
            $result['vote_no'] = (int) $vote_no;
        }

        return $result;
    }

    /**
     *
     * @param array $set
     * @param int   $requestid
     *
     * @throws Exception
     *
     * @return bool|int|PDOStatement
     */
    public function update(array $set, int $requestid)
    {
        $result = $this->fluent->update('requests')
                               ->set($set)
                               ->where('id = ?', $requestid)
                               ->execute();

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
        $result = $this->fluent$sql = "DELETE FROM 'requests' WHERE ..."; $this->db->perform($sql);;

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
        $id = $this->fluent->insertInto('requests')
                           ->values($values)
                           ->execute();

        return $id;
    }
}
