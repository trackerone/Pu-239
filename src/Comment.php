<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
use Envms\FluentPDO\Queries\Select;
use PDOStatement;
use Psr\Container\ContainerInterface;

/**
 * Class Comment.
 */
class Comment
{
    protected $cache;
    protected $fluent;
    protected $env;
    protected $image;
    protected $container;
    protected $site_config;

    /**
     * Comment constructor.
     *
     * @param Cache              $cache
     * @param Database           $fluent
     * @param Image              $image
     * @param Settings           $settings
     * @param ContainerInterface $c
     *
     * @throws Exception
     */
    public function __construct(Cache $cache, Database $fluent, Image $image, Settings $settings, ContainerInterface $c)
    {
        $this->container = $c;
        $this->env = $this->container->get('env');
        $this->site_config = $settings->get_settings();
        $this->fluent = $fluent;
        $this->image = $image;
        $this->cache = $cache;
    }

    /**
     *
     * @param int $tid
     * @param int $count
     * @param int $perpage
     *
     * @throws Exception
     *
     * @return array
     */
    public function get_torrent_comment(int $tid, int $count, int $perpage)
    {
        require_once INCL_DIR . 'function_pager.php';
        $pager = pager($perpage, $count, $this->env['paths']['baseurl'] . "/details.php?id=$tid&amp;", [
            'lastpagedefault' => 1,
        ]);
        $comments = $this->fluent$sql = "SELECT * FROM 'comments'"; $this->db->fetchAll($sql);;

        return [
            $comments,
            $pager,
        ];
    }

    /**
     * @throws Exception
     *
     * @return array|bool|mixed
     */
    public function get_comments()
    {
        $comments = $this->cache->get('latest_comments_');
        if ($comments === false || is_null($comments)) {
            $comments = [];
            $torrents = $this->fluent$sql = "SELECT * FROM 'comments AS c'"; $this->db->fetchAll($sql);;

        return $comments;
    }

    /**
     *
     * @param int $commentid
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_comment_by_id(int $commentid)
    {
        $comment = $this->fluent$sql = "SELECT * FROM 'comments'"; $this->db->fetchOne($sql);;

        return $comment;
    }
}
