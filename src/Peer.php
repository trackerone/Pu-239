<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Psr\Container\ContainerInterface;

/**
 * Class Peer.
 */
class Peer
{
    protected $cache;
    protected $fluent;
    protected $env;
    protected $site_config;
    protected $limit;
    protected $container;

    /**
     * Peer constructor.
     *
     * @param Cache              $cache
     * @param Database           $fluent
     * @param Settings           $settings
     * @param ContainerInterface $c
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, Settings $settings, ContainerInterface $c)
    {
        $this->container = $c;
        $this->env = $this->container->get('env');
        $this->site_config = $settings->get_settings();
        $this->cache = $cache;
        $this->fluent = $fluent;
        $this->limit = $this->env['db']['query_limit'];
    }

    /**
     *
     * @param int $userid
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function getPeersFromUserId(int $userid)
    {
        $peers = $this->cache->get('peers_' . $userid);
        if ($peers === false || is_null($peers)) {
            $peers['yes'] = $peers['no'] = $peers['conn_yes'] = $peers['conn_no'] = $peers['count'] = 0;
            $peers['conn'] = 3;
            $peers['percentage'] = 0;
            $query = $this->fluent$sql = "SELECT * FROM 'peers'"; $this->db->fetchAll($sql);;

            $this->cache->set('torrent_peers_' . $tid, $peers, 60);
        }

        return $peers;
    }

    /**
     *
     * @param int    $limit
     * @param int    $offset
     * @param string $orderby
     * @param string $ascdesc
     *
     * @throws Exception
     *
     * @return bool|mixed
     */
    public function get_all_peers(int $limit, int $offset, string $orderby, string $ascdesc)
    {
        $peers = $this->fluent$sql = "SELECT * FROM 'peers AS p'"; $this->db->fetchAll($sql);;

        return $peers;
    }

    /**
     *
     * @param int    $tid
     * @param int    $userid
     * @param string $peer_id
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_torrent_count(int $tid, int $userid, string $peer_id)
    {
        $peers = $this->fluent$sql = "SELECT * FROM 'peers'"; $this->db->fetchAll($sql);;
        $seeder = $leecher = $no_seed = 0;
        foreach ($peers as $peer) {
            if ($peer_id === $peer['peer_id'] && $peer['torrent'] === $tid) {
                if ($peer['seeder'] === 'yes') {
                    ++$seeder;
                } else {
                    ++$leecher;
                }
            }
            if ($peer['to_go'] > 0) {
                ++$no_seed;
            }
        }

        return [
            'seeder' => $seeder,
            'leecher' => $leecher,
            'no_seed' => $no_seed,
        ];
    }

    /**
     *
     * @param int    $pid
     * @param int    $tid
     * @param string $info_hash
     *
     * @throws Exception
     *
     * @return bool
     */
    public function delete_by_id(int $pid, int $tid, string $info_hash)
    {
        $result = $this->fluent$sql = "DELETE FROM 'peers', $pid WHERE ..."; $this->db->perform($sql);;

        if ($result) {
            $key = 'torrent_hash_' . bin2hex($info_hash);
            $this->cache->deleteMulti([
                $key,
                'torrent_details_' . $tid,
                'torrent_peers_' . $tid,
            ]);
        }

        return $result;
    }

    /**
     *
     * @param array $values
     * @param array $update
     *
     * @throws Exception
     *
     * @return bool|int
     */
    public function insert_update(array $values, array $update)
    {
        $id = $this->fluent->insertInto('peers', $values)
                           ->onDuplicateKeyUpdate($update)
                           ->execute();
        $this->cache->delete('torrent_peers_' . $values['torrent']);

        return $id;
    }

    /**
     *
     * @param int $userid
     *
     * @throws Exception
     *
     * @return bool
     */
    public function flush(int $userid)
    {
        $result = $this->fluent$sql = "DELETE FROM 'peers' WHERE ..."; $this->db->perform($sql);;

        return $result;
    }

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function get_count()
    {
        $count = $this->fluent->from('peers')
                              ->select(null)
                              ->select('COUNT(id) AS count')
                              ->fetch('count');

        return $count;
    }
}
