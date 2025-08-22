<?php
require_once __DIR__ . '/../include/runtime_safe.php';
require_once __DIR__ . '/../include/mysql_compat.php';


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
        $count = $this->fluent->from('offers AS o')
                              ->select(null)
                              ->select('COUNT(o.id) AS count');
        if (!$show_hidden) {
            $count->leftJoin('categories AS c ON o.category = c.id')
                  ->where('c.hidden = 0');
        }
        if (!$all) {
            $count->where('o.torrentid = 0');
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
     *
     * @throws Exception
     *
     * @return array
     */
    public function get_all(int $limit, int $offset, string $orderby, bool $desc, bool $all, bool $show_hidden)
    {
        $results = $this->fluent->from('offers AS r')
                                ->select('u.username')
                                ->select('u.class')
                                ->select('c.name as cat')
                                ->select('c.image')
                                ->select('p.name AS parent_name')
                                ->select('(n.id IS NOT NULL) AS notify')
                                ->select('COALESCE(v.vote, false) AS voted')
                                ->leftJoin('users AS u ON r.userid = u.id')
                                ->leftJoin('categories AS c ON r.category = c.id')
                                ->leftJoin('categories AS p ON c.parent_id = p.id')
                                ->leftJoin('offer_notify AS n ON r.userid = n.userid AND r.id = n.offerid')
                                ->leftJoin('offer_votes AS v ON r.userid = v.user_id AND r.id = v.offer_id')
                                ->limit($limit)
                                ->offset($offset);
        if (!$show_hidden) {
            $results = $results->where('c.hidden = 0');
        }
        if (!$all) {
            $results = $results->where('r.torrentid = 0')
                               ->where("r.status != 'denied'");
        }
        if (!empty($orderby)) {
            $order = $orderby . ($desc ? ' DESC' : '');
            $results = $results->orderBy($order);
        }
        $results = $results->orderBy('r.userid');
        $offer = [];
        foreach ($results as $result) {
            if (!empty($result['parent_name'])) {
                $result['cat'] = $result['parent_name'] . '::' . $result['cat'];
            }
            $offer[] = $result;
        }

        return $offer;
    }

    /**
     *
     * @param int  $offerid
     * @param bool $is_staff
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get(int $offerid, bool $is_staff)
    {
        $result = $this->fluent->from('offers AS r')
                               ->select('u.username')
                               ->select('c.name as cat')
                               ->select('c.image')
                               ->select('p.name AS parent_name')
                               ->leftJoin('users AS u ON r.userid = u.id')
                               ->leftJoin('categories AS c ON r.category = c.id')
                               ->leftJoin('categories AS p ON c.parent_id = p.id')
                               ->where('r.id = ?', $offerid)
                               ->fetch();
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
        $result = $this->fluent->update('offers')
                               ->set($set)
                               ->where('id = ?', $offerid)
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
        $result = $this->fluent->deleteFrom('offers')
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
        $id = $this->fluent->insertInto('offers')
                           ->values($values)
                           ->execute();

        return $id;
    }
}
