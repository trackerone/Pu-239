<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use PDOStatement;

/**
 * Class Snatched.
 */
class Snatched
{
    protected $cache;
    protected $fluent;
    protected $user;
    protected $site_config;
    protected $settings;

    /**
     * Snatched constructor.
     *
     * @param Cache    $cache
     * @param Database $fluent
     * @param User     $user
     * @param Settings $settings
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, User $user, Settings $settings)
    {
        $this->settings = $settings;
        $this->site_config = $this->settings->get_settings();
        $this->cache = $cache;
        $this->fluent = $fluent;
        $this->user = $user;
    }

    /**
     *
     * @param int $userid
     * @param int $tid
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function get_snatched(int $userid, int $tid)
    {
        $snatches = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

            $snatches = array_merge($snatches, $snatched);
            $this->remove_cain($hnr[$type]);
        }
        foreach ($snatches as $snatch) {
            $users[$snatch['userid']][] = $snatch;
            $cains[] = $snatch['id'];
        }
        if (!empty($cains)) {
            $this->set_cain($cains);
        }

        return $users;
    }

    /**
     * @param int $time
     *
     * @throws Exception
     */
    public function remove_cain(int $time)
    {
        $set = ['mark_of_cain' => 'no'];

        // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
    }

    /**
     * @param array $cains
     *
     * @throws Exception
     */
    public function set_cain(array $cains)
    {
        $set = ['mark_of_cain' => 'yes'];
        // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
    }

    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_user_to_remove_hnr()
    {
        $users = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $users;
    }

    /**
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_user_to_add_hnr()
    {
        $users = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $users;
    }

    /**
     * @param array $hnr
     *
     * @throws Exception
     */
    public function set_hnr(array $hnr)
    {
        $set = [
            's.hit_and_run' => TIME_NOW,
        ];
        $types = [
            'days_3',
            'days_14',
            'days_over_14',
        ];
        foreach ($types as $type) {
            // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
        }
    }

    /**
     * @param array $hnr
     *
     * @throws Exception
     */
    public function remove_hnr(array $hnr)
    {
        $set = [
            's.hit_and_run' => 0,
        ];
        $types = [
            'days_3',
            'days_14',
            'days_over_14',
        ];
        foreach ($types as $type) {
            // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
        }
    }

    /**
     * @throws Exception
     */
    public function update_seeder()
    {
        $deadtime = TIME_NOW - floor($this->site_config['tracker']['announce_interval'] * 1.3);
        $update = [
            'seeder' => 'no',
        ];
        // TODO: review update
$sql = "UPDATE table SET ... WHERE ...";
$this->db->perform($sql, [/* params */]);;
    }
}
