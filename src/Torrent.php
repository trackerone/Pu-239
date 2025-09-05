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
        $this->fluent$sql = "DELETE FROM 'torrents' WHERE ..."; $this->db->perform($sql);;

        $query = $this->fluent->getPdo()
            ->prepare('DELETE likes, comments
                       FROM likes
                       LEFT JOIN comments ON comments.id=likes.comment_id
                       WHERE comments.torrent = ?');
        $query->bindParam(1, $tid);
        $query->execute();

        $this->fluent$sql = "DELETE FROM 'comments' WHERE ..."; $this->db->perform($sql);;

        $this->fluent$sql = "DELETE FROM 'coins' WHERE ..."; $this->db->perform($sql);;

        $this->fluent$sql = "DELETE FROM 'rating' WHERE ..."; $this->db->perform($sql);;

        $this->fluent$sql = "DELETE FROM 'snatched' WHERE ..."; $this->db->perform($sql);;

        $this->fluent$sql = "DELETE FROM 'peers' WHERE ..."; $this->db->perform($sql);;

        $this->fluent$sql = "DELETE FROM 'deathrow' WHERE ..."; $this->db->perform($sql);;
        $update = [
            'torrentid' => 0,
            'status' => 'sourcing',
        ];
        $this->fluent->update('upcoming')
            ->set($update)
            ->where('torrentid != 0')
            ->execute();

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
            $torrent = $this->fluent$sql = "SELECT * FROM 'torrents'"; $this->db->fetchAll($sql);;

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
            $torrents = $this->fluent$sql = "SELECT * FROM 'torrents AS t'"; $this->db->fetchAll($sql);;
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
            $staff_picks = $this->fluent$sql = "SELECT * FROM 'torrents AS t'"; $this->db->fetchAll($sql);;

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
