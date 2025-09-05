<?php
require_once __DIR__ . '/../include/runtime_safe.php';

require_once __DIR__ . '/../include/bootstrap_pdo.php';


declare(strict_types = 1);

namespace Pu239;

use Envms\FluentPDO\Exception;
// removed FluentPDO Literal
use Envms\FluentPDO\Queries\Select;
use Psr\Container\ContainerInterface;

/**
 * Class Image.
 */
class Image
{
    protected $fluent;
    protected $env;
    protected $limit;
    protected $container;
    protected $cache;

    /**
     * Image constructor.
     *
     * @param Database           $fluent
     * @param Cache              $cache
     * @param ContainerInterface $c
     */
    public function __construct(Database $fluent, Cache $cache, ContainerInterface $c)
    {
        $this->container = $c;
        $this->env = $this->container->get('env');
        $this->fluent = $fluent;
        $this->cache = $cache;
        $this->limit = $this->env['db']['query_limit'];
    }

    /**
     * @param array $values
     *
     * @throws \Exception
     */
    public function insert(array $values)
    {
        // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
    }

    /**
     * @param array $values
     *
     * @throws Exception
     */
    public function insert_update(array $values)
    {
        $update = [
            'imdb_id' => new Literal('VALUES(imdb_id)'),
            'tmdb_id' => new Literal('VALUES(tmdb_id)'),
            'type' => new Literal('VALUES(type)'),
        ];
        $count = (int) ($this->limit / max(array_map('count', $values)));
        foreach (array_chunk($values, $count) as $t) {
            // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
        }
    }

    /**
     * @param array $values
     * @param array $update
     *
     * @throws Exception
     */
    public function update(array $values, array $update)
    {
        $count = (int) ($this->limit / max(array_map('count', $values)));
        foreach (array_chunk($values, $count) as $t) {
            // TODO: review insert
$sql = "INSERT INTO table (...) VALUES (...)";
$this->db->perform($sql, [/* params */]);;
        }
    }

    /**
     *
     * @param string $imdb
     * @param string $type
     *
     * @throws Exception
     *
     * @return string|null
     */
    public function find_images(string $imdb, string $type = 'poster')
    {
        $images = $this->cache->get($type . '_' . $imdb);
        if ($images === false || is_null($images)) {
            $images = // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

            if (!empty($images)) {
                $this->cache->set($type . '_' . $imdb, $images, 86400);
            } else {
                $this->cache->set($type . '_' . $imdb, [], 3600);
            }
        }

        if (!empty($images)) {
            shuffle($images);
            $image = $images[0]['url'];

            return $image;
        }

        return null;
    }

    /**
     *
     * @param int $limit
     * @param int $offset
     *
     * @throws Exception
     *
     * @return array|bool
     */
    public function get_images(int $limit, int $offset)
    {
        return // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;
    }

    /**
     *
     * @param string $url
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get_image(string $url)
    {
        return // TODO: review query
$sql = "SELECT * FROM table WHERE ...";
$this->db->fetchAll($sql, [/* params */]);;

        return $query;
    }
}
