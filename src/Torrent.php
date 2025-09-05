<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use DI\DependencyException;
use DI\NotFoundException;
use Envms\FluentPDO\Exception;
use MatthiasMullie\Scrapbook\Exception\UnbegunTransaction;
use PDOStatement;
use Spatie\Image\Exceptions\InvalidManipulation;

/**
 * Class Torrent.
 */
class Torrent
{
    protected $cache;
    protected $fluent;
    protected $site_config;
    protected $users_class;
    protected $settings;
    protected $image;

    /**
     * Torrent constructor.
     *
     * @param Cache    $cache
     * @param Database $fluent
     * @param User     $users_class
     * @param Image    $image
     * @param Settings $settings
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, User $users_class, Image $image, Settings $settings)
    {
        $this->settings = $settings;
        $this->site_config = $this->settings->get_settings();
        $this->fluent = $fluent;
        $this->cache = $cache;
        $this->image = $image;
        $this->users_class = $users_class;
    }

    /**
     * @param int $tid
     *
     * @throws Exception
     */
    public function delete_by_id(int $tid)
    {
        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        $query = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
        $update = [
            'torrentid' => 0,
            'status' => 'sourcing',
        ];
        // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        if (file_exists(TORRENTS_DIR . $tid . '.torrent')) {
            unlink(TORRENTS_DIR . $tid . '.torrent');
        }

