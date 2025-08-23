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
        $count = $this->fluent->from('requests AS r')
                              ->select(null)
                              ->select('COUNT(r.id) AS count');
        if (!$show_hidden) {
            $count->leftJoin('categories AS c ON r.category = c.id')
                  ->where('c.hidden = 0');
        }
        if (!$all) {
            $count->where('r.torrentid = 0');
        }
        $count = $count->fetch('count');

        return $count;
    }

    /**
     *
     * @param int    $limit
     * @param int    $offset
     * @param string $orderby
     * @param bool   $desc
     * @param bool   $all
     * @param bool   $show_hidden
     * @param int    $userid
     *
     * @throws Exception
     *
     * @return array
     */
    public function get_all(int $limit, int $offset, string $orderby, bool $desc, bool $all, bool $show_hidden, int $userid)
    {
        $results = $this->fluent->from('requests AS r')
                                ->select('u.username')
                                ->select('u.class')
                                ->select('c.name as cat')
                                ->select('c.image')
                                ->select('p.name AS parent_name')
                                ->select('(n.id IS NOT NULL) AS notify')
                                ->select('COALESCE(v.vote, false) AS voted')
                                ->select('b.amount AS bounty')
                                ->select('SUM(a.amount) AS bounties')
                                ->leftJoin('users AS u ON r.userid = u.id')
                                ->leftJoin('categories AS c ON r.category = c.id')
                                ->leftJoin('categories AS p ON c.parent_id = p.id')
                                ->leftJoin('request_notify AS n ON r.userid = n.userid AND r.id = n.requestid')
                                ->leftJoin('request_votes AS v ON r.userid = v.user_id AND r.id = v.request_id')
                                ->leftJoin('bounties AS b ON b.userid = ? AND r.id = b.requestid', $userid)
                                ->leftJoin('bounties AS a ON r.id = a.requestid')
                                ->groupBy('r.id')
                                ->groupBy('v.vote')
                                ->groupBy('b.amount')
                                ->limit($limit)
                                ->offset($offset);
        if (!$show_hidden) {
            $results = $results->where('c.hidden = 0');
        }
        if (!$all) {
            $results = $results->where('r.torrentid = 0');
        }
        if (!empty($orderby)) {
            $order = $orderby . ($desc ? ' DESC' : '');
            $results = $results->orderBy($order);
        }
        $results = $results->orderBy('r.userid');
        $request = [];
        foreach ($results as $result) {
            $result['bounties'] = !empty($result['bounties']) ? (int) $result['bounties'] : 0;
            $result['bounty'] = !empty($result['bounty']) ? (int) $result['bounty'] : 0;
            if (!empty($result['parent_name'])) {
                $result['cat'] = $result['parent_name'] . '::' . $result['cat'];
            }
            $request[] = $result;
        }

        return $request;
    }

    /**
     *
     * @param int  $requestid
     * @param bool $is_staff
     * @param int  $userid
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get(int $requestid, bool $is_staff, int $userid)
    {
        $result = $this->fluent->from('requests AS r')
                               ->select('u.username')
                               ->select('c.name as cat')
                               ->select('c.image')
                               ->select('p.name AS parent_name')
                               ->select('b.amount AS bounty')
                               ->select('b.paid AS paid')
                               ->select('SUM(a.amount) AS bounties')
                               ->select('t.owner')
                               ->leftJoin('users AS u ON r.userid = u.id')
                               ->leftJoin('categories AS c ON r.category = c.id')
                               ->leftJoin('categories AS p ON c.parent_id = p.id')
                               ->leftJoin('bounties AS b ON b.userid = ? AND r.id = b.requestid', $userid)
                               ->leftJoin('bounties AS a ON r.id = a.requestid')
                               ->leftJoin('torrents AS t ON r.torrentid = t.id')
                               ->where('r.id = ?', $requestid)
                               ->groupBy('r.id')
                               ->groupBy('b.amount')
                               ->groupBy('b.paid')
                               ->fetch();
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
        $result = $this->fluent->deleteFrom('requests')
                               ->where('id = ?', $id);
        if (!$staff) {
            $result = $result->where('userid = ?', $userid);
        }
        $result = $result->execute();

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