        if (file_exists(NFO_DIR . $tid . '.png')) {
            unlink(NFO_DIR . $tid . '.png');
        }
    }

    /**
     *
     * @param array $items
     * @param int   $tid
     *
     * @throws Exception
     *
     * @return bool|mixed
     *
     */
    public function get_items(array $items, int $tid)
    {
        $torrent = $this->get($tid);
        if (empty($torrent)) {
            return false;
        }
        $count = count($items);
        $list = [];
        if ($count === 1) {
            if (!empty($torrent[$items[0]])) {
                return $torrent[$items[0]];
            }

            return false;
        }

        foreach ($items as $item) {
            if (!empty($torrent[$item])) {
                $list[$item] = $torrent[$item];
            }
        }

        if (!empty($list)) {
            return $list;
        }

        return false;
    }

    /**
     *
     * @param int  $tid
     * @param bool $fresh
     *
     * @throws Exception
     *
     * @return bool|mixed
     *
     */
    public function get(int $tid, bool $fresh = false)
    {
        $torrent = $this->cache->get('torrent_details_' . $tid);
        if ($torrent === false || is_null($torrent) || $fresh) {
            $torrent = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
            if (empty($torrent)) {
                return $torrent;
            }

            $torrent['previous'] = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $torrent['next'] = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $this->cache->set('torrent_details_' . $tid, $torrent, $this->site_config['expires']['torrent_details']);
        }

        return $torrent;
    }

    /**
     *
     * @param int $userid
     *
     * @throws Exception
     *
     * @return mixed
     *
     */
    public function get_all_snatched(int $userid)
    {
        $torrents = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        if ($query) {
            $this->cache->update_row('torrent_details_' . $tid, $set, $this->site_config['expires']['torrent_details']);
            if ($seeders) {
                $this->cache->deleteMulti([
                    'scroller_torrents_',
                    'slider_torrents_',
                    'latest_torrents_',
                    'top_torrents_',
                    'motw_',
                    'staff_picks_',
                ]);
            }
        }

        return $query;
    }

    /**
     *
     * @param string   $infohash
     * @param null|int $tid
     * @param null|int $owner
     * @param null|int $added
     *
     * @throws Exception
     * @throws UnbegunTransaction
     *
     * @return bool
     *
     */
    public function remove_torrent(string $infohash, int $tid = null, int $owner = null, int $added = null)
    {
        if (strlen($infohash) != 20) {
            return false;
        }
        if (empty($tid) || empty($owner) || empty($added)) {
            $torrent = $this->get_torrent_from_hash($infohash);
            if (!empty($torrent)) {
                $tid = $torrent['id'];
                $owner = $torrent['owner'];
                $added = $torrent['added'];
            }
        }
        if (!empty($tid) && !empty($owner)) {
            $key = 'torrent_hash_' . bin2hex($infohash);
            $this->cache->deleteMulti([
                $key,
                'peers_' . $owner,
                'coin_points_' . $tid,
                'latest_comments_',
                'top_torrents_',
                'latest_torrents_',
                'latest_torrents_' . implode('_', $this->site_config['categories']['movie']),
                'latest_torrents_' . implode('_', $this->site_config['categories']['tv']),
                'scroller_torrents_',
                'torrent_details_' . $tid,
                'latest_torrents_',
                'slider_torrents_',
                'torrent_poster_count_',
                'torrent_banner_count_',
                'backgrounds_',
                'get_torrent_count_',
                'torrent_descr_' . $tid,
                'staff_picks_',
                'motw_',
            ]);
        }

        if ($added > TIME_NOW - (14 * 86400)) {
            $seedbonus = $this->users_class->get_item('seedbonus', $owner);
            $set = [
                'seedbonus' => $seedbonus - $this->site_config['bonus']['per_delete'],
            ];
            // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $this->cache->update_row('user_' . $owner, $set, $this->site_config['expires']['user_cache']);
        }

        return true;
    }

    /**
     *
     * @param string $info_hash
     *
     * @throws Exception
     *
     * @return array|bool
     *
     */
    public function get_torrent_from_hash(string $info_hash)
    {
        $key = 'torrent_hash_' . bin2hex($info_hash);
        $ttl = 21600;
        $torrent = $this->cache->get($key);
        if ($torrent === false || is_null($torrent) || !is_array($torrent)) {
            $tid = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

        return $id;
    }

    /**
     * @throws Exception
     *
     * @return bool|mixed
     *
     */
    public function get_torrent_count()
    {
        $count = $this->cache->get('get_torrent_count_');
        if ($count === false || is_null($count)) {
            $count = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

                        if (!empty($images)) {
                            $this->cache->set('posters_' . $torrent['imdb_id'], $images, 86400);
                        } else {
                            $this->cache->set('posters_' . $torrent['imdb_id'], [], 3600);
                        }
                    }
                    if (!empty($images)) {
                        $scrollers[] = $torrent;
                    }
                }
                if (count($scrollers) >= $this->site_config['latest']['scroller_limit']) {
                    break;
                }
            }
            $torrents = $scrollers;
            $this->cache->set('scroller_torrents_', $torrents, $this->site_config['expires']['scroll_torrents']);
        }
        if (!empty($torrents)) {
            foreach ($torrents as $torrent) {
                if (empty($torrent['poster'])) {
                    $images = $this->cache->get('posters_' . $torrent['imdb_id']);
                    if (!empty($images)) {
                        shuffle($images);
                        $torrent['poster'] = $images[0]['url'];
                    }
                }
                $scroller_torrents[] = $torrent;
            }
        }

        return $scroller_torrents;
    }

    /**
     * @throws Exception
     *
     * @return array
     *
     */
    public function get_latest_slider()
    {
        $sliding_torrents = $imdb_ids = [];
        $torrents = $this->cache->get('slider_torrents_');
        if ($torrents === false || is_null($torrents)) {
            $torrents = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;
                    if (!empty($banners)) {
                        $this->cache->set('banners_' . $torrent['imdb_id'], $banners, 86400);
                    } else {
                        $this->cache->set('banners_' . $torrent['imdb_id'], [], 3600);
                    }
                }
                if (!empty($banners) && !in_array($torrent['imdb_id'], $imdb_ids)) {
                    $sliders[] = $torrent;
                    $imdb_ids[] = $torrent['imdb_id'];
                }

                if (count($sliders) >= $this->site_config['latest']['slider_limit']) {
                    break;
                }
            }

            $torrents = $sliders;
            $this->cache->set('slider_torrents_', $torrents, $this->site_config['expires']['slider_torrents']);
        }

        if (!empty($torrents)) {
            $imdb_ids = [];
            foreach ($torrents as $torrent) {
                $images = $this->cache->get('banners_' . $torrent['imdb_id']);
                if (!empty($images)) {
                    shuffle($images);
                    $torrent['banner'] = $images[0]['url'];
                }
                if (!empty($torrent['banner']) && !in_array($torrent['imdb_id'], $imdb_ids)) {
                    $sliding_torrents[] = $torrent;
                    $imdb_ids[] = $torrent['imdb_id'];
                }
            }
        }

        return $sliding_torrents;
    }

    /**
     * @throws Exception
     *
     * @return array|bool|mixed
     *
     */
    public function get_staff_picks()
    {
        $torrents = [];
        $staff_picks = $this->cache->get('staff_picks_');
        if ($staff_picks === false || is_null($staff_picks)) {
            $staff_picks = // TODO: review query
$sql = "SELECT/INSERT/UPDATE/DELETE ...";
$this->db->perform($sql, [/* params */]);;

            $this->cache->set('imdbs_', $imdbs, 3600);
        }
        foreach ($imdbs as $imdb) {
            $this->get_plot($imdb['imdb_id']);
        }
    }

    /**
     *
     * @param string $imdb
     *
     * @throws Exception
     *
     * @return bool|mixed|null
     *
     */
    public function get_plot(string $imdb)
    {
        $plot = $this->cache->get('imdb_plot_' . $imdb);
        if ($plot === false || is_null($plot)) {
            $plot = $this->fluent->from('imdb_info')
                ->select(null)
                ->select('plot')
                ->where('imdb_id = ?', str_replace('tt', '', $imdb))
                ->fetch('plot');

            if (!empty($plot)) {
                $this->cache->set('imdb_plot_' . $imdb, $plot, 86400);
            } else {
                $this->cache->set('imdb_plot_' . $imdb, 'No plot set', 3600);
            }
        }
        if (!empty($plot)) {
            return $plot;
        }

        return null;
    }

    /**
     *
     * @param int $torrentid
     *
     * @throws Exception
     * @throws DependencyException
     * @throws NotFoundException
     * @throws InvalidManipulation
     *
     * @return false|mixed|string|string[]|null
     *
     */
    public function format_descr(int $torrentid)
    {
        $descr = $this->cache->get('torrent_descr_' . $torrentid);
        if ($descr === false || is_null($descr)) {
            $torrent = $this->get($torrentid);
            if (!empty($torrent)) {
                $descr = $torrent['descr'];
                if (!empty($descr)) {
                    if (!preg_match('/\[pre\].*\[\/pre\]/isU', $descr)) {
                        $descr = '[pre]' . $descr . '[/pre]';
                    }
                    require_once INCL_DIR . 'function_bbcode.php';
                    $descr = format_comment($descr);
                    $this->cache->set('torrent_descr_' . $torrentid, $descr, 86400);
                }
            }
        }

        return $descr;
    }
}
